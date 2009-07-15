<?php

createHtaccess();

$baseURL = dirname($_SERVER['SCRIPT_NAME']);
if($baseURL == "/") {
	$baseURL = "";
}

if(isset($_REQUEST['force'])) {
	echo "Forced continue, attempting to redirect to <a href=\"home/successfullyinstalled?flush=1\">home/successfullyinstalled</a>.
		<script>setTimeout(function() { window.location.href = 'home/successfullyinstalled?flush=1'; }, 1000);</script>";
} else {	
	$modRewriteWorking  = performModRewriteTest();
	
	if(!$modRewriteWorking) {
		createHtaccessAlternative();
		$modRewriteWorking  = performModRewriteTest();
	}
	
	if($modRewriteWorking) {
		echo "mod_rewrite is working! I will now try and direct you to
					<a href=\"home/successfullyinstalled?flush=1\">home/successfullyinstalled</a> to confirm that the installation was successful.
					<script>setTimeout(function() { window.location.href = 'home/successfullyinstalled?flush=1'; }, 1000);</script>
		";
	} else {
		restoreHtaccess();
		
		echo "mod_rewrite doesn't appear to be working. Make sure:" .
				"<ul>" .
				"<li>mod_rewrite is enabled in your httpd.conf</li>" .
				"<li>AllowOverride is enabled for the current path.</li>" .
				"</ul>" .
				"Please check these options, then refresh this page." .
				"If you believe that your configuration is correct, <a href=\"rewritetest.php?force=1\">click here to proceed anyway.</a>";
	}
}

function performModRewriteTest() {
	$baseURL = dirname($_SERVER['SCRIPT_NAME']);
	if($baseURL == "/") {
		$baseURL = "";
	}

	// Check if mod_rewrite works properly
	$location = 'http://' . (isset($_SERVER['PHP_AUTH_USER']) ? "$_SERVER[PHP_AUTH_USER]:$_SERVER[PHP_AUTH_PW]@" : '') . $_SERVER['HTTP_HOST'] . $baseURL . '/InstallerTest/testrewrite';
	$testrewriting = file_get_contents($location);

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

function createHtaccess() {
	$start = "### SILVERSTRIPE START ###\n";
	$end= "\n### SILVERSTRIPE END ###";
	$base = dirname($_SERVER['SCRIPT_NAME']);
	
	$rewrite = <<<TEXT
RewriteEngine On
RewriteBase $base

RewriteCond %{REQUEST_URI} !(\.gif$)|(\.jpg$)|(\.png$)|(\.css$)|(\.js$) 

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
		
	createFile('.htaccess', $start . $rewrite . $end);
}

function createHtaccessAlternative() {
	$start = "### SILVERSTRIPE START ###\n";
	$end= "\n### SILVERSTRIPE END ###";
	$base = dirname($_SERVER['SCRIPT_NAME']);
	
	$rewrite = <<<TEXT
RewriteEngine On
RewriteBase $base

RewriteCond %{REQUEST_URI} !(\.gif$)|(\.jpg$)|(\.png$)|(\.css$)|(\.js$) 

RewriteCond %{REQUEST_URI} ^(.*)$
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule .* $_SERVER[DOCUMENT_ROOT]/sapphire/main.php?url=%1&%{QUERY_STRING} [L]
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
		
	createFile('.htaccess', $start . $rewrite . $end);
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
	
	createFile('.htaccess', $start . $end);
}

function getBaseDir() {
	return dirname($_SERVER['SCRIPT_FILENAME']) . '/';
}

function createFile($filename, $content) {
	$base = getBaseDir();
	if(($fh = fopen($base . $filename, 'w')) && fwrite($fh, $content) && fclose($fh))
		return true;
}
?>
