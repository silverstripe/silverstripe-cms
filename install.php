<?php

/**
 * SilverStripe CMS Installer
 * This installer doesn't use any of the fancy Sapphire stuff in case it's unsupported.
 */

ini_set('max_execution_time', 300);
error_reporting(E_ALL ^ E_NOTICE);
session_start();

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
		'password' => 'password',
		'firstname' => '',
		'surname' => ''
	);
}

$alreadyInstalled = (file_exists('mysite/_config.php') || file_exists('tutorial/_config.php'));

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

if(isset($_REQUEST['go']) || $installFromCli && !$req->hasErrors() && !$dbReq->hasErrors()) {
	// Confirm before reinstalling
	if(!isset($_REQUEST['force_reinstall']) && !$installFromCli && $alreadyInstalled) {
		include('config-form.html');
		
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
	include('config-form.html');	
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
			if($this->requireFunction('mysql_connect', array("PHP Configuration", "MySQL support", "MySQL support not included in PHP."))) {
				$this->requireMySQLServer($databaseConfig['server'], array("MySQL Configuration", "Does the server exist", 
					"Can't find the a MySQL server on '$databaseConfig[server]'", $databaseConfig['server']));
				if($this->requireMysqlConnection($databaseConfig['server'], $databaseConfig['username'], $databaseConfig['password'], 
					array("MySQL Configuration", "Are the access credentials correct", "That username/password doesn't work"))) {
					@$this->requireMySQLVersion("4.1", array("MySQL Configuration", "MySQL version at least 4.1", "MySQL version 4.1 is required, you only have ", "MySQL " . mysql_get_server_info()));
					}
				$this->requireDatabaseOrCreatePermissions($databaseConfig['server'], $databaseConfig['username'], $databaseConfig['password'], $databaseConfig['database'], 
					array("MySQL Configuration", "Can I access/create the database", "I can't create new databases and the database '$databaseConfig[database]' doesn't exist"));
			}
	}
	
	
	/**
	 * Check everything except the database
	 */
	function check() {
		$this->errors = null;
		
		$this->requirePHPVersion('5.2.0', '5.0.4', array("PHP Configuration", "PHP5 installed", null, "PHP version " . phpversion()));

		// Check that we can identify the root folder successfully
		$this->requireFile('config-form.html', array("File permissions", 
			"Does the webserver know where files are stored?", 
			"The webserver isn't letting me identify where files are stored.",
			$this->getBaseDir()
			));		
		$this->requireFile('mysite', array("File permissions", "mysite/ folder exists", "There's no mysite folder."));
		$this->requireFile('sapphire', array("File permissions", "sapphire/ folder exists", "There's no sapphire folder."));
		$this->requireFile('cms', array("File permissions", "cms/ folder exists", "There's no cms folder."));
		$this->requireFile('jsparty', array("File permissions", "jsparty/ folder exists", "There's no jsparty folder."));
		$this->requireWriteable('.htaccess', array("File permissions", "Is the .htaccess file writeable?", null));
		$this->requireWriteable('mysite', array("File permissions", "Is the mysite/ folder writeable?", null));
		$this->requireWriteable('assets', array("File permissions", "Is the assets/ folder writeable?", null));
		
		$this->requireTempFolder(array('File permissions', 'Is the temporary folder writeable?', null));
		
		// Check for web server, unless we're calling the installer from the command-line
		if(!isset($_SERVER['argv']) || !$_SERVER['argv']) { 
			$webserver = strip_tags(trim($_SERVER['SERVER_SIGNATURE']));
			if($webserver == '') {
				$webserver = "I can't tell what webserver you are running";
			}
		
			$this->isRunningApache(array("Webserver Configuration", "Server software", "$webserver.  Without Apache I can't tell if mod_rewrite is enabled.", $webserver));
			if(function_exists('apache_get_modules')) {
				$this->requireApacheModule('mod_rewrite', array("Webserver Configuration", "mod_rewrite enabled", "You need mod_rewrite to run SilverStripe CMS, but it is not enabled."));
			} else {
				$this->warning(array("Webserver Configuration", "mod_rewrite enabled", "I can't tell whether mod_rewrite is running.  You may need to configure a rewriting rule yourself."));
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
		
		// Check memory allocation
		$this->requireMemory(20*1024*1024, 32*1024*1024, array("PHP Configuration", "Memory allocated (PHP config option 'memory_limit')", "SilverStripe needs a minimum of 20M allocated to PHP, but recommends 32M.", ini_get("memory_limit")));
		
		// Check allow_call_time_pass_reference
		$this->suggestPHPSetting('allow_call_time_pass_reference', array(1,'1','on','On'), array("PHP Configuration", "Check that the php.ini setting allow_call_time_pass_reference is on",
			"You can install with allow_call_time_pass_reference not set, but some warnings may get displayed.  For best results, turn it on."));
	
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
		if($mem < (32 * 1024 * 1024)) {
			ini_set('memory_limit', '32M');
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
	
	function requirePHPVersion($recommendedVersion, $requiredVersion, $testDetails) {
		$this->testing($testDetails);
		
		list($recA, $recB, $recC) = explode('.', $recommendedVersion);
		list($reqA, $reqB, $reqC) = explode('.', $requiredVersion);
		list($a, $b, $c) = explode('.', phpversion());
		$c = ereg_replace('-.*$','',$c);
		
		if($a > $recA || ($a == $recA && $b > $recB) || ($a == $reqA && $b == $reqB && $c >= $reqC)) {
			$testDetails[2] = "SilverStripe recommends PHP version $recommendedVersion or later, only $a.$b.$c is installed. While SilverStripe should run, you may run into issues, and future versions of SilverStripe may require a later version. Upgrading PHP is recommended.";
			$this->warning($testDetails);
			return;
		}
		
		if($a > $reqA) return true;
		if($a == $reqA && $b > $reqB) return true;
		if($a == $reqA && $b == $reqB && $c >= $reqC) return true;

		if(!$testDetails[2]) {
			if($a < $reqA) {
				$testDetails[2] = "You need PHP version $version or later, only $a.$b.$c is installed.  Unfortunately PHP$a and PHP$reqA have some incompatabilities, so if you are on a your web-host may need to move you to a different server.   Some software doesn't work with PHP5 and so upgrading a shared server could be problematic.";
			} else {
				$testDetails[2] = "You need PHP version $requiredVersion or later, only $a.$b.$c is installed.  Please upgrade your server, or ask your web-host to do so.";
			}
		}
	
		$this->error($testDetails);
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
		$filename = $this->getBaseDir() . $filename;
		
		if(function_exists('posix_getgroups')) {
			if(!is_writeable($filename)) {
				$user = posix_getpwuid(posix_geteuid());
				$groups = posix_getgroups();
				foreach($groups as $group) {
					$groupInfo = posix_getgrgid($group);
					$groupList[] = $groupInfo['name'];
				}
				$groupList = "'" . implode("', '", $groupList) . "'";
				
				$testDetails[2] .= "User '$user[name]' needs to be able to write to this file:\n$filename";
				$this->error($testDetails);
			}
		} else {
			$testDetails[2] .= "Unable to detect whether I can write to files. Please ensure $filename is writable.";
			$this->warning($testDetails);
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
	
	function isRunningApache($testDetails) {
		$this->testing($testDetails);
		if(function_exists('apache_get_modules') || stristr($_SERVER['SERVER_SIGNATURE'], 'Apache'))
			return true;
		
		$this->warning($testDetails);
		return false;
	}


	protected $baseDir;
	function getBaseDir() {
		// Cache the value so that when the installer mucks with SCRIPT_FILENAME half way through, this method
		// still returns the correct value.
		if(!$this->baseDir) $this->baseDir = realpath(dirname($_SERVER['SCRIPT_FILENAME'])) . '/';
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
<h1>Installing SilverStripe...</h1>
<p>I am now running through the installation steps (this should take about 30 seconds)</p>
<p>If you receive a fatal error, refresh this page to continue the installation
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

// This line set's the current theme. More themes can be
// downloaded from http://www.silverstripe.com/themes/
SSViewer::set_theme('$theme');

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
	"password" => "{$escapedPassword}", 
	"database" => "{$config['mysql']['database']}",
);

// Sites running on the following servers will be
// run in development mode. See
// http://doc.silverstripe.com/doku.php?id=devmode
// for a description of what dev mode does.
Director::set_dev_servers($devServers);

// This line set's the current theme. More themes can be
// downloaded from http://www.silverstripe.com/themes/
SSViewer::set_theme('$theme');

?>
PHP
			);
		}

		$this->statusMessage("Creating '.htaccess' file...");
		
		$this->createHtaccess();

		// Load the sapphire runtime
		$_SERVER['SCRIPT_FILENAME'] = dirname(realpath($_SERVER['SCRIPT_FILENAME'])) . '/sapphire/main.php';
		chdir('sapphire');

		require_once('core/Core.php');
	
		$this->statusMessage("Building database schema...");

		// Build database
		$_GET['flush'] = true;
		$con = new Controller();
		$con->pushCurrent();

		global $databaseConfig;
		DB::connect($databaseConfig);
		
		$dbAdmin = new DatabaseAdmin();
		$dbAdmin->init();
		
		$_REQUEST['username'] = $config['admin']['username'];
		$_REQUEST['password'] = $config['admin']['password'];
		$dbAdmin->doBuild(true);
		
		$adminmember = DataObject::get_one('Member',"`Email`= '".$_REQUEST['admin']['username']."'");
		if($adminmember) {
			$adminmember->FirstName = $_REQUEST['admin']['firstname'];
			$adminmember->Surname = $_REQUEST['admin']['surname'];
			$adminmember->write();
		}
		
		// Syncing filesystem (so /assets/Uploads is available instantly, see ticket #2266)
		FileSystem::sync();

		if(isset($_SERVER['HTTP_HOST'])) {
			$this->statusMessage("Checking mod_rewrite works...");
			$modRewriteWorks = $this->checkModRewrite();
		} else {
			$modRewriteWorks = true;
		}
		
		$_SESSION['username'] = $config['admin']['username'];
		$_SESSION['password'] = $config['admin']['password'];

		if($modRewriteWorks && !$this->errors) {
			if(isset($_SERVER['HTTP_HOST'])) {
				echo "<p>Installed SilverStripe successfully.  I will now try and direct you to 
					<a href=\"home/successfullyinstalled?flush=1\">home/successfullyinstalled</a> to confirm that the installation was successful.</p>
					<script>setTimeout(function() { window.location.href = 'home/successfullyinstalled?flush=1'; }, 1000);</script>
					";
			} else {
				echo "\nSilverStripe successfully installed\n";
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

		if((@$fh = fopen($base . $filename, 'w')) && fwrite($fh, $content) && fclose($fh)) {
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
<IfModule mod_dir.c>
DirectorySlash Off
</IfModule>

RewriteEngine On
$baseClause
RewriteCond %{REQUEST_URI} !(\.gif)|(\.jpg)|(\.png)|(\.css)|(\.js)|(\.php)$ 

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

	function createHtaccessAlternative() {
		$start = "### SILVERSTRIPE START ###\n";
		$end= "\n### SILVERSTRIPE END ###";
		
		$base = dirname($_SERVER['SCRIPT_NAME']);
		if($base != '.') $baseClause = "RewriteBase $base\n";
		
		$rewrite = <<<TEXT
<IfModule mod_dir.c>
DirectorySlash Off
</IfModule>

RewriteEngine On
$baseClause
RewriteCond %{REQUEST_URI} !(\.gif)|(\.jpg)|(\.png)|(\.css)|(\.js)|(\.php)$ 

RewriteCond %{REQUEST_URI} ^(.*)$
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule .* $_SERVER[DOCUMENT_ROOT]/sapphire/main.php?url=%1&%{QUERY_STRING} [L]
TEXT;

		if(file_exists($this->getBaseDir() . '.htaccess')) {
			$htaccess = file_get_contents($this->getBaseDir() . '.htaccess');
			
			if(strpos($htaccess, '### SILVERSTRIPE START ###') === false && strpos($htaccess, '### SILVERSTRIPE END ###') === false) {
				$htaccess .= "\n### SILVERSTRIPE START ###\n### SILVERSTRIPE END ###\n";
			}
		
			if(strpos($htaccess, '### SILVERSTRIPE START ###') !== false && strpos($htaccess, '### SILVERSTRIPE END ###') !== false) {
				$start = substr($htaccess, 0, strpos($htaccess, '### SILVERSTRIPE START ###')) . "### SILVERSTRIPE START ###\n";
				$end = "\n" . substr($htaccess, strpos($htaccess, '### SILVERSTRIPE END ###'));
			}
		}
		
		echo "\n\nRewrite is $rewrite\n";
		
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
		if($this->performModRewriteTest() == true) {
			return true;
		}
		
		$this->createHtaccessAlternative();
		
		if($this->performModRewriteTest() == false) {
			echo "<li>ERROR: mod_rewrite not working, redirecting to mod_rewrite test page</li>";
			
			$this->restoreHtaccess();
			
			echo "I will now try and direct you to <a href=\"rewritetest.php\">rewritetest</a> to troubleshoot mod_rewrite</p>
				<script>setTimeout(function() { window.location.href = 'rewritetest.php'; }, 1000);</script>
				";
			return false;
		}
		return true;
	}
	
	function performModRewriteTest() {
		if(!isset($_SERVER['HTTP_HOST']) || !$_SERVER['HTTP_HOST']) {
			$this->statusMessage("Installer seems to be called from command-line, we're going to assume that rewriting is working.");
			return true;
		}
		
		$baseURL = dirname($_SERVER['SCRIPT_NAME']);
		if($baseURL == "/") {
			$baseURL = "";
		}

		// Check if mod_rewrite works properly
		$location = 'http://' . (isset($_SERVER['PHP_AUTH_USER']) ? "$_SERVER[PHP_AUTH_USER]:$_SERVER[PHP_AUTH_PW]@" : '') . $_SERVER['HTTP_HOST'] . $baseURL . '/InstallerTest/testrewrite';
		echo $location;
		@$testrewriting = file_get_contents($location);
	
		if($testrewriting == 'OK') {
			return true;
		}
		
		// Workaround for 'URL file-access is disabled in the server configuration' using curl
		if(function_exists('curl_init')) {
			$ch = curl_init($location);
			$fp = @fopen(dirname(tempnam('adfadsfdas','')) . '/rewritetest', "w");
			
			if($fp) {
				curl_setopt($ch, CURLOPT_FILE, $fp);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_exec($ch);
				curl_close($ch);
				fclose($fp);
				$testrewriting = file_get_contents(dirname(tempnam('adfadsfdas','')) . '/rewritetest');
				unlink(dirname(tempnam('adfadsfdas','')) . '/rewritetest');
				if($testrewriting == 'OK') {
					return true;
				}
			}
		}
		
		return false;
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
