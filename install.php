<?php

/************************************************************************************
 ************************************************************************************
 **                                                                                **
 **  If you can read this text in your browser then you don't have PHP installed.  **
 **  Please install PHP 5.3.2 or higher, preferably PHP 5.3.4+.                    **
 **                                                                                **
 ************************************************************************************
 ************************************************************************************/

if (!file_exists('framework') || !file_exists('framework/_config.php')) include "install-frameworkmissing.html";
else include('./framework/dev/install/install.php');
