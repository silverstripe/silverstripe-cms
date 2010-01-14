<?php

if(defined('SS_DATABASE_CLASS') && SS_DATABASE_CLASS == 'SQLite3Database') {
	global $databaseConfig;
	$databaseConfig = array(
		'type' => 'SQLite3Database',
		'database' => (defined('SS_DATABASE_PREFIX') ? SS_DATABASE_PREFIX : '') . $database . (defined('SS_DATABASE_SUFFIX') ? SS_DATABASE_SUFFIX : ''),
		'path' => defined('SS_SQLITE3_DATABASE_PATH') && SS_SQLITE3_DATABASE_PATH ? SS_SQLITE3_DATABASE_PATH : ASSETS_PATH,
		'key'  => defined('SS_SQLITE3_DATABASE_KEY')  && SS_SQLITE3_DATABASE_KEY  ? SS_SQLITE3_DATABASE_KEY :  'SQLite3DatabaseKey',
		'memory' => true,
	);
}

