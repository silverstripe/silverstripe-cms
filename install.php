<?php

/************************************************************************************
 ************************************************************************************
 **                                                                                **
 **  If you can read this text in your browser then you don't have PHP installed.  **
 **  Please install PHP 5.0 or higher, preferably PHP 5.2.                         **
 **                                                                                **
 ************************************************************************************
 ************************************************************************************/

/**
 * SilverStripe CMS Installer
 * This installer doesn't use any of the fancy Sapphire stuff in case it's unsupported.
 * It's also PHP4 syntax compatable
 */

// speed up mysql_connect timeout if the server can't be found
ini_set('mysql.connect_timeout', 5);

ini_set('max_execution_time', 0);
error_reporting(E_ALL ^ E_NOTICE);
session_start();

$majorVersion = strtok(phpversion(),'.');
if($majorVersion < 5) {
	header("HTTP/1.1 500 Server Error");
	echo str_replace('$PHPVersion', phpversion(), file_get_contents("sapphire/dev/install/php5-required.html"));
	die();
}

// Include environment files
$usingEnv = false;
$envFiles = array('_ss_environment.php', '../_ss_environment.php', '../../_ss_environment.php');
foreach($envFiles as $envFile) {
        if(@file_exists($envFile)) {
                include_once($envFile);
                $usingEnv = true;				
                break;
        }
}


// Load database config
if(isset($_REQUEST['mysql'])) {
	$databaseConfig = $_REQUEST['mysql'];
} else {
	$_REQUEST['mysql'] = $databaseConfig = array(
		"type" => "MySQLDatabase",
		"server" => defined('SS_DATABASE_SERVER') ? SS_DATABASE_SERVER : "localhost",
		"username" => defined('SS_DATABASE_USERNAME') ? SS_DATABASE_USERNAME : "root",
		"password" => defined('SS_DATABASE_PASSWORD') ? SS_DATABASE_PASSWORD : "",
		"database" => isset($_SERVER['argv'][2]) ? $_SERVER['argv'][2] : "SS_mysite",
	);
}

if(isset($_REQUEST['admin'])) {
	$adminConfig = $_REQUEST['admin'];
} else {
	$_REQUEST['admin'] = $adminConfig = array(
		'username' => 'admin',
		'password' => '',
		'firstname' => '',
		'surname' => ''
	);
}

$alreadyInstalled = false;
if(file_exists('mysite/_config.php')) {
	// Find the $database variable in the relevant config file without having to execute the config file
	if(preg_match("/\\\$database\s*=\s*[^\n\r]+[\n\r]/", file_get_contents("mysite/_config.php"), $parts)) {
		eval($parts[0]);
		if($database) $alreadyInstalled = true;
	// Assume that if $databaseConfig is defined in mysite/_config.php, then a non-environment-based installation has
	// already gone ahead
	} else if(preg_match("/\\\$databaseConfig\s*=\s*[^\n\r]+[\n\r]/", file_get_contents("mysite/_config.php"), $parts)) {
		$alreadyInstalled = true;
	}
	
}

if(file_exists('sapphire/silverstripe_version')) {
	$sapphireVersionFile = file_get_contents('sapphire/silverstripe_version');
		if(strstr($sapphireVersionFile, "/sapphire/trunk")) {
			$silverstripe_version = "trunk";
		} else {
			preg_match("/sapphire\/(?:(?:branches)|(?:tags))(?:\/rc)?\/([A-Za-z0-9._-]+)\/silverstripe_version/", $sapphireVersionFile, $matches);
			$silverstripe_version = $matches[1];
		}
} else {
	$silverstripe_version = "unknown";
}

// Check requirements
$req = new InstallRequirements();
$req->check();

if($req->hasErrors()) {
	$hasErrorOtherThanDatabase = true;
}

if($databaseConfig) {
	$dbReq = new InstallRequirements();
	$dbReq->checkdatabase($databaseConfig);
}

// Actual processor
$installFromCli = (isset($_SERVER['argv'][1]) && $_SERVER['argv'][1] == 'install');

// CLI-install error message.  exit(1) will halt any makefile.
if($installFromCli && ($req->hasErrors() || $dbReq->hasErrors())) {
	echo "Cannot install due to errors:\n";
	$req->listErrors();
	$dbReq->listErrors();
	exit(1);
}

if((isset($_REQUEST['go']) || $installFromCli) && !$req->hasErrors() && !$dbReq->hasErrors() && $adminConfig['username'] && $adminConfig['password']) {
	// Confirm before reinstalling
	if(!isset($_REQUEST['force_reinstall']) && !$installFromCli && $alreadyInstalled) {
		include('sapphire/dev/install/config-form.html');
		
	} else {
		$inst = new Installer();
		if($_REQUEST) $inst->install($_REQUEST);
		else $inst->install(array(
			'database' => $databaseConfig['type'],
			'mysql' => $databaseConfig,
			'admin' => $adminConfig,
		));
	}

// Show the config form
} else {
	include('sapphire/dev/install/config-form.html');	
}

/**
 * This class checks requirements
 * Each of the requireXXX functions takes an argument which gives a user description of the test.  It's an array
 * of 3 parts:
 *  $description[0] - The test catetgory
 *  $description[1] - The test title
 *  $description[2] - The test error to show, if it goes wrong
 */
 
class InstallRequirements {
	var $errors, $warnings, $tests;
	
	/**
	 * Just check that the database configuration is okay
	 */
	function checkdatabase($databaseConfig) {
		if($this->requireFunction(
			'mysql_connect',
			array(
				"PHP Configuration",
				"MySQL support",
				"MySQL support not included in PHP.")
			)
		) {
			if($this->requireMySQLServer(
				$databaseConfig['server'],
				array(
					"MySQL Configuration",
					"MySQL server exists",
					"I couldn't find a MySQL server on '$databaseConfig[server]'", $databaseConfig['server']
				)
			)) {
				if($this->requireMysqlConnection(
					$databaseConfig['server'],
					$databaseConfig['username'],
					$databaseConfig['password'], 
					array(
						"MySQL Configuration",
						"MySQL access credentials correct",
						"That username/password doesn't work"
					)
				)) {
					@$this->requireMySQLVersion(
						"4.1",
						array(
							"MySQL Configuration",
							"MySQL version at least 4.1",
							"MySQL version 4.1 is required, you only have ",
							"MySQL " . mysql_get_server_info()
						)
					);
				}
				$this->requireDatabaseOrCreatePermissions(
					$databaseConfig['server'],
					$databaseConfig['username'],
					$databaseConfig['password'],
					$databaseConfig['database'], 
					array(
						"MySQL Configuration",
						"Can I access/create the database",
						"I can't create new databases and the database '$databaseConfig[database]' doesn't exist"
					)
				);
			}
		}
	}
	
	
	/**
	 * Check everything except the database
	 */
	function check() {
		$this->errors = null;
		
		$this->requirePHPVersion('5.2.0', '5.0.4', array("PHP Configuration", "PHP5 installed", null, "PHP version " . phpversion()));

		// Check that we can identify the root folder successfully
		$this->requireFile('sapphire/dev/install/config-form.html', array("File permissions", 
			"Does the webserver know where files are stored?", 
			"The webserver isn't letting me identify where files are stored.",
			$this->getBaseDir()
			));		
		$this->requireFile('mysite', array("File permissions", "mysite/ folder exists", "There's no mysite folder."));
		$this->requireFile('sapphire', array("File permissions", "sapphire/ folder exists", "There's no sapphire folder."));
		$this->requireFile('cms', array("File permissions", "cms/ folder exists", "There's no cms folder."));
		$this->requireWriteable('.htaccess', array("File permissions", "Is the .htaccess file writeable?", null));
		$this->requireWriteable('mysite/_config.php', array("File permissions", "Is the mysite/_config.php file writeable?", null));
		$this->requireWriteable('assets', array("File permissions", "Is the assets/ folder writeable?", null));
		
		$this->requireTempFolder(array('File permissions', 'Is the temporary folder writeable?', null));
		
		// Check for web server, unless we're calling the installer from the command-line
		if(!isset($_SERVER['argv']) || !$_SERVER['argv']) { 
			$webserver = strip_tags(trim($_SERVER['SERVER_SIGNATURE']));
			if(!$webserver) {
				if(isset($_SERVER['SERVER_SOFTWARE'])) {
					if(strpos($_SERVER['SERVER_SOFTWARE'], 'IIS') !== false ||
						strpos($_SERVER['SERVER_SOFTWARE'], 'Apache') !== false) {
						$webserver = $_SERVER['SERVER_SOFTWARE'];
					}
				} else {
					$webserver = "I can't tell what webserver you are running";
				}
			}
			
			$this->isRunningWebServer(array("Webserver Configuration", "Server software", "$webserver.  Without Apache I can't tell if mod_rewrite is enabled.", $webserver));
			if(function_exists('apache_get_modules')) {
				$this->requireApacheModule('mod_rewrite', array("Webserver Configuration", "mod_rewrite enabled", "You need mod_rewrite to run SilverStripe CMS, but it is not enabled."));
			} elseif(strpos($webserver, 'IIS') !== false) {
				$this->requireIISRewriteModule('IIS_UrlRewriteModule', array("Webserver Configuration", "IIS URL Rewrite Module enabled", "You need to enable the IIS URL Rewrite Module, but it is not installed or enabled. Download it for IIS 7 from http://www.iis.net/expand/URLRewrite"));
			} else {
				$this->warning(array("Webserver Configuration", "URL rewrite enabled", "I can't tell whether any rewriting module is running.  You may need to configure a rewriting rule yourself."));
			}
		
			$this->requireServerVariables(array('SCRIPT_NAME','HTTP_HOST','SCRIPT_FILENAME'), array("Webserver config", "Recognised webserver", "You seem to be using an unsupported webserver.  The server variables SCRIPT_NAME, HTTP_HOST, SCRIPT_FILENAME need to be set."));
		}
		
		// Check for GD support
		if(!$this->requireFunction("imagecreatetruecolor", array("PHP Configuration", "GD2 support", "PHP must have GD version 2."))) {
			$this->requireFunction("imagecreate", array("PHP Configuration", "GD2 support", "GD support for PHP not included."));
		}
		
		// Check for XML support
		$this->requireFunction('xml_set_object', array("PHP Configuration", "XML support", "XML support not included in PHP."));
		
		// Check for MySQL support
		$this->requireFunction('mysql_connect', array("PHP Configuration", "MySQL support", "MySQL support not included in PHP."));
		
		// Check for token_get_all
		$this->requireFunction('token_get_all', array("PHP Configuration", "PHP Tokenizer", "PHP tokenizer support not included in PHP."));
		
		
		// Check memory allocation
		$this->requireMemory(32*1024*1024, 64*1024*1024, array("PHP Configuration", "Memory allocated (PHP config option 'memory_limit')", "SilverStripe needs a minimum of 32M allocated to PHP, but recommends 64M.", ini_get("memory_limit")));

		// Check that troublesome classes don't exist
		$badClasses = array('Query', 'HTTPResponse');
		$this->requireNoClasses($badClasses, array("PHP Configuration", "Check that certain classes haven't been defined by PHP plugins", "Your version of PHP has defined some classes that conflict with SilverStripe's"));
			
		return $this->errors;
	}
	
	function suggestPHPSetting($settingName, $settingValues, $testDetails) {
		$this->testing($testDetails);
		
		$val = ini_get($settingName);
		if(!in_array($val, $settingValues) && $val != $settingValues) {
			$testDetails[2] = "$settingName is set to '$val' in php.ini.  $testDetails[2]";
			$this->warning($testDetails);
		}
	}
	
	function requireMemory($min, $recommended, $testDetails) {
		$_SESSION['forcemem'] = false;
		
		$mem = $this->getPHPMemory();
		if($mem < (64 * 1024 * 1024)) {
			ini_set('memory_limit', '64M');
			$mem = $this->getPHPMemory();
			$testDetails[3] = ini_get("memory_limit");
		}
		
		$this->testing($testDetails);

		if($mem < $min && $mem > 0) {
			$testDetails[2] .= " You only have " . ini_get("memory_limit") . " allocated";
			$this->error($testDetails);
		} else if($mem < $recommended && $mem > 0) {
			$testDetails[2] .= " You only have " . ini_get("memory_limit") . " allocated";
			$this->warning($testDetails);
		} elseif($mem == 0) {
			$testDetails[2] .= " We can't determine how much memory you have allocated. Install only if you're sure you've allocated at least 20 MB.";
			$this->warning($testDetails);
		}
	}
	
	function getPHPMemory() {
		$memString = ini_get("memory_limit");

		switch(strtolower(substr($memString,-1))) {
			case "k":
				return round(substr($memString,0,-1)*1024);

			case "m":
				return round(substr($memString,0,-1)*1024*1024);
			
			case "g":
				return round(substr($memString,0,-1)*1024*1024*1024);
			
			default:
				return round($memString);
		}
	}
	
	function listErrors() {
		if($this->errors) {
			echo "<p>The following problems are preventing me from installing SilverStripe CMS:</p>\n\n";
			foreach($this->errors as $error) {
				echo "<li>" . htmlentities(implode(", ", $error)) . "</li>\n";
			}
		}
	}
	
	function showTable($section = null) {
		if($section) {
			$tests = $this->tests[$section];
			echo "<table class=\"testResults\" width=\"100%\">";
			foreach($tests as $test => $result) {
				echo "<tr class=\"$result[0]\"><td>$test</td><td>" . nl2br(htmlentities($result[1])) . "</td></tr>";
			}
			echo "</table>";
			
		} else {
			foreach($this->tests as $section => $tests) {
				echo "<h5>$section</h5>";
				echo "<table class=\"testResults\">";
				
				foreach($tests as $test => $result) {
					echo "<tr class=\"$result[0]\"><td>$test</td><td>" . nl2br(htmlentities($result[1])) . "</td></tr>";
				}
				echo "</table>";
			}		
		}
	}
	
	function showInstallStatus() {
		if($this->warnings) {
			echo "I have installed SilverStripe CMS, however, you should note the following:";
			foreach($this->warnings as $warning) {
				echo "<li>" . htmlentities($warning) . "</li>";
			}
		} else {
			if(isset($_SERVER['HTTP_HOST'])) {
				?>
				<p>I have installed SilverStripe CMS successfully!</p>
				<p><a href="./admin/" target="_blank">Open the CMS tool</a><br />
				<a href="./" target="_blank">Open the site</a></p>
				<?php
			} else {
				echo "\nSilverStripe successfully installed\n";
			}
		}
	}
	
	function requireFunction($funcName, $testDetails) {
		$this->testing($testDetails);
		if(!function_exists($funcName)) $this->error($testDetails);
		else return true;
	}
	
	/**
	 * Require that the given class doesn't exist
	 */
	function requireNoClasses($classNames, $testDetails) {
		$this->testing($testDetails);
		$badClasses = array();
		foreach($classNames as $className) {
			if(class_exists($className)) $badClasses[] = $className;
		}
		if($badClasses) {
			$testDetails[2] .= ".  The following classes are at fault: " . implode(', ', $badClasses);
			$this->error($testDetails);
		}
		else return true;
	}
		
	function requirePHPVersion($recommendedVersion, $requiredVersion, $testDetails) {
		$this->testing($testDetails);
		
		$installedVersion = phpversion();
		
		if(version_compare($installedVersion, $requiredVersion, '<')) {
			$testDetails[2] = "SilverStripe requires PHP version $requiredVersion or later.\n
				PHP version $installedVersion is currently installed.\n
				While SilverStripe requires at least PHP version $requiredVersion, upgrading to $recommendedVersion or later is recommended.\n
				If you are installing SilverStripe on a shared web server, please ask your web hosting provider to upgrade PHP for you.";
			$this->error($testDetails);
			return;
		}
		
		if(version_compare($installedVersion, $recommendedVersion, '<')) {
			$testDetails[2] = "PHP version $installedVersion is currently installed.\n
				Upgrading to at least PHP version $recommendedVersion is recommended.\n
				SilverStripe should run, but you may run into issues. Future releases may require a later version of PHP.\n";
			$this->warning($testDetails);
			return;
		}
		
		return true;
	}
	
	function requireFile($filename, $testDetails) {
		$this->testing($testDetails);
		$filename = $this->getBaseDir() . $filename;
		if(!file_exists($filename)) {
			$testDetails[2] .= " (file '$filename' not found)";
			$this->error($testDetails);
		}
	}
	function requireNoFile($filename, $testDetails) {
		$this->testing($testDetails);
		$filename = $this->getBaseDir() . $filename;
		if(file_exists($filename)) {
			$testDetails[2] .= " (file '$filename' found)";
			$this->error($testDetails);
		}
	}
	function moveFileOutOfTheWay($filename, $testDetails) {
		$this->testing($testDetails);
		$filename = $this->getBaseDir() . $filename;
		if(file_exists($filename)) {
			if(file_exists("$filename.bak")) rm("$filename.bak");
			rename($filename, "$filename.bak");
		}
	}
	
	function requireWriteable($filename, $testDetails) {
		$this->testing($testDetails);
		$filename = $this->getBaseDir() . str_replace("/", DIRECTORY_SEPARATOR,$filename);
		
		if(!is_writeable($filename)) {
			if(function_exists('posix_getgroups')) {
				$userID = posix_geteuid();
				$user = posix_getpwuid($userID);

				$currentOwnerID = fileowner($filename);
				$currentOwner = posix_getpwuid($currentOwnerID);

				$testDetails[2] .= "User '$user[name]' needs to be able to write to this file:\n$filename\n\nThe file is currently owned by '$currentOwner[name]'.  ";

				if($user['name'] == $currentOwner['name']) {
					$testDetails[2] .= "We recommend that you make the file writeable.";
				} else {
					
					$groups = posix_getgroups();
					foreach($groups as $group) {
						$groupInfo = posix_getgrgid($group);
						if(in_array($currentOwner['name'], $groupInfo['members'])) $groupList[] = $groupInfo['name'];
					}
					if($groupList) {
						$testDetails[2] .= "	We recommend that you make the file group-writeable and change the group to one of these groups:\n - ". implode("\n - ", $groupList)
							. "\n\nFor example:\nchmod g+w $filename\nchgrp " . $groupList[0] . " $filename";  		
					} else {
						$testDetails[2] .= "  There is no user-group that contains both the web-server user and the owner of this file.  Change the ownership of the file, create a new group, or temporarily make the file writeable by everyone during the install process.";
					}
				}

			} else {
				$testDetails[2] .= "The webserver user needs to be able to write to this file:\n$filename";
			}
			
			$this->error($testDetails);
		}
	}
	
	function requireTempFolder($testDetails) {
		$this->testing($testDetails);
		
		if(function_exists('sys_get_temp_dir')) {
	        $sysTmp = sys_get_temp_dir();
	    } elseif(isset($_ENV['TMP'])) {
			$sysTmp = $_ENV['TMP'];    	
	    } else {
	        @$tmpFile = tempnam('adfadsfdas','');
	        @unlink($tmpFile);
	        $sysTmp = dirname($tmpFile);
	    }
	    
	    $worked = true;
	    $ssTmp = "$sysTmp/silverstripe-cache";
	    
	    if(!@file_exists($ssTmp)) {
	    	@$worked = mkdir($ssTmp);
	    	
	    	if(!$worked) {
		    	$ssTmp = dirname($_SERVER['SCRIPT_FILENAME']) . "/silverstripe-cache";
		    	$worked = true;
		    	if(!@file_exists($ssTmp)) {
		    		@$worked = mkdir($ssTmp);
		    	}
		    	if(!$worked) {
		    		$testDetails[2] = "Permission problem gaining access to a temp folder. " .
		    			"Please create a folder named silverstripe-cache in the base folder "  .
		    			"of the installation and ensure it has the adequate permissions";
		    		$this->error($testDetails);
		    	}
		    }
		}
	}
	
	function requireApacheModule($moduleName, $testDetails) {
		$this->testing($testDetails);
		if(!in_array($moduleName, apache_get_modules())) $this->error($testDetails);
	}

	function requireIISRewriteModule($moduleName, $testDetails) {
		$this->testing($testDetails);
		if(isset($_SERVER[$moduleName]) && $_SERVER[$moduleName]) {
			return true;
		} else {
			$this->error($testDetails);
			return false;
		}
	}
	
	function requireMysqlConnection($server, $username, $password, $testDetails) {
		$this->testing($testDetails);
		$conn = @mysql_connect($server, $username, $password);
		
		if($conn) {
			return true;
			/*
			if(mysql_query("CREATE DATABASE testing123")) {
				mysql_query("DROP DATABASE testing123");
				return true;
			} else {
				$testDetails[2] .= " (user '$username' doesn't have CREATE DATABASE permissions.)";
				$this->error($testDetails);
			}
			*/
		} else {
			$testDetails[2] .= ": " . mysql_error();
			$this->error($testDetails);
		}
	}
	
	function requireMySQLServer($server, $testDetails) {
		$this->testing($testDetails);
		$conn = @mysql_connect($server, null, null);

		if($conn || mysql_errno() < 2000) {
			return true;
		} else {
			$testDetails[2] .= ": " . mysql_error();
			$this->error($testDetails);
		}
	}
	
	function requireMySQLVersion($version, $testDetails) {
		$this->testing($testDetails);
		
		if(!mysql_get_server_info()) {
			$testDetails[2] = 'Cannot determine the version of MySQL installed. Please ensure at least version 4.1 is installed.';
			$this->warning($testDetails);
		} else {
			list($majorRequested, $minorRequested) = explode('.', $version);
			$result = mysql_query('SELECT VERSION()');
			$row=mysql_fetch_row($result);
			$version = ereg_replace("([A-Za-z-])", "", $row[0]);
			list($majorHas, $minorHas) = explode('.', substr(trim($version), 0, 3));
						
			if(($majorHas > $majorRequested) || ($majorHas == $majorRequested && $minorHas >= $minorRequested)) {
				return true;
			} else {
				$testDetails[2] .= "{$majorHas}.{$minorHas}.";
				$this->error($testDetails);
			}
		}
	}

	
	function requireDatabaseOrCreatePermissions($server, $username, $password, $database, $testDetails) {
		$this->testing($testDetails);
		$conn = @mysql_connect($server, $username, $password);
		
		if(@mysql_select_db($database)) {
			$okay = "Database '$database' exists";
			
		} else {
			if(@mysql_query("CREATE DATABASE testing123")) {
				mysql_query("DROP DATABASE testing123");
				$okay = "Able to create a new database";

			} else {
				$testDetails[2] .= " (user '$username' doesn't have CREATE DATABASE permissions.)";
				$this->error($testDetails);
				return;
			}
		}
		
		if($okay) {
			$testDetails[3] = $okay;
			$this->testing($testDetails);
		}

	}
	
	function requireServerVariables($varNames, $errorMessage) {
		//$this->testing($testDetails);
		foreach($varNames as $varName) {
			if(!$_SERVER[$varName]) $missing[] = '$_SERVER[' . $varName . ']';
		}
		if(!isset($missing)) {
			return true;
		} else {
			$testDetails[2] .= " (the following PHP variables are missing: " . implode(", ", $missing) . ")";
			$this->error($testDetails);
		}
	}
	
	function isRunningWebServer($testDetails) {
		$this->testing($testDetails);
		if(function_exists('apache_get_modules') || stristr($_SERVER['SERVER_SIGNATURE'], 'Apache')) {
			return true;
		} elseif(strpos($_SERVER['SERVER_SOFTWARE'], 'IIS') !== false) {
			return true;
		} else {
			$this->warning($testDetails);
			return false;
		}
	}


	// Must be PHP4 compatible
	var $baseDir;
	function getBaseDir() {
		// Cache the value so that when the installer mucks with SCRIPT_FILENAME half way through, this method
		// still returns the correct value.
		if(!$this->baseDir) $this->baseDir = realpath(dirname($_SERVER['SCRIPT_FILENAME'])) . DIRECTORY_SEPARATOR;
		return $this->baseDir;
	}
	
	function testing($testDetails) {
		if(!$testDetails) return;
		
		$section = $testDetails[0];
		$test = $testDetails[1];
		
		$message = "OK";
		if(isset($testDetails[3])) $message .= " ($testDetails[3])";

		$this->tests[$section][$test] = array("good", $message);
	}
	
	function error($testDetails) {
		$section = $testDetails[0];
		$test = $testDetails[1];

		$this->tests[$section][$test] = array("error", $testDetails[2]);
		$this->errors[] = $testDetails;

	}
	function warning($testDetails) {
		$section = $testDetails[0];
		$test = $testDetails[1];


		$this->tests[$section][$test] = array("warning", $testDetails[2]);
		$this->warnings[] = $testDetails;
	}
	
	function hasErrors() {
		return sizeof($this->errors);
	}
	function hasWarnings() {
		return sizeof($this->warnings);
	}
	
}

class Installer extends InstallRequirements {
	function __construct() {
		// Cache the baseDir value
		$this->getBaseDir();
	}
	
	function install($config) {
		if(isset($_SERVER['HTTP_HOST'])) {
			?>
<html>
	<head>
		<title>PHP 5 is required</title>
		<link rel="stylesheet" type="text/css" href="themes/blackcandy/css/layout.css" />
		<link rel="stylesheet" type="text/css" href="themes/blackcandy/css/typography.css" />
		<link rel="stylesheet" type="text/css" href="themes/blackcandy/css/form.css" />
		<link rel="stylesheet" type="text/css" href="sapphire/dev/install/install.css" />
		<script src="sapphire/thirdparty/jquery/jquery.js"></script>
	</head>
	<body>
		<div id="BgContainer">
			<div id="Container">
				<div id="Header">
					<h1>SilverStripe CMS Installation</h1>
				</div>

				<div id="Navigation">&nbsp;</div>
				<div class="clear"><!-- --></div>

				<div id="Layout">
					<div class="typography">
						<h1>Installing SilverStripe...</h1>
						<p>I am now running through the installation steps (this should take about 30 seconds)</p>
						<p>If you receive a fatal error, refresh this page to continue the installation</p>
						<ul>
<?php
		} else {
			echo "SILVERSTRIPE COMMAND-LINE INSTALLATION\n\n";
		}
		
		flush();
		
		if(isset($_POST['stats'])) {
			if(file_exists('sapphire/silverstripe_version')) {
				$sapphireVersionFile = file_get_contents('sapphire/silverstripe_version');
				if(strstr($sapphireVersionFile, "/sapphire/trunk")) {
					$silverstripe_version = "trunk";
				} else {
					preg_match("/sapphire\/(?:(?:branches)|(?:tags))(?:\/rc)?\/([A-Za-z0-9._-]+)\/silverstripe_version/", $sapphireVersionFile, $matches);
					$silverstripe_version = $matches[1];
				}
			} else {
				$silverstripe_version = "unknown";
			}
			
			$phpVersion = urlencode(phpversion());
			$conn = @mysql_connect($config['mysql']['server'], null, null);
			$databaseVersion = urlencode('MySQL ' . mysql_get_server_info());
			$webserver = urlencode($_SERVER['SERVER_SOFTWARE']);
			
			$url = "http://ss2stat.silverstripe.com/Installation/add?SilverStripe=$silverstripe_version&PHP=$phpVersion&Database=$databaseVersion&WebServer=$webserver";
			
			if(isset($_SESSION['StatsID']) && $_SESSION['StatsID']) {
				$url .= '&ID=' . $_SESSION['StatsID'];
			}
			
			@$_SESSION['StatsID'] = file_get_contents($url);
		}
		
		if(file_exists('mysite/_config.php')) {
			unlink('mysite/_config.php');
		}
		$theme = isset($_POST['template']) ? $_POST['template'] : 'blackcandy';
		// Write the config file
		global $usingEnv;
		if($usingEnv) {
			$this->statusMessage("Creating 'mysite/_config.php' for use with _ss_environment.php...");
			$this->createFile("mysite/_config.php", <<<PHP
<?php

global \$project;
\$project = 'mysite';

global \$database;
\$database = "{$config['mysql']['database']}";

require_once("conf/ConfigureFromEnv.php");

MySQLDatabase::set_connection_charset('utf8');

// This line set's the current theme. More themes can be
// downloaded from http://www.silverstripe.org/themes/
SSViewer::set_theme('$theme');

// enable nested URLs for this site (e.g. page/sub-page/)
SiteTree::enable_nested_urls();
?>
PHP
			);

			
		} else {
			$this->statusMessage("Creating 'mysite/_config.php'...");
		
			$devServers = $this->var_export_array_nokeys(explode("\n", $_POST['devsites']));
		
			$escapedPassword = addslashes($config['mysql']['password']);
			$this->createFile("mysite/_config.php", <<<PHP
<?php

global \$project;
\$project = 'mysite';

global \$databaseConfig;
\$databaseConfig = array(
	"type" => "$config[database]",
	"server" => "{$config['mysql']['server']}", 
	"username" => "{$config['mysql']['username']}", 
	"password" => '{$escapedPassword}', 
	"database" => "{$config['mysql']['database']}",
);

// Sites running on the following servers will be
// run in development mode. See
// http://doc.silverstripe.org/doku.php?id=configuration
// for a description of what dev mode does.
Director::set_dev_servers($devServers);

// This line set's the current theme. More themes can be
// downloaded from http://www.silverstripe.org/themes/
SSViewer::set_theme('$theme');

// enable nested URLs for this site (e.g. page/sub-page/)
SiteTree::enable_nested_urls();
?>
PHP
			);
		}

		$this->statusMessage("Creating '.htaccess' file...");
		
		$this->createHtaccess();

		// Load the sapphire runtime
		$_SERVER['SCRIPT_FILENAME'] = dirname(realpath($_SERVER['SCRIPT_FILENAME'])) . '/sapphire/main.php';
		chdir('sapphire');

		// Rebuild the manifest
		$_GET['flush'] = true;
		// Show errors as if you're in development mode 
		$_SESSION['isDev'] = 1;
		
		require_once('core/Core.php');
	
		$this->statusMessage("Building database schema...");

		// Build database
		$con = new Controller();
		$con->pushCurrent();

		global $databaseConfig;
		DB::connect($databaseConfig);
		
		$dbAdmin = new DatabaseAdmin();
		$dbAdmin->init();
		
		$dbAdmin->doBuild(true);
		
		// Create default administrator user and group in database 
		// (not using Security::setDefaultAdmin())
		$adminMember = Security::findAnAdministrator();
		$adminMember->Email = $config['admin']['username'];
		$adminMember->Password = $config['admin']['password'];
		$adminMember->PasswordEncryption = Security::get_password_encryption_algorithm();
		$adminMember->FirstName = $config['admin']['firstname'];
		$adminMember->Surname = $config['admin']['surname'];
		$adminMember->write();
		
		// Syncing filesystem (so /assets/Uploads is available instantly, see ticket #2266)
		FileSystem::sync();
		
		$_SESSION['username'] = $config['admin']['username'];
		$_SESSION['password'] = $config['admin']['password'];

		if(!$this->errors) {
			if(isset($_SERVER['HTTP_HOST'])) {
				$this->statusMessage("Checking that friendly URLs work...");
				$this->checkModRewrite();
			} else {
				echo "\n\nSilverStripe successfully installed\n";
			}
		}
		
		return $this->errors;
	}
	
	function makeFolder($folder) {
		$base = $this->getBaseDir();
		if(!file_exists($base . $folder)) {
			if(!mkdir($base . $folder, 02775)) {
				$this->error("Couldn't create a folder called $base$folder");
			} else {
				chmod($base . $folder, 02775);
			}
		} 
	}
	
	function renameFolder($oldName, $newName) {
		if($oldName == $newName) return true;
		
		$base = $this->getBaseDir();
		if(!rename($base . $oldName, $base . $newName)) {
			$this->error("Couldn't rename $base$oldName to $base$newName");
			return false;
		} else {
			return true;
		}
	}

	function copyFolder($oldName, $newName) {
		if($oldName == $newName) return true;
		
		$base = $this->getBaseDir();
		if(!copyr($base . $oldName, $base . $newName)) {
			$this->error("Couldn't rename $base$oldName to $base$newName");
			return false;
		} else {
			return true;
		}
	}
	
	function createFile($filename, $content) {
		$base = $this->getBaseDir();
		$this->statusMessage("Creating $base$filename");

		if((@$fh = fopen($base . $filename, 'wb')) && fwrite($fh, $content) && fclose($fh)) {
			return true;
		} else {
			$this->error("Couldn't write to file $base$filename");
		}
	}
	
	function createHtaccess() {
		$start = "### SILVERSTRIPE START ###\n";
		$end = "\n### SILVERSTRIPE END ###";
		
		$base = dirname($_SERVER['SCRIPT_NAME']);
		if(defined('DIRECTORY_SEPARATOR')) $base = str_replace(DIRECTORY_SEPARATOR, '/', $base);
		else $base = str_replace("\\", '/', $base);
		
		if($base != '.') $baseClause = "RewriteBase $base\n";
		else $baseClause = "";
		
		$rewrite = <<<TEXT
<Files *.ss>
Order deny,allow
Deny from all
Allow from 127.0.0.1
</Files>

RewriteEngine On
$baseClause

RewriteCond %{REQUEST_URI} ^(.*)$
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule .* sapphire/main.php?url=%1&%{QUERY_STRING} [L]
TEXT
		;
		
		if(file_exists('.htaccess')) {
			$htaccess = file_get_contents('.htaccess');
			
			if(strpos($htaccess, '### SILVERSTRIPE START ###') === false && strpos($htaccess, '### SILVERSTRIPE END ###') === false) {
				$htaccess .= "\n### SILVERSTRIPE START ###\n### SILVERSTRIPE END ###\n";
			}
		
			if(strpos($htaccess, '### SILVERSTRIPE START ###') !== false && strpos($htaccess, '### SILVERSTRIPE END ###') !== false) {
				$start = substr($htaccess, 0, strpos($htaccess, '### SILVERSTRIPE START ###')) . "### SILVERSTRIPE START ###\n";
				$end = "\n" . substr($htaccess, strpos($htaccess, '### SILVERSTRIPE END ###'));
			}
		}
		
		$this->createFile('.htaccess', $start . $rewrite . $end);
	}
	
	function restoreHtaccess() {
		$start = "### SILVERSTRIPE START ###\n";
		$end= "\n### SILVERSTRIPE END ###";
		
		if(file_exists('.htaccess')) {
			$htaccess = file_get_contents('.htaccess');
			
			if(strpos($htaccess, '### SILVERSTRIPE START ###') === false && strpos($htaccess, '### SILVERSTRIPE END ###') === false) {
				$htaccess .= "\n### SILVERSTRIPE START ###\n### SILVERSTRIPE END ###\n";
			}
		
			if(strpos($htaccess, '### SILVERSTRIPE START ###') !== false && strpos($htaccess, '### SILVERSTRIPE END ###') !== false) {
				$start = substr($htaccess, 0, strpos($htaccess, '### SILVERSTRIPE START ###')) . "### SILVERSTRIPE START ###\n";
				$end = "\n" . substr($htaccess, strpos($htaccess, '### SILVERSTRIPE END ###'));
			}
		}
		
		$this->createFile('.htaccess', $start . $end);
	}
	
	function checkModRewrite() {
		if(!isset($_SERVER['HTTP_HOST']) || !$_SERVER['HTTP_HOST']) {
			$this->statusMessage("Installer seems to be called from command-line, we're going to assume that rewriting is working.");
			return true;
		}

		echo <<<HTML
<li id="ModRewriteResult">Testing...</li>
<script>
	if(typeof $ == 'undefined') {
		document.getElemenyById('ModeRewriteResult').innerHTML = "I can't run jQuery ajax to set rewriting; I will redirect you to the homepage to see if everything is working.";
		setTimeout(function() {
			window.location = "home/successfullyinstalled?flush=1";
		}, 10000);
	} else {
		$.ajax({
			method: 'get',
			url: 'InstallerTest/testrewrite',
			complete: function(response) {
				if(response.responseText == 'OK') {
					$('#ModRewriteResult').html("Friendly URLs set up successfully; I am now redirecting you to your SilverStripe site...")
					setTimeout(function() {
						window.location = "home/successfullyinstalled?flush=1";
					}, 2000);
				} else {
					$('#ModRewriteResult').html("Friendly URLs are not working.  This is most likely because mod_rewrite isn't configured"
						+ "correctly on your site.  Please check the following things in your Apache configuration; "
						+ " you may need to get your web host or server administrator to do this for you:"
						+ "<ul><li>mod_rewrite is enabled</li><li>AllowOverride All is set for your directory</li></ul>");
				}
			}
		});
	}
</script>
<noscript>
<li><a href="home/successfullyinstalled?flush=1">Click here to check friendly URLs are working.  If you get a 404 then something is wrong.</li>
</noscript>
HTML;
	}
	
	function var_export_array_nokeys($array) {
		$retval = "array(\n";
		foreach($array as $item) {
			$retval .= "\t'";
			$retval .= trim($item);
			$retval .= "',\n";
		}
		$retval .= ")";
		return $retval;
	}
	
	/**
	 * Show an installation status message.
	 * The output differs depending on whether this is CLI or web based
	 */
	function statusMessage($msg) {
		if(isset($_SERVER['HTTP_HOST'])) echo "<li>$msg</li>\n";
		else echo "$msg\n";
		flush();
	}
}

/**
 * Copy a file, or recursively copy a folder and its contents
 *
 * @author      Aidan Lister <aidan@php.net>
 * @version     1.0.1
 * @link        http://aidanlister.com/repos/v/function.copyr.php
 * @param       string   $source    Source path
 * @param       string   $dest      Destination path
 * @return      bool     Returns TRUE on success, FALSE on failure
 */
function copyr($source, $dest)
{
    // Simple copy for a file
    if (is_file($source)) {
        return copy($source, $dest);
    }

    // Make destination directory
    if (!is_dir($dest)) {
        mkdir($dest);
    }

    // Loop through the folder
    $dir = dir($source);
    while (false !== $entry = $dir->read()) {
        // Skip pointers
        if ($entry == '.' || $entry == '..') {
            continue;
        }

        // Deep copy directories
        if ($dest !== "$source/$entry") {
            copyr("$source/$entry", "$dest/$entry");
        }
    }

    // Clean up
    $dir->close();
    return true;
}

function rm($fileglob)
{
   if (is_string($fileglob)) {
       if (is_file($fileglob)) {
           return unlink($fileglob);
       } else if (is_dir($fileglob)) {
           $ok = rm("$fileglob/*");
           if (! $ok) {
               return false;
           }
           return rmdir($fileglob);
       } else {
           $matching = glob($fileglob);
           if ($matching === false) {
               trigger_error(sprintf('No files match supplied glob %s', $fileglob), E_USER_WARNING);
               return false;
           }     
           $rcs = array_map('rm', $matching);
           if (in_array(false, $rcs)) {
               return false;
           }
       }     
   } else if (is_array($fileglob)) {
       $rcs = array_map('rm', $fileglob);
       if (in_array(false, $rcs)) {
           return false;
       }
   } else {
       trigger_error('Param #1 must be filename or glob pattern, or array of filenames or glob patterns', E_USER_ERROR);
       return false;
   }

   return true;
}

?>
