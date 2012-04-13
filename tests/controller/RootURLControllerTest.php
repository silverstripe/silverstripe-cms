<?php
/**
 * @package cms
 * @subpackage tests
 */
class RootURLControllerTest extends SapphireTest {
	static $fixture_file = 'RootURLControllerTest.yml';
	
	public function testGetHomepageLink() {
		$default = $this->objFromFixture('Page', 'home');
		
		SiteTree::disable_nested_urls();
		$this->assertEquals('home', RootURLController::get_homepage_link());
		SiteTree::enable_nested_urls();
		$this->assertEquals('home', RootURLController::get_homepage_link());
	}
	
}
