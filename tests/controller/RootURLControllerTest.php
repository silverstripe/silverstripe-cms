<?php

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\CMS\Controllers\RootURLController;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;

/**
 * @package cms
 * @subpackage tests
 */
class RootURLControllerTest extends SapphireTest {
	protected static $fixture_file = 'RootURLControllerTest.yml';

	public function testGetHomepageLink() {
		$default = $this->objFromFixture('Page', 'home');

		SiteTree::config()->nested_urls = false;
		$this->assertEquals('home', RootURLController::get_homepage_link());
		Config::inst()->update('SilverStripe\\CMS\\Model\\SiteTree', 'nested_urls', true);
		$this->assertEquals('home', RootURLController::get_homepage_link());
	}

}
