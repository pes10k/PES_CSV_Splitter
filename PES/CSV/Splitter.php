<?php

/**
 * PES_CSV_Splitter
 *
 * This class allows for simple splitting of a large CSV
 * file into smaller pieces, for easier parsing by memory-limited tools.
 * The header / first line of the CSV file is preserved across all
 * of the generated CSV files.
 *
 * Generated files are named with the name of the master / source CSV
 * file, an incrementing number, and the a CSV extension.  So, if provided
 * a CSV file with 1000 lines in it named "example.csv", this class would
 * write 10 files, named "example-1.csv" through "example-10.csv", in
 * the specified directory.
 *
 * This class requires the File_CSV class from PEAR and PHP 5.3
 *
 * @category    File
 * @package     File
 * @author      Peter Snyder <snyderp@gmail.com>
 * @version 1.0
 * @see         http://pear.php.net/package/File_CSV
 */
class PES_CSV_Splitter {

  /**
   * Reference to a single, File_CSV instance, reused for each time
   * a CSV file is split.
   *
   * @var File_CSV
   * @access private
   */
  private $parser;

  /**
   * The maximum number of data lines (ie, not including the header
   * of the CSV file) that should be included in each generated CSV
   * file.
   *
   * (default value: 100)
   *
   * @var int
   * @access private
   */
  private $lines_per_file = 100;

  /**
   * The path to write the outputted CSV files to.  On instantiation,
   * this will be set to the current directory.
   *
   * (default value: '')
   *
   * @var string
   * @access private
   */
  private $output_directory = '';

  /**
   * A boolean description of whether the given, master CSV file has
   * a header row.  If so, this header row will be copied to each
   * child, created CSV file.
   *
   * (default value: FALSE)
   *
   * @var bool
   * @access private
   */
  private $file_has_header = FALSE;

  /**
   * The constructor is overloaded to set the output directory to the current
   * directory, and to instantiate the CSV parser.
   */
  public function __construct () {

    $this->setOutputDirectory(__DIR__);
    $this->parser = new File_CSV();
  }

  /**
   * Attempts to parse the given CSV file into smaller CSV files, each with only
   * as many lines as specified by the caller.
   *
   * @param string $path_to_csv_file
   *   The path to the CSV file to be parsed.
   *
   * @throws Exception
   *   Exceptions are thrown if the given path isn't a readable file, if the
   *   set output directory isn't a writeable directory path, or if the CSV
   *   file specified can't be parsed by the PEAR File_CSV class.
   *
   * @return array
   *   Returns an array of zero or more strings, each being the name of a CSV
   *   file that was written to disk.
   */
  public function parse ($path_to_csv_file) {

    if ( ! is_file($path_to_csv_file) OR ! is_readable($path_to_csv_file)) {

      throw new Exception('"' . $path_to_csv_file . '" is not a readable file.');

    } else if ( ! is_dir($this->outputDirectory())) {

      throw new Exception('"' . $this->outputDirectory() . '" is not a valid directory.');

    } else if ( ! is_writable($this->outputDirectory())) {

      throw new Exception('"' . $this->outputDirectory() . '" is not writeable by the current process.');

    }

    // Once we've verified that we have a valid input file and that the
    // destination given to write to is actually a writeable directory,
    // the last needed check is to make sure that the specified file
    // looks like a valid CSV file

    $csv_format = $this->parser->discoverFormat($path_to_csv_file);

    if (PEAR::isError($csv_format)) {

      throw new Exception($csv_format->getMessage());

    } else {

      $file_name_parts = $this->parseFileName(basename($path_to_csv_file));

      // If the caller specified that the first row of the source file
      // should be treated as a header, extract it and hold onto it, so
      // we can write it to each child file.
      if ($this->fileHasHeader()) {
      
        $header_row = $this->parser->read($path_to_csv_file, $csv_format);
        
        if ( ! $header_row) {        
          $this->parser->readQuoted($path_to_csv_file, $csv_format);
        }

      } else {
      
        $header_row = FALSE;

      }

      // The nth file that we've created from the contents of the parent
      // CSV file.
      $current_file = 0;

      // The count of the row of the parent CSV file we're currently
      // reading.
      $current_row_count = 0;

      // An array to track all the child files that CSV contents were
      // written to.
      $child_files = array();

      // Now, iterate over the given CSV file, and every time we hit the
      // specified number of rows, create a new child file and start writing
      // to it.
      while (($row = $this->parser->read($path_to_csv_file, $csv_format)) OR
        ($row = $this->parser->readQuoted($path_to_csv_file, $csv_format))) {

        if ($current_row_count % $this->linesPerFile() === 0) {

          $current_file += 1;

          $child_files[] = $this->pathToCurrentChildFile($file_name_parts, $current_file);

          if ($header_row) {

            $this->parser->write(
              $this->pathToCurrentChildFile($file_name_parts, $current_file),
              $header_row,
              $csv_format
            );
          }
        }

        $this->parser->write(
          $this->pathToCurrentChildFile($file_name_parts, $current_file),
          $row,
          $csv_format
        );

        $current_row_count += 1;
      }

      return $child_files;
    }
  }

  /* =================== */
  /* ! Getter / Setters  */
  /* =================== */

  /**
   * Returns the maximum number of non-header lines that will be written into
   * each generated, child CSV file.
   *
   * @return int
   */
  public function linesPerFile () {

    return $this->lines_per_file;
  }

  /**
   * Sets the maximum number of non-header lines that will be written into
   * each generated, child CSV file.  If the source CSV file has more than
   * this number of lines, a new child CSV file will be created.
   *
   * @param int $num_lines_per_file
   *   The maximum number of lines that will be written into any child CSV
   *   file.
   *
   * @return PES_CSV_Splitter
   *   A refernece to the current object, for method chaining
   */
  public function setLinesPerFile ($num_lines_per_file) {

    $this->lines_per_file = $num_lines_per_file;
    return $this;
  }

  /**
   * Returns the directory path that the child CSV files will be written to.
   *
   * @return string
   *   A string representation of a filesystem path.
   */
  public function outputDirectory () {

    return $this->output_directory;
  }

  /**
   * Sets the directory path that child CSV files should be written to.
   * If the provided directory path ends in a '/', its normalzied
   * by stripping the last path seperator off.
   *
   * @param string $a_path
   *   The string representation of a path on the local filesystem that
   *   is writeable by the current process.
   *
   * @return PES_CSV_Splitter
   *   A refernece to the current object, for method chaining
   */
  public function setOutputDirectory ($a_path) {

    $this->output_directory = rtrim($a_path, DIRECTORY_SEPARATOR);
    return $this;
  }

  /**
   * Returns the given, caller setting of whether the first line of the given,
   * source CSV file has a header row that should be copied to each written,
   * child CSV file.
   *
   * @return bool
   *   The given boolean description of whether the first line of the source CSV
   *   file should be treated as header information to be copied to each
   *   child file.
   */
  public function fileHasHeader () {

    return $this->file_has_header;
  }

  /**
   * Sets whether the first row of the source CSV file should be treated as a
   * header.  If TRUE, the first row of the source CSV file will be copied
   * to each written, child CSV file.
   *
   * @param bool $a_bool
   *   A boolean description of whether the first row in the source CSV file
   *   is a header (and not data)
   *
   * @return PES_CSV_Splitter
   *   A refernece to the current object, for method chaining
   */
  public function setFileHasHeader ($a_bool) {

    $this->file_has_header = $a_bool;
    return $this;
  }

  /* ========================= */
  /* ! Private Helper Methods  */
  /* ========================= */

  /**
   * Finds the base and extension of a given file name.  If the file name has
   * no extension, the entire file is treated as the filename.  So, for example,
   * given "example.txt", this method would return an array with the key "base"
   * being "example", and "extension" being "txt".  But given the file
   * "another-example", this method returns "base" being "another-example" and
   * "extension" being an empty string.
   *
   * @param string $file_name
   *   A filename to split into a base name and file extension.
   *
   * @return array|bool
   *   Returns FALSE on invalid input.  Otherwise, returns an array with two
   *   keys, "base", which is the main, pre-extension part of the file name, and
   *   "extension", which is the text after the last period, if any exists.
   */
  protected function parseFileName ($file_name) {

    if (empty($file_name) OR !is_string($file_name)) {

      return FALSE;

    } else {

      $dot_position = strripos($file_name, '.');

      if ($dot_position === FALSE) {

        return array(
          'base' => $file_name,
          'extension' => '',
        );

      } else {

        return array(
          'base' => substr($file_name, 0, $dot_position),
          'extension' => substr($file_name, $dot_position + 1),
        );
      }
    }
  }

  /**
   * Generates and returns the path to the given numbered child CSV file.
   *
   * @param array $child_file_name_format
   *   An array describing how the child files should be named.  This should
   *   be an array returned by the PES_CSV_Splitter::parseFileName() method.
   * @param int $current_child_file_index
   *   The index / count of the current file being written to.
   *
   * @return string
   *   The path to the child CSV file that should be written to.
   */
  protected function pathToCurrentChildFile ($child_file_name_format, $current_child_file_index) {

    $file_name = $child_file_name_format['base'] . '-' . $current_child_file_index;

    if ( ! empty($child_file_name_format['extension'])) {

      $file_name .= '.';
    }

    $file_name .= $child_file_name_format['extension'];

    return $this->outputDirectory() . DIRECTORY_SEPARATOR . $file_name;
  }
}