<?php

global $project;
$project = 'mysite';

global $database;
$database = "";

require_once("conf/ConfigureFromEnv.php");

MySQLDatabase::set_connection_charset('utf8');

// Use SQLite on this project
/*
global $databaseConfig;
$databaseConfig = array(
	"type" => "SQLite3Database",
	"memory" => true, // only used for testing
	"path" => ASSETS_PATH,
	"database" => "data.sqlite",
	
);
*/

// This line set's the current theme. More themes can be
// downloaded from http://www.silverstripe.com/themes/
SSViewer::set_theme('blackcandy');

// enable nested URLs for this site (e.g. page/sub-page/)
SiteTree::enable_nested_urls();
