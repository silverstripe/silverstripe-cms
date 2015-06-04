<?php


/**
 * Tests CMS Specific subclasses of {@see CMSBatchAction}
 */
class CMSBatchActionsTest extends SapphireTest {

	protected static $fixture_file = 'CMSBatchActionsTest.yml';

	public function setUp() {
		parent::setUp();

		// published page
		$published = $this->objFromFixture('Page', 'published');
		$published->doPublish();

		// Deleted / archived page
		$archived = $this->objFromFixture('Page', 'archived');
		$archived->doArchive();
		
		// Unpublished
		$unpublished = $this->objFromFixture('Page', 'unpublished');
		$unpublished->doPublish();
		$unpublished->doUnpublish();

		// Modified
		$modified = $this->objFromFixture('Page', 'modified');
		$modified->doPublish();
		$modified->Title = 'modified2';
		$modified->write();
	}

	/**
	 * Test which pages can be published via batch actions
	 */
	public function testBatchPublish() {
		$this->logInWithPermission('ADMIN');
		$pages = Versioned::get_including_deleted('Page');
		$ids = $pages->column('ID');
		$action = new CMSBatchAction_Publish();

		// Test applicable pages
		$applicable = $action->applicablePages($ids);
		$this->assertContains($this->idFromFixture('Page', 'published'), $applicable);
		$this->assertNotContains($this->idFromFixture('Page', 'archived'), $applicable);
		$this->assertContains($this->idFromFixture('Page', 'unpublished'), $applicable);
		$this->assertContains($this->idFromFixture('Page', 'modified'), $applicable);
	}


	/**
	 * Test which pages can be unpublished via batch actions
	 */
	public function testBatchUnpublish() {
		$this->logInWithPermission('ADMIN');
		$pages = Versioned::get_including_deleted('Page');
		$ids = $pages->column('ID');
		$action = new CMSBatchAction_Unpublish();

		// Test applicable page
		$applicable = $action->applicablePages($ids);
		$this->assertContains($this->idFromFixture('Page', 'published'), $applicable);
		$this->assertNotContains($this->idFromFixture('Page', 'archived'), $applicable);
		$this->assertNotContains($this->idFromFixture('Page', 'unpublished'), $applicable);
		$this->assertContains($this->idFromFixture('Page', 'modified'), $applicable);
	}

	/**
	 * Test which pages can be published via batch actions
	 */
	public function testBatchArchive() {
		$this->logInWithPermission('ADMIN');
		$pages = Versioned::get_including_deleted('Page');
		$ids = $pages->column('ID');
		$action = new CMSBatchAction_Archive();

		// Test applicable pages
		$applicable = $action->applicablePages($ids);
		$this->assertContains($this->idFromFixture('Page', 'published'), $applicable);
		$this->assertNotContains($this->idFromFixture('Page', 'archived'), $applicable);
		$this->assertContains($this->idFromFixture('Page', 'unpublished'), $applicable);
		$this->assertContains($this->idFromFixture('Page', 'modified'), $applicable);
	}

}
