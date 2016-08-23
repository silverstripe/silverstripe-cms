<?php

use SilverStripe\ORM\Versioning\Versioned;
use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\ORM\Search\FulltextSearchable;
use SilverStripe\Core\Config\Config;
use SilverStripe\Assets\File;
use SilverStripe\Dev\SapphireTest;



class ContentControllerSearchExtensionTest extends SapphireTest {

	public function testCustomSearchFormClassesToTest() {
		$page = new Page();
		$page->URLSegment = 'whatever';
		$page->Content = 'oh really?';
		$page->write();
		$page->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
		$controller = new ContentController($page);
		$form = $controller->SearchForm();

		if (get_class($form) == 'SilverStripe\\CMS\\Search\\SearchForm') $this->assertEquals(array('SilverStripe\\Assets\\File'), $form->getClassesToSearch());
	}

	public function setUpOnce() {
		parent::setUpOnce();

		FulltextSearchable::enable('SilverStripe\\Assets\\File');
	}

	/**
	 * FulltextSearchable::enable() leaves behind remains that don't get cleaned up
	 * properly at the end of the test. This becomes apparent when a later test tries to
	 * ALTER TABLE File and add fulltext indexes with the InnoDB table type.
	 */
	public function tearDownOnce() {
		parent::tearDownOnce();

		Config::inst()->update('SilverStripe\\Assets\\File', 'create_table_options', array('SilverStripe\ORM\Connect\MySQLDatabase' => 'ENGINE=InnoDB'));
		File::remove_extension('SilverStripe\\ORM\\Search\\FulltextSearchable');
	}

}
