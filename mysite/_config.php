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

i18n::set_locale('en_NZ');
date_default_timezone_set('Pacific/Auckland');

if (class_exists('SiteTree')) SiteTree::enable_nested_urls();

SiteTree::set_create_default_pages(false);

Object::add_extension('SiteTree', 'WorkflowApplicable');
Object::add_extension('SiteTree', 'WorkflowEmbargoExpiryExtension');
