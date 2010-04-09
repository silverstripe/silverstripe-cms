<?php

global $project;
$project = 'mysite';

global $database;
$database = 'SS_ss24';

require_once('conf/ConfigureFromEnv.php');

MySQLDatabase::set_connection_charset('utf8');

// This line set's the current theme. More themes can be
// downloaded from http://www.silverstripe.org/themes/
SSViewer::set_theme('blackcandy');

// Set the site locale
i18n::set_locale('en_US');

// enable nested URLs for this site (e.g. page/sub-page/)
SiteTree::enable_nested_urls();

// Mollom config 
Mollom::setPublicKey("1819023dfcb10a667d10bd1578c5f39b");
Mollom::setPrivateKey("2430fcbd65756ac6732a54a7c9ada116");
SpamProtectorManager::set_spam_protector('MollomSpamProtector');