<?php
class ContentControllerSearchExtensionTest extends SapphireTest {

	public function testCustomSearchFormClassesToTest() {
		$page = new Page();
		$page->URLSegment = 'whatever';
		$page->Content = 'oh really?';
		$page->write();
		$page->publish('Stage', 'Live');
		$controller = new ContentController($page);
		$form = $controller->SearchForm();
		
		if (get_class($form) == 'SearchForm') $this->assertEquals(array('File'), $form->getClassesToSearch());
	}

	public function setUpOnce() {
		parent::setUpOnce();

		FulltextSearchable::enable('File');
	}

	/**
	 * FulltextSearchable::enable() leaves behind remains that don't get cleaned up
	 * properly at the end of the test. This becomes apparent when a later test tries to
	 * ALTER TABLE File and add fulltext indexes with the InnoDB table type.
	 */
	public function tearDownOnce() {
		parent::tearDownOnce();

		Config::inst()->update('File', 'create_table_options', array('MySQLDatabase' => 'ENGINE=InnoDB'));
		File::remove_extension('FulltextSearchable');
	}

}
