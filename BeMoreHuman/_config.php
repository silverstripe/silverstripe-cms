<?php

if($_SERVER['HTTP_HOST'] == 'test') {
	header("Location: http://test.silverstripe.com$_SERVER[REQUEST_URI]");
	die();
}
if($_SERVER['HTTP_HOST'] == 'dev') {
	header("Location: http://dev.silverstripe.com$_SERVER[REQUEST_URI]");
	die();
}

global $project;
$project = 'BeMoreHuman';

global $database;
$database = 'SS_bemorehuman';

//MySQLDatabase::set_connection_charset('utf8');

// Use _ss_environment.php file for configuration
require_once("conf/ConfigureFromEnv.php");

Director::addRules(100, array(
));

Email::setAdminEmail('support@silverstripe.com');

// This line set's the current theme. More themes can be
// downloaded from http://www.silverstripe.com/cms-themes-and-skin
SSViewer::set_theme('blackcandy');

// enable nested URLs for this site (e.g. page/sub-page/)
SiteTree::enable_nested_urls();

date_default_timezone_set('Pacific/Auckland');

?>
