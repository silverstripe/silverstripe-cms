<?php

/**
Command line binary to pull core SilverStripe modules into a new project

This is an attempt to solve a problem introduced by the move to git, namely that new developers
who checkout the silverstripe-installer repo now don't get a "read to go" set of code, and they 
shouldn't need to know how to use git or what modules need pulling in to get a base SilverStripe
install up and running.

Currently it doesn't attempt to solve the more general problem of updating or installing new modules
in an existing project - that's likely to be handled by some of this code + a re-architecture of sake
so that we can let developers add code to handle particulars of the process in an environment-aware manner
*/

$base = dirname(__FILE__);

require dirname($base).'/versions.php';
require 'sources.php';
require 'tools.php';

$opts = getopt('m:t:h',array('mode:', 'template:', 'help'));

$mode = isset($opts['m']) ? $opts['m'] : (isset($opts['mode']) ? $opts['mode'] : 'piston');
$templatefile = isset($opts['t']) ? $opts['t'] : (isset($opts['template']) ? $opts['template'] : 'template.php');

include $templatefile;

if (!isset($template)) {
	echo "Template could not be found.\n\n";
	$templatefile = null;
}
else if ($mode) {
	$errors = array();
	
	foreach ($template as $dest => $source) {
		if     ($mode == 'flat')       $errors = array_merge($errors, (array)$source->canExport());
		elseif ($mode == 'piston')     $errors = array_merge($errors, (array)$source->canPiston());
		elseif ($mode == 'contribute') $errors = array_merge($errors, (array)$source->canCheckout());
	}
	
	$errors = array_unique($errors);
	if ($errors) {
		echo "\nRequirements were not met for mode $mode:\n  ";
		echo implode("\n  ", $errors);
		echo "\n\nEither correct the requirements or try a different mode\n\n";
		$mode = null;
	}
}	

if (($mode != 'piston' && $mode != 'flat' && $mode != 'contribute') || !$templatefile || isset($opts['h']) || isset($opts['help'])) {
	echo "Usage: new-project [-m | --mode piston | flat | contribute] [-t | --template template.php] [-h | --help]\n";
	echo "\n";
	echo "    piston is the default mode, and uses the piston tool to add the core modules\n";
	echo "        It allows pulling down core module updates later while maintaining your changes.\n";
	echo "        It does not provide any tools for contributing your changes back upstream, though a third-party tool for git is available\n";
	echo "        It requires the external piston tool and all it's dependancies to be installed.\n";
	echo "        It only works on svn and git managed repositories.\n";
	echo "\n";
	echo "    flat copies the core module code without using any tools or version control\n";
	echo "        It does not provide any tools for pulling down core modules updates later, or contributing changes back upstream\n";
	echo "        It requires only php with the zip and curl extensions\n";
	echo "        It works regardless of version control system\n";
	echo "\n";
	echo "    contribute sets up the core as separate modules, allowing you to contribute any changes back upstream\n";
	echo "        It allows pulling down core module updates later while maintaining your changes.\n";
	echo "        It allows contributing your changes back upstream\n";
	echo "        It requires git and all it's dependancies to be installed.\n";
	echo "        It only works on git managed repositories.\n";
	die;
}

// Check we're not being re-called before we do anything
$alreadyexists = false;
foreach ($template as $dest => $source) {
	if (file_exists($dest)) { 
		echo "ERROR: Module $dest already exists. This script can not be called multiple times, or upgrade existing modules.\n"; 
		$alreadyexists = true;
	}
}
if ($alreadyexists) die;

if ($mode == 'piston') {
	echo "Now running piston to add modules. Piston is quite noisy, and can sometimes list errors as fatal that can be ignored.\n";
	echo "If errors are shown, please check result before assuming failure\n\n";
}

foreach ($template as $dest => $source) {
	if ($mode == 'contribute') {
		GIT::ignore($dest);
		$source->checkout($dest);
	}
	else if ($mode == 'piston') $source->piston($dest);
	else $source->export($dest);
}

if ($mode == 'piston' && GIT::isGITRepo()) {
	echo "\n\nNow commit the changes with something like \"git commit -m 'Import core SilverStripe modules'\"\n";
}

