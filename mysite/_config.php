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

// redirect some requests to the secure domain
if(defined('CWP_SECURE_DOMAIN') && @$_SERVER['HTTP_X_FORWARDED_PROTOCOL'] != 'https') {
	Director::forceSSL(array('/^Security/'), CWP_SECURE_DOMAIN);
	// Note 1: the platform always redirects "/admin" to CWP_SECURE_DOMAIN regardless of what you set here
	// Note 2: if you have your own certificate installed, you can use your own domain, just omit the second parameter:
	//   Director::forceSSL(array('/^Security/'));
	//
	// See Director::forceSSL for more information.
}

MySQLDatabase::set_connection_charset('utf8');

SSViewer::set_theme('default');

date_default_timezone_set('Pacific/Auckland');

if (class_exists('SiteTree')) SiteTree::enable_nested_urls();

SiteTree::add_extension('WorkflowApplicable');
SiteTree::add_extension('WorkflowEmbargoExpiryExtension');
