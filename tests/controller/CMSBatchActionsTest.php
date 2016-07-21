<?php

use SilverStripe\ORM\Versioning\Versioned;
use SilverStripe\CMS\BatchActions\CMSBatchAction_Publish;
use SilverStripe\CMS\BatchActions\CMSBatchAction_Unpublish;
use SilverStripe\CMS\BatchActions\CMSBatchAction_Archive;
use SilverStripe\CMS\BatchActions\CMSBatchAction_Restore;
use SilverStripe\CMS\Model\SiteTree;


/**
 * Tests CMS Specific subclasses of {@see CMSBatchAction}
 */
class CMSBatchActionsTest extends SapphireTest {

	protected static $fixture_file = 'CMSBatchActionsTest.yml';

	public function setUp() {
		parent::setUp();

		$this->logInWithPermission('ADMIN');

		// published page
		$published = $this->objFromFixture('SilverStripe\\CMS\\Model\\SiteTree', 'published');
		$published->publishSingle();

		// Deleted / archived page
		$archived = $this->objFromFixture('SilverStripe\\CMS\\Model\\SiteTree', 'archived');
		$archived->doArchive(); // should archive all children

		// Unpublished
		$unpublished = $this->objFromFixture('SilverStripe\\CMS\\Model\\SiteTree', 'unpublished');
		$unpublished->publishSingle();
		$unpublished->doUnpublish();

		// Modified
		$modified = $this->objFromFixture('SilverStripe\\CMS\\Model\\SiteTree', 'modified');
		$modified->publishSingle();
		$modified->Title = 'modified2';
		$modified->write();
	}

	/**
	 * Test which pages can be published via batch actions
	 */
	public function testBatchPublishApplicable() {
		$this->logInWithPermission('ADMIN');
		$pages = Versioned::get_including_deleted('SilverStripe\\CMS\\Model\\SiteTree');
		$ids = $pages->column('ID');
		$action = new CMSBatchAction_Publish();

		// Test applicable pages
		$applicable = $action->applicablePages($ids);
		$this->assertContains($this->idFromFixture('SilverStripe\\CMS\\Model\\SiteTree', 'published'), $applicable);
		$this->assertNotContains($this->idFromFixture('SilverStripe\\CMS\\Model\\SiteTree', 'archived'), $applicable);
		$this->assertNotContains($this->idFromFixture('SilverStripe\\CMS\\Model\\SiteTree', 'archivedx'), $applicable);
		$this->assertNotContains($this->idFromFixture('SilverStripe\\CMS\\Model\\SiteTree', 'archivedy'), $applicable);
		$this->assertContains($this->idFromFixture('SilverStripe\\CMS\\Model\\SiteTree', 'unpublished'), $applicable);
		$this->assertContains($this->idFromFixture('SilverStripe\\CMS\\Model\\SiteTree', 'modified'), $applicable);
	}


	/**
	 * Test which pages can be unpublished via batch actions
	 */
	public function testBatchUnpublishApplicable() {
		$this->logInWithPermission('ADMIN');
		$pages = Versioned::get_including_deleted('SilverStripe\\CMS\\Model\\SiteTree');
		$ids = $pages->column('ID');
		$action = new CMSBatchAction_Unpublish();

		// Test applicable page
		$applicable = $action->applicablePages($ids);
		$this->assertContains($this->idFromFixture('SilverStripe\\CMS\\Model\\SiteTree', 'published'), $applicable);
		$this->assertNotContains($this->idFromFixture('SilverStripe\\CMS\\Model\\SiteTree', 'archived'), $applicable);
		$this->assertNotContains($this->idFromFixture('SilverStripe\\CMS\\Model\\SiteTree', 'archivedx'), $applicable);
		$this->assertNotContains($this->idFromFixture('SilverStripe\\CMS\\Model\\SiteTree', 'archivedy'), $applicable);
		$this->assertNotContains($this->idFromFixture('SilverStripe\\CMS\\Model\\SiteTree', 'unpublished'), $applicable);
		$this->assertContains($this->idFromFixture('SilverStripe\\CMS\\Model\\SiteTree', 'modified'), $applicable);
	}

	/**
	 * Test which pages can be archived via batch actions
	 */
	public function testBatchArchiveApplicable() {
		$this->logInWithPermission('ADMIN');
		$pages = Versioned::get_including_deleted('SilverStripe\\CMS\\Model\\SiteTree');
		$ids = $pages->column('ID');
		$action = new CMSBatchAction_Archive();

		// Test applicable pages
		$applicable = $action->applicablePages($ids);
		$this->assertContains($this->idFromFixture('SilverStripe\\CMS\\Model\\SiteTree', 'published'), $applicable);
		$this->assertNotContains($this->idFromFixture('SilverStripe\\CMS\\Model\\SiteTree', 'archived'), $applicable);
		$this->assertContains($this->idFromFixture('SilverStripe\\CMS\\Model\\SiteTree', 'unpublished'), $applicable);
		$this->assertContains($this->idFromFixture('SilverStripe\\CMS\\Model\\SiteTree', 'modified'), $applicable);
	}

	/**
	 * Test restore batch actions
	 */
	public function testBatchRestoreApplicable() {
		$this->logInWithPermission('ADMIN');
		$pages = Versioned::get_including_deleted('SilverStripe\\CMS\\Model\\SiteTree');
		$ids = $pages->column('ID');
		$action = new CMSBatchAction_Restore();

		// Test applicable pages
		$applicable = $action->applicablePages($ids);
		$this->assertNotContains($this->idFromFixture('SilverStripe\\CMS\\Model\\SiteTree', 'published'), $applicable);
		$this->assertContains($this->idFromFixture('SilverStripe\\CMS\\Model\\SiteTree', 'archived'), $applicable);
		$this->assertContains($this->idFromFixture('SilverStripe\\CMS\\Model\\SiteTree', 'archivedx'), $applicable);
		$this->assertContains($this->idFromFixture('SilverStripe\\CMS\\Model\\SiteTree', 'archivedy'), $applicable);
		$this->assertNotContains($this->idFromFixture('SilverStripe\\CMS\\Model\\SiteTree', 'unpublished'), $applicable);
		$this->assertNotContains($this->idFromFixture('SilverStripe\\CMS\\Model\\SiteTree', 'modified'), $applicable);
	}

	public function testBatchRestore() {
		$this->logInWithPermission('ADMIN');
		$pages = Versioned::get_including_deleted('SilverStripe\\CMS\\Model\\SiteTree');
		$action = new CMSBatchAction_Restore();
		$archivedID = $this->idFromFixture('SilverStripe\\CMS\\Model\\SiteTree', 'archived');
		$archivedxID = $this->idFromFixture('SilverStripe\\CMS\\Model\\SiteTree', 'archivedx');
		$archivedyID = $this->idFromFixture('SilverStripe\\CMS\\Model\\SiteTree', 'archivedy');

		// Just restore one child
		$list = $pages->filter('RecordID', $archivedxID);
		$this->assertEquals(1, $list->count());
		$this->assertEquals($archivedID, $list->first()->ParentID);

		// Run restore
		$result = json_decode($action->run($list), true);
		$this->assertEquals(
			array(
				$archivedxID => $archivedxID
			),
			$result['success']
		);
		$archivedx = SiteTree::get()->byID($archivedxID);
		$this->assertNotNull($archivedx);
		$this->assertEquals(0, $archivedx->ParentID); // Restore to root because parent is unrestored

		// Restore both remaining pages
		$list = $pages
			->filter('RecordID', array($archivedID, $archivedyID))
			->sort('Title');
		$this->assertEquals(2, $list->count());
		$this->assertEquals($archivedID, $list->first()->ParentID); // archivedy
		$this->assertEquals(0, $list->last()->ParentID); // archived (parent)

		// Run restore
		$result = json_decode($action->run($list), true);
		$this->assertEquals(
			array(
				// Order of archived is opposite to order items are passed in, as
				// these are sorted by level first
				$archivedID => $archivedID,
				$archivedyID => $archivedyID
			),
			$result['success']
		);
		$archived = SiteTree::get()->byID($archivedID);
		$archivedy = SiteTree::get()->byID($archivedyID);
		$this->assertNotNull($archived);
		$this->assertNotNull($archivedy);
		$this->assertEquals($archivedID, $archivedy->ParentID); // Not restored to root, but to the parent
		$this->assertEquals(0, $archived->ParentID); // Root stays root
	}

}
