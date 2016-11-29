<?php

if (!defined('FRAMEWORK_PATH')) {
    echo "FRAMEWORK_PATH hasn't been defined. This probably means that framework/Core/Constants.php hasn't been " .
        "included by Composer's autoloader.\n" .
        "Make sure the you are running your tests via vendor/bin/phpunit and your autoloader is up to date.\n";
    exit(1);
}

if (empty($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = 'localhost';
}
