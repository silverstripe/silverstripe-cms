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

	protected $restoreFiles = array();
	protected $restoreDirectories = array();

	/**
	 * Create a test theme
	 *
	 * @Given /^a theme "(?<theme>[^"]+)"/
	 */
	public function stepCreateTheme($theme) {
		if(!preg_match('/^[0-9a-zA-Z_-]+$/', $theme)) throw new \InvalidArgumentException("Bad theme '$theme'");

		$this->requireDir(BASE_PATH . '/themes');
		$this->requireDir(BASE_PATH . '/themes/' . $theme);
		$this->requireDir(BASE_PATH . '/themes/' . $theme . '/templates');
	}

	/**
	 * Create a template within a test theme
	 *
	 * @Given /^a template "(?<template>[^"]+)" in theme "(?<theme>[^"]+)" with content "(?<content>[^"]+)"/
	 */
	public function stepCreateTemplate($template, $theme, $content) {
		if(!preg_match('/^[0-9a-zA-Z_-]+$/', $theme)) throw new \InvalidArgumentException("Bad theme '$theme'");
		if(!preg_match('/^(Layout\/)?[0-9a-zA-Z_-]+\.ss$/', $template)) throw new \InvalidArgumentException("Bad template '$template'");

		$this->stepCreateTheme($theme);
		$this->requireFile(BASE_PATH . '/themes/' . $theme . '/templates/' . $template, $content);
	}

	protected function requireFile($filename, $content) {
		// Already exists
		if(file_exists($filename)) {
			// If the content is different, remember old content for restoration
			$origContent = file_get_contents($filename);
			if($origContent != $content) {
				file_put_contents($filename, $content);
				$this->restoreFiles[$filename] = $origContent;
			}
		// Doesn't exist, mark it for deletion after test
		} else {
			file_put_contents($filename, $content);
			$this->restoreFiles[$filename] = null;
		}
	}

	protected function requireDir($dirname) {
		// Directory doesn't exist, create it and mark it for deletion
		if(!file_exists($dirname)) {
			mkdir($dirname);
			$this->restoreDirectories[] = $dirname;
		}
	}

	/**
	 * Clean up any theme manipulation
	 *
	 * @AfterScenario
	 */
	public function cleanThemesAfterScenario() {
		// Restore any created/modified files.
		//  - If modified, revert then to original contnet
		//  - If created, delete them
		if($this->restoreFiles) {
			foreach($this->restoreFiles as $file => $origContent) {
				if($origContent === null) {
					unlink($file);
				} else {
					file_put_contents($file, $origContent);
				}
			}

			$this->restoreFiles = array();
		}

		// Restore any created directories: that is, delete them
		if($this->restoreDirectories) {
			// Flip the order so that nested direcotires are unlinked() first
			$this->restoreDirectories = array_reverse($this->restoreDirectories);
			foreach($this->restoreDirectories as $dir) {
				rmdir($dir);
			}

			$this->restoreDirectories = array();
		}
	}
}
