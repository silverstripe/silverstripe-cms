<?php
/**
 * Tests for the {@link FilesystemPublisher} class.
 * 
 * @package cms
 * @subpackage tests
 */
class FilesystemPublisherTest extends SapphireTest {
	
	protected $usesDatabase = true;
	
	function setUp() {
		parent::setUp();
		
		Object::add_extension("SiteTree", "FilesystemPublisher('../FilesystemPublisherTest-static-folder/')");
		SiteTree::$write_homepage_map = false;
	}
	
	function tearDown() {
		Object::remove_extension("SiteTree", "FilesystemPublisher('../FilesystemPublisherTest-static-folder/')");
		SiteTree::$write_homepage_map = true;
		
		parent::tearDown();
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
	function testHasCalledParentConstructor() {
		$fsp = new FilesystemPublisher('.', '.html');
		$this->assertEquals($fsp->class, 'FilesystemPublisher');
	}
	
	function testHomepageMapIsWithStaticPublishing() {
		$this->logInWithPermission('ADMIN');
		
		$p1 = new Page();
		$p1->URLSegment = strtolower(__CLASS__).'-page-1';
		$p1->HomepageForDomain = '';
		$p1->write();
		$p1->doPublish();
		$p2 = new Page();
		$p2->URLSegment = strtolower(__CLASS__).'-page-2';
		$p2->HomepageForDomain = 'domain1';
		$p2->write();
		$p2->doPublish();
		$p3 = new Page();
		$p3->URLSegment = strtolower(__CLASS__).'-page-3';
		$p3->HomepageForDomain = 'domain2,domain3';
		$p3->write();
		$p3->doPublish();
		
		$map = SiteTree::generate_homepage_domain_map();
		
		$this->assertEquals(
			$map, 
			array(
				'domain1' => strtolower(__CLASS__).'-page-2',
				'domain2' => strtolower(__CLASS__).'-page-3',
				'domain3' => strtolower(__CLASS__).'-page-3',
			), 
			'Homepage/domain map is correct when static publishing is enabled'
		);
	}
	
	/*
	 * These are a few simple tests to check that we will be retrieving the correct theme when we need it
	 * StaticPublishing needs to be able to retrieve a non-null theme at the time publishPages() is called.
	 */
	function testStaticPublisherTheme(){
		
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
	
}