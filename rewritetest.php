<?php

if(file_exists('.htaccess_rewrite')) {
	copy(".htaccess_rewrite", ".htaccess");
}

$baseURL = dirname($_SERVER['SCRIPT_NAME']);
if($baseURL == "/") {
	$baseURL = "";
}

if(isset($_REQUEST['force'])) {
	echo "Forced continue, attempting to redirect to <a href=\"home/successfullyinstalled\">home/successfullyinstalled</a>.
		<script>setTimeout(function() { window.location.href = 'home/successfullyinstalled'; }, 1000);</script>";
} else {	
	$modRewriteWorking  = performModRewriteTest();
	
	if(!$modRewriteWorking) {
		copy(".htaccess_alternative", ".htaccess");
		$modRewriteWorking  = performModRewriteTest();
	}
	
	if($modRewriteWorking) {
		echo "mod_rewrite is working! I will now try and direct you to
					<a href=\"home/successfullyinstalled\">home/successfullyinstalled</a> to confirm that the installation was successful.
					<script>setTimeout(function() { window.location.href = 'home/successfullyinstalled'; }, 1000);</script>
		";
	} else {
		unlink('.htaccess');
		if(file_exists('.htaccess_orig')) {
			copy('.htaccess_orig', '.htaccess');
		}
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
	$location = 'http://' . (isset($_SERVER['PHP_AUTH_USER']) ? "$_SERVER[PHP_AUTH_USER]:$_SERVER[PHP_AUTH_PW]@" : '') . $_SERVER['HTTP_HOST'] . $baseURL . '/InstallerTest/testRewrite';
	@$testrewriting = file_get_contents($location);

	if($testrewriting == 'OK') {
		return true;
	}
	
	// Workaround for 'URL file-access is disabled in the server configuration' using curl
	if(function_exists('curl_init')) {
		$ch = curl_init($location);
		$fp = @fopen("temp", "w");
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_exec($ch);
		curl_close($ch);
		fclose($fp);
		$testrewriting = file_get_contents('temp');
		unlink('temp');
		if($testrewriting == 'OK') {
			return true;
		}
	}
	
	return false;
}
?>
