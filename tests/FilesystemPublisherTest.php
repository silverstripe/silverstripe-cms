<?php
/**
 * Tests for the {@link FilesystemPublisher} class.
 * 
 * @package cms
 * @subpackage tests
 */
class FilesystemPublisherTest extends SapphireTest {
	
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
	
}