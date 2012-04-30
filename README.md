PES_CSV_Splitter
================

Simple PHP Class and command line script for splitting a CSV file into several
child files.

Requirements
===

This class requires PHP 5.3 or later, and the PEAR File_CSV class (see 
http://pear.php.net/package/File_CSV).

Description
===

This class allows for simple splitting of a large CSV file into smaller pieces,
for easier parsing by memory-limited tools.  The header / first line of the CSV
file is preserved across all of the generated CSV files.

Generated files are named with the name of the master / source CSV file, an
incrementing number, and the a CSV extension.  So, if provided a CSV file with
1000 lines in it named "example.csv", this class would write 10 files, named
"example-1.csv" through "example-10.csv", in the specified directory.

A sample, command line tool for use with the class is also included.  This
file, "csv_splitter.php", requires the PEAR Console_Getargs package (see
http://pear.php.net/package/Console_Getargs).  Information on using this tool
is available by running "php csv_splitter.php --help" on the command line.

Author
===
Peter E. Snyder - snyderp@gmail.com
