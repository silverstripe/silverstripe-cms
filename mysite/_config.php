<?php

global $project;
$project = 'mysite';

global $database;
$database = "SS_mysite";

require_once("conf/ConfigureFromEnv.php");

// This line set's the current theme. More themes can be
// downloaded from http://www.silverstripe.com/themes/
SSViewer::set_theme('blackcandy');

?>
