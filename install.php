<?php

/************************************************************************************
 ************************************************************************************
 **                                                                                **
 **  If you can read this text in your browser then you don't have PHP installed.  **
 **  Please install PHP 5.2 or higher, preferably PHP 5.3.                         **
 **                                                                                **
 ************************************************************************************
 ************************************************************************************/

if (!file_exists('sapphire') || !file_exists('sapphire/_config.php')) include "install-sapphiremissing.html";
else include('./sapphire/dev/install/install.php');
