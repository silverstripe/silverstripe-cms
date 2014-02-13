<?php

namespace SilverStripe\Cms\Test\Behaviour;

use Behat\Behat\Context\ClosuredContextInterface,
	Behat\Behat\Context\TranslatedContextInterface,
	Behat\Behat\Context\BehatContext,
	Behat\Behat\Context\Step,
	Behat\Behat\Event\StepEvent,
	Behat\Behat\Exception\PendingException,
	Behat\Mink\Driver\Selenium2Driver,
	Behat\Gherkin\Node\PyStringNode,
	Behat\Gherkin\Node\TableNode;

// PHPUnit
require_once 'PHPUnit/Autoload.php';
require_once 'PHPUnit/Framework/Assert/Functions.php';

/**
 * Context used to create fixtures in the SilverStripe ORM.
 */
class ThemeContext extends BehatContext {

	/**
	 * Create a test theme
	 * 
	 * @Given /^a theme "(?<theme>[^"]+)"/
	 */
	public function stepCreateTheme($theme) {
		if(!preg_match('/^[0-9a-zA-Z_-]+$/', $theme)) throw new \InvalidArgumentException("Bad theme '$theme'");

		if(!file_exists(BASE_PATH . '/themes/' . $theme)) mkdir(BASE_PATH . '/themes/' . $theme);
		if(!file_exists(BASE_PATH . '/themes/' . $theme . '/templates')) mkdir(BASE_PATH . '/themes/' . $theme . '/templates');
	}

	/**
	 * Create a template within a test theme
	 * 
	 * @Given /^a template "(?<template>[^"]+)" in theme "(?<theme>[^"]+)" with content "(?<content>[^"]+)"/
	 */
	public function stepCreateTemplate($template, $theme, $content) {
		if(!preg_match('/^[0-9a-zA-Z_-]+$/', $theme)) throw new \InvalidArgumentException("Bad theme '$theme'");
		if(!preg_match('/^(Layout\/)?[0-9a-zA-Z_-]+\.ss$/', $template)) throw new \InvalidArgumentException("Bad template '$template'");

		file_put_contents(BASE_PATH . '/themes/' . $theme . '/templates/' . $template, $content);
	}	
}
