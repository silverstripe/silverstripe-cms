<?php

global $project;
$project = 'mysite';

global $database;
$database = '';
//require_once('conf/ConfigureFromEnv.php');

Director::set_environment_type("dev");
global $databaseConfig;

/*
$databaseConfig = array(
	'type' => 'PostgreSQLDatabase',
	'server' => 'localhost',
	'username' => 'postgres',
	'password' => 'postgres',
	'database' => 'SS_24modules'
);

*/

$databaseConfig = array(
	"type" => 'SQLiteDatabase',
	"server" => '', 
	"username" => '', 
	"password" => '', 
	"database" => '24modules',
	"path" => ASSETS_PATH .'/.db',
);


MySQLDatabase::set_connection_charset('utf8');

// This line set's the current theme. More themes can be
// downloaded from http://www.silverstripe.org/themes/
SSViewer::set_theme('blackcandy');

// enable nested URLs for this site (e.g. page/sub-page/)
SiteTree::enable_nested_urls();

Security::setDefaultAdmin('username', 'password');