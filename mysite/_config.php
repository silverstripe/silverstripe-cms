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

MySQLDatabase::set_connection_charset('utf8');

SSViewer::set_theme('default');

date_default_timezone_set('Pacific/Auckland');

if (class_exists('SiteTree')) SiteTree::enable_nested_urls();

SiteTree::add_extension('WorkflowApplicable');
SiteTree::add_extension('WorkflowEmbargoExpiryExtension');
