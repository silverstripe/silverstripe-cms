<?php
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
		Config::inst()->update('SiteTree', 'nested_urls', true);
		$this->assertEquals('home', RootURLController::get_homepage_link());
	}
	
}
