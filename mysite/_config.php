<?php

global $project;
$project = 'mysite';

global $database;

// find the database name from the environment file
if(defined('SS_DATABASE_NAME') && SS_DATABASE_NAME) {
	$database = SS_DATABASE_NAME;
} else {
	$database = 'SS_cwp';
}

require_once('conf/ConfigureFromEnv.php');

date_default_timezone_set('Pacific/Auckland');

// Set default FROM field for emails. Reconfigure this if you would like to use a custom address.
Email::setAdminEmail('admin@cwp.govt.nz');

## NOTE: Any SilverStripe configuration ideally goes into mysite/_config/config.yml
## which uses the {@link Config} API instead of manipulating statics directly.
## Check out "configuration.md" in the framework docs for more information.

