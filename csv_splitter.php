<?php

/**
 * @file
 * This file is a simple, commandline front end for the included
 * PES_CSV_Splitter class.  It allows for splitting single, large
 * CSV file into several, smaller ones.  Configuration options
 * are described below, or can be read by running "php csv_splitter.php --help"
 * on the command line.
 */

// The used PEAR Console_Getargs class isn't PHP5 compliant, so hide
// hide the depreciated error messages.
error_reporting(E_ALL ^ E_DEPRECATED);

include 'Console/Getargs.php';
include 'File/CSV.php';
include 'PES/CSV/Splitter.php';

$config = array(
  'dest' => array(
    'desc' => 'The path to write the created, child CSV files to',
    'min' => 0,
    'max' => 1,
    'default' => __DIR__,
  ),
  'lines' => array(
    'desc' => 'The maximum number of lines to write to each child CSV file.',
    'min' => 0,
    'max' => 1,
    'default' => 100,
  ),
  'has-header' => array(
    'max' => 0,
    'default' => 0,
    'desc' => 'Whether the first line of the given CSV file should be treated as a header row, to be added to each child file.',
  ),
  'source' => array(
    'desc' => 'The CSV file to split into child files.',
    'min' => 0,
    'max' => 1,
  ),
);

$args = Console_Getargs::factory($config);

if (PEAR::isError($args)) {

  if ($args->getCode() === CONSOLE_GETARGS_ERROR_USER) {

  echo Console_Getargs::getHelp($config, NULL, $args->getMessage());

  } else if ($args->getCode() === CONSOLE_GETARGS_HELP) {

    echo Console_Getargs::getHelp($config);

  }

} else {

  $splitter = new PES_CSV_Splitter();
  $created_files = $splitter
    ->setLinesPerFile($args->getValue('lines'))
    ->setOutputDirectory($args->getValue('dest'))
    ->setFileHasHeader(!!$args->getValue('has-header'))
    ->parse($args->getValue('source'));

  echo 'Wrote ' . count($created_files) . ' CSV files:' . PHP_EOL;

  foreach ($created_files as $a_file) {
  
    echo ' - ' . $a_file . PHP_EOL;
  }
}