<?php
/**
 * @package cms
 * @subpackage tests
 */
class ErrorPageTest extends FunctionalTest {
	
	protected static $fixture_file = 'ErrorPageTest.yml';
	
	protected $orig = array();
	
	protected $tmpAssetsPath = '';
	
	public function setUp() {
		parent::setUp();
		
		$this->orig['ErrorPage_staticfilepath'] = ErrorPage::config()->static_filepath;		
		$this->tmpAssetsPath = sprintf('%s/_tmp_assets_%s', TEMP_FOLDER, rand());
		Filesystem::makeFolder($this->tmpAssetsPath . '/ErrorPageTest');
		ErrorPage::config()->static_filepath = $this->tmpAssetsPath . '/ErrorPageTest';
		
		Config::inst()->update('Director', 'environment_type', 'live');
	}
	
	public function tearDown() {
		parent::tearDown();
		
		ErrorPage::config()->static_filepath = $this->orig['ErrorPage_staticfilepath'];
		
		Filesystem::removeFolder($this->tmpAssetsPath . '/ErrorPageTest');
		Filesystem::removeFolder($this->tmpAssetsPath);
	}
	
	public function test404ErrorPage() {
		$page = $this->objFromFixture('ErrorPage', '404');
		// ensure that the errorpage exists as a physical file
		$page->publish('Stage', 'Live');
		
		$response = $this->get('nonexistent-page');
		
		/* We have body text from the error page */
		$this->assertNotNull($response->getBody(), 'We have body text from the error page');

		/* Status code of the SS_HTTPResponse for error page is "404" */
		$this->assertEquals($response->getStatusCode(), '404', 'Status code of the SS_HTTPResponse for error page is "404"');
		
		/* Status message of the SS_HTTPResponse for error page is "Not Found" */
		$this->assertEquals($response->getStatusDescription(), 'Not Found', 'Status message of the HTTResponse for error page is "Not found"');
	}
	
	public function testBehaviourOfShowInMenuAndShowInSearchFlags() {
		$page = $this->objFromFixture('ErrorPage', '404');
		
		/* Don't show the error page in the menus */
		$this->assertEquals($page->ShowInMenus, 0, 'Don\'t show the error page in the menus');
		
		/* Don't show the error page in the search */
		$this->assertEquals($page->ShowInSearch, 0, 'Don\'t show the error page in search');
	}
	
}
