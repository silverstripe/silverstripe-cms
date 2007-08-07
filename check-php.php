<?php
	if(ini_get("short_open_tag")) {
		 header("Location: install.php"); 
	} else {
		echo "Please set the PHP option short_open_tag to true, restart your webserver, and then refresh your browser to continue.";
	}
?>
<!--<?php /*-->
<html>
<head>
<title>No PHP Support</title>
</head>

<body>
<h1>No PHP Support</h1>
<p>
	<p>Before I can install SilverStripe 2, you must add PHP support to your webserver.</p>
	<p><a href="check-php.php">Try again</a></p>

</body>
</html>
<!--*/?>-->