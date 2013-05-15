<?php

global $project;
$project = 'mysite';

global $database;

// Pick up the database name from the _ss_environment.php.
if (defined('SS_DATABASE_NAME') && SS_DATABASE_NAME) {
	$database = SS_DATABASE_NAME;
} else {
	$database = 'SS_cwp';
}

require_once('conf/ConfigureFromEnv.php');

date_default_timezone_set('Pacific/Auckland');

## NOTE: Any configuration ideally goes into _config/config.yml

