<?php

/**
Base template used by new-project to know what modules to install where & where to get them from
Structure is likely to change at some point
*/

$template = array(
	'sapphire' => new Github(array(
		'user' => 'silverstripe', 
		'project' => 'sapphire', 
		'branch' => SAPPHIRE_CURRENT_BRANCH
	)),
	'cms' => new Github(array(
		'user' => 'silverstripe', 
		'project' => 'silverstripe-cms',
		'branch' => SAPPHIRE_CURRENT_BRANCH
	)),
	'themes/blackcandy' => new SvnRepo(array(
		'repo' => 'http://svn.silverstripe.com/open/themes/blackcandy',
		'branch' => 'trunk',
		'subdir' => 'blackcandy'
	)),
	'themes/blackcandy_blog' => new SvnRepo(array(
		'repo' => 'http://svn.silverstripe.com/open/themes/blackcandy',
		'branch' => 'trunk',
		'subdir' => 'blackcandy_blog'
	))
);

