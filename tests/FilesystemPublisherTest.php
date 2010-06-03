<?php
/**
 * Tests for the {@link FilesystemPublisher} class.
 * 
 * @package cms
 * @subpackage tests
 */
class FilesystemPublisherTest extends SapphireTest {
	function setup() {
		parent::setup();
		SiteTree::$write_homepage_map = false;
	}
	
	function teardown() {
		parent::teardown();
		SiteTree::$write_homepage_map = true;
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
	
	function testHomepageMapIsCorrect() {
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
		
		$validMap = array(
			'domain1' => strtolower(__CLASS__).'-page-2',
			'domain2' => strtolower(__CLASS__).'-page-3',
			'domain3' => strtolower(__CLASS__).'-page-3',
		);
		
		$this->assertEquals($map, $validMap, 'Homepage/domain map is correct');
	}
	
}