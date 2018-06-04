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

		$this->origEnvType = 		Config::inst()->get('Director', 'environment_type');
		Config::inst()->update('Director', 'environment_type', 'live');
	}

	public function tearDown() {
		parent::tearDown();

		ErrorPage::config()->static_filepath = $this->orig['ErrorPage_staticfilepath'];

		Filesystem::removeFolder($this->tmpAssetsPath . '/ErrorPageTest');
		Filesystem::removeFolder($this->tmpAssetsPath);

		Config::inst()->update('Director', 'environment_type', $this->origEnvType);
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

	public function testBehaviourOf403() {
		$page = $this->objFromFixture('ErrorPage', '403');
		$page->publish('Stage', 'Live');

		try {
			$controller = singleton('ContentController');
			$controller->httpError(403);
			$this->fail('Expected exception to be thrown');
		}
		catch(SS_HTTPResponse_Exception $e) {
			$response = $e->getResponse();
			$this->assertEquals($response->getStatusCode(), '403');
			$this->assertNotNull($response->getBody(), 'We have body text from the error page');
		}
	}

	public function testSecurityError() {
		// Generate 404 page
		$page = $this->objFromFixture('ErrorPage', '404');
		$page->publish('Stage', 'Live');

		// Test invalid action
		$response = $this->get('Security/nosuchaction');
		$this->assertEquals($response->getStatusCode(), '404');
		$this->assertNotNull($response->getBody());
		$this->assertContains('text/html', $response->getHeader('Content-Type'));
	}

	public function testRequireDefaultRecords()
	{
		$this->logInWithPermission('ADMIN');
		Config::inst()->update('ErrorPage', 'static_filepath', ASSETS_PATH . '/erropagetest');
		Filesystem::makeFolder(ASSETS_PATH . '/erropagetest');

		// Ensure 404 page is published
		/** @var ErrorPage $page404 */
		$page404 = $this->objFromFixture('ErrorPage', '404');
		$page404->publish('Stage', 'Live');

		// Ensure 500 page isn't pubilshed
		/** @var ErrorPage $page500 */
		$page500 = $this->objFromFixture('ErrorPage', '500');
		$page500->doUnpublish();

		// Run regeneration
		/** @var ErrorPage $singleton */
		$singleton = singleton('ErrorPage');
		$singleton->requireDefaultRecords();

		// 404 page exists
		$path404 = ErrorPage::get_filepath_for_errorcode(404);
		$this->assertFileExists($path404);

		// 500 doesn't exist due to it being unpubilshed
		$path500 = ErrorPage::get_filepath_for_errorcode(500);
		$this->assertFileNotExists($path500);
	}
}
