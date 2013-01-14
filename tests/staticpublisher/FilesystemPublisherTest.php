<?php
/**
 * Tests for the {@link FilesystemPublisher} class.
 * 
 * @package cms
 * @subpackage tests
 */
class FilesystemPublisherTest extends SapphireTest {
	
	protected $usesDatabase = true;
	
	protected $orig = array();

	static $fixture_file = 'cms/tests/FileSystemPublisherTest.yml';
	
	public function setUp() {
		parent::setUp();
		
		SiteTree::add_extension("FilesystemPublisher('assets/FilesystemPublisherTest-static-folder/')");
		
		$this->orig['domain_based_caching'] = FilesystemPublisher::$domain_based_caching;
		FilesystemPublisher::$domain_based_caching = false;
	}
	
	public function tearDown() {
		parent::tearDown();

		SiteTree::remove_extension("FilesystemPublisher('assets/FilesystemPublisherTest-static-folder/')");

		FilesystemPublisher::$domain_based_caching = $this->orig['domain_based_caching'];

		if(file_exists(BASE_PATH . '/assets/FilesystemPublisherTest-static-folder')) {
			Filesystem::removeFolder(BASE_PATH . '/assets/FilesystemPublisherTest-static-folder');
		}
	}
	
	public function testUrlsToPathsWithRelativeUrls() {
		$fsp = new FilesystemPublisher('.', 'html');
		
		$this->assertEquals(
			$fsp->urlsToPaths(array('/')),
			array('/' => './index.html'),
			'Root URL path mapping'
		);
		
		$this->assertEquals(
			$fsp->urlsToPaths(array('about-us')),
			array('about-us' => './about-us.html'),
			'URLsegment path mapping'
		);
		
		$this->assertEquals(
			$fsp->urlsToPaths(array('parent/child')),
			array('parent/child' => 'parent/child.html'),
			'Nested URLsegment path mapping'
		);
	}
	
	public function testUrlsToPathsWithAbsoluteUrls() {
		$fsp = new FilesystemPublisher('.', 'html');
		
		$url = Director::absoluteBaseUrl();
		$this->assertEquals(
			$fsp->urlsToPaths(array($url)),
			array($url => './index.html'),
			'Root URL path mapping'
		);
		
		$url = Director::absoluteBaseUrl() . 'about-us';
		$this->assertEquals(
			$fsp->urlsToPaths(array($url)),
			array($url => './about-us.html'),
			'URLsegment path mapping'
		);
		
		$url = Director::absoluteBaseUrl() . 'parent/child';
		$this->assertEquals(
			$fsp->urlsToPaths(array($url)),
			array($url => 'parent/child.html'),
			'Nested URLsegment path mapping'
		);
	}

	public function testUrlsToPathsWithDomainBasedCaching() {
		$origDomainBasedCaching = FilesystemPublisher::$domain_based_caching;
		FilesystemPublisher::$domain_based_caching = true;
		
		$fsp = new FilesystemPublisher('.', 'html');
		
		$url = 'http://domain1.com/';
		$this->assertEquals(
			$fsp->urlsToPaths(array($url)),
			array($url => 'domain1.com/index.html'),
			'Root URL path mapping'
		);
		
		$url = 'http://domain1.com/about-us';
		$this->assertEquals(
			$fsp->urlsToPaths(array($url)),
			array($url => 'domain1.com/about-us.html'),
			'URLsegment path mapping'
		);
		
		$url = 'http://domain2.com/parent/child';
		$this->assertEquals(
			$fsp->urlsToPaths(array($url)),
			array($url => 'domain2.com/parent/child.html'),
			'Nested URLsegment path mapping'
		);
		
		FilesystemPublisher::$domain_based_caching = $origDomainBasedCaching;
	}
	
	/**
	 * Simple test to ensure that FileSystemPublisher::__construct()
	 * has called parent::__construct() by checking the class property.
	 * The class property is set on {@link Object::__construct()} and
	 * this is therefore a good test to ensure it was called.
	 * 
	 * If FilesystemPublisher doesn't call parent::__construct() then
	 * it won't be enabled propery because {@link Object::__construct()}
	 * is where extension instances are set up and subsequently used by
	 * {@link DataObject::defineMethods()}.
	 */
	public function testHasCalledParentConstructor() {
		$fsp = new FilesystemPublisher('.', '.html');
		$this->assertEquals($fsp->class, 'FilesystemPublisher');
	}
	
	/*
	 * These are a few simple tests to check that we will be retrieving the correct theme when we need it
	 * StaticPublishing needs to be able to retrieve a non-null theme at the time publishPages() is called.
	 */
	public function testStaticPublisherTheme(){
		
		//This will be the name of the default theme of this particular project
		$default_theme=SSViewer::current_theme();
		
		$p1 = new Page();
		$p1->URLSegment = strtolower(__CLASS__).'-page-1';
		$p1->HomepageForDomain = '';
		$p1->write();
		$p1->doPublish();
		
		$current_theme=SSViewer::current_custom_theme();
		$this->assertEquals($current_theme, $default_theme, 'After a standard publication, the theme is correct');
		
		//The CMS sometimes sets the theme to null.  Check that the $current_custom_theme is still the default
		SSViewer::set_theme(null);
		$current_theme=SSViewer::current_custom_theme();
		$this->assertEquals($current_theme, $default_theme, 'After a setting the theme to null, the default theme is correct');
		
		//We can set the static_publishing theme to something completely different:
		//Static publishing will use this one instead of the current_custom_theme if it is not false
		StaticPublisher::set_static_publisher_theme('otherTheme');
		$current_theme=StaticPublisher::static_publisher_theme();
		$this->assertNotEquals($current_theme, $default_theme, 'The static publisher theme overrides the custom theme');
		
		
	}

	function testPublishPages() {
		$cacheFolder = '/assets/FilesystemPublisherTest-static-folder/';
		$cachePath = Director::baseFolder() . $cacheFolder;
		$publisher = new FilesystemPublisher($cacheFolder, 'html');
		$page1 = $this->objFromFixture('Page', 'page1');
		$page1->publish('Stage', 'Live');
		$redirector1 = $this->objFromFixture('RedirectorPage', 'redirector1');
		$redirector1->publish('Stage', 'Live');
		
		$results = $publisher->publishPages(array(
			$page1->Link(), 
			$redirector1->regularLink(),
			'notfound'
		));

		$this->assertArrayHasKey($page1->Link(), $results);
		$this->assertEquals(200, $results[$page1->Link()]['statuscode']);
		$this->assertEquals(
			realpath($results[$page1->Link()]['path']), 
			realpath($cachePath . './page1.html')
		);

		$this->assertArrayHasKey($redirector1->regularLink(), $results);
		$this->assertEquals(301, $results[$redirector1->regularLink()]['statuscode']);
		$this->assertEquals('/page1/', $results[$redirector1->regularLink()]['redirect']);
		$this->assertEquals(
			realpath($results[$redirector1->regularLink()]['path']), 
			realpath($cachePath . './redirect-to-page1.html')
		);

		$this->assertArrayHasKey('notfound', $results);
		$this->assertEquals(404, $results['notfound']['statuscode']);
		$this->assertNull($results['notfound']['redirect']);
		$this->assertNull($results['notfound']['path']);
	}
	
}
