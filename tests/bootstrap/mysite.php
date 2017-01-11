<?php

// Mock mysite if not installed with silverstripe/installer
if (defined('BASE_PATH')) {
    $projectPath = BASE_PATH . '/mysite';
} else {
    $projectPath = getcwd() . '/mysite';
}
if (!is_dir($projectPath)) {
    mkdir($projectPath, 02775);
    mkdir($projectPath . '/code', 02775);
    mkdir($projectPath . '/_config', 02775);
    copy(__DIR__ . '/fixtures/Page.php.fixture', $projectPath . '/code/Page.php');
    copy(__DIR__ . '/fixtures/PageController.php.fixture', $projectPath . '/code/PageController.php');
}
