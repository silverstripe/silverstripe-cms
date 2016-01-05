<?php
/**
 * Possible actions:
 * - action_save
 * - action_publish
 * - action_unpublish
 * - action_archive
 * - action_deletefromlive
 * - action_rollback
 * - action_revert
 *
 * @package cms
 * @subpackage tests
 */
class SiteTreeActionsTest extends FunctionalTest {

	protected static $fixture_file = 'SiteTreeActionsTest.yml';
	
	public function testActionsReadonly() {
		if(class_exists('SiteTreeCMSWorkflow')) return true;
		
		$readonlyEditor = $this->objFromFixture('Member', 'cmsreadonlyeditor');
		$this->session()->inst_set('loggedInAs', $readonlyEditor->ID);
	
		$page = new SiteTreeActionsTest_Page();
		$page->CanEditType = 'LoggedInUsers';
		$page->write();
		$page->doPublish();
	
		$actions = $page->getCMSActions();
	
		$this->assertNull($actions->dataFieldByName('action_save'));
		$this->assertNull($actions->dataFieldByName('action_publish'));
		$this->assertNull($actions->dataFieldByName('action_unpublish'));
		$this->assertNull($actions->dataFieldByName('action_delete'));
		$this->assertNull($actions->dataFieldByName('action_deletefromlive'));
		$this->assertNull($actions->dataFieldByName('action_rollback'));
		$this->assertNull($actions->dataFieldByName('action_revert'));
	}
	
	public function testActionsNoDeletePublishedRecord() {
		if(class_exists('SiteTreeCMSWorkflow')) return true;

		$this->logInWithPermission('ADMIN');
		
		$page = new SiteTreeActionsTest_Page();
		$page->CanEditType = 'LoggedInUsers';
		$page->write();
		$pageID = $page->ID;
		$page->doPublish();
		$page->deleteFromStage('Stage');
		
		// Get the live version of the page
		$page = Versioned::get_one_by_stage("SiteTree", "Live", "\"SiteTree\".\"ID\" = $pageID");
		$this->assertInstanceOf("SiteTree", $page);
		
		// Check that someone without the right permission can't delete the page
		$editor = $this->objFromFixture('Member', 'cmsnodeleteeditor');
		$this->session()->inst_set('loggedInAs', $editor->ID);

		$actions = $page->getCMSActions();
		$this->assertNull($actions->dataFieldByName('action_deletefromlive'));

		// Check that someone with the right permission can delete the page
 		$this->objFromFixture('Member', 'cmseditor')->logIn();
		$actions = $page->getCMSActions();
		$this->assertNotNull($actions->dataFieldByName('action_deletefromlive'));
	}

	public function testActionsPublishedRecord() {
		if(class_exists('SiteTreeCMSWorkflow')) return true;

		$author = $this->objFromFixture('Member', 'cmseditor');
		$this->session()->inst_set('loggedInAs', $author->ID);
		
		$page = new Page();
		$page->CanEditType = 'LoggedInUsers';
		$page->write();
		$page->doPublish();

		$actions = $page->getCMSActions();
	
		$this->assertNotNull($actions->dataFieldByName('action_save'));
		$this->assertNotNull($actions->dataFieldByName('action_publish'));
		$this->assertNotNull($actions->dataFieldByName('action_unpublish'));
		$this->assertNotNull($actions->dataFieldByName('action_archive'));
		$this->assertNull($actions->dataFieldByName('action_deletefromlive'));
		$this->assertNull($actions->dataFieldByName('action_rollback'));
		$this->assertNull($actions->dataFieldByName('action_revert'));
	}
	
	public function testActionsDeletedFromStageRecord() {
		if(class_exists('SiteTreeCMSWorkflow')) return true;

		$author = $this->objFromFixture('Member', 'cmseditor');
		$this->session()->inst_set('loggedInAs', $author->ID);
		
		$page = new Page();
		$page->CanEditType = 'LoggedInUsers';
		$page->write();
		$pageID = $page->ID;
		$page->doPublish();
		$page->deleteFromStage('Stage');
		
		// Get the live version of the page
		$page = Versioned::get_one_by_stage("SiteTree", "Live", "\"SiteTree\".\"ID\" = $pageID");
		$this->assertInstanceOf('SiteTree', $page);
		
		$actions = $page->getCMSActions();
		
		$this->assertNull($actions->dataFieldByName('action_save'));
		$this->assertNull($actions->dataFieldByName('action_publish'));
		$this->assertNull($actions->dataFieldByName('action_unpublish'));
		$this->assertNull($actions->dataFieldByName('action_archive'));
		$this->assertNotNull($actions->dataFieldByName('action_deletefromlive'));
		$this->assertNull($actions->dataFieldByName('action_rollback'));
		$this->assertNotNull($actions->dataFieldByName('action_revert'));
	}
	
	public function testActionsChangedOnStageRecord() {
		if(class_exists('SiteTreeCMSWorkflow')) return true;
		
		$author = $this->objFromFixture('Member', 'cmseditor');
		$this->session()->inst_set('loggedInAs', $author->ID);
		
		$page = new Page();
		$page->CanEditType = 'LoggedInUsers';
		$page->write();
		$page->doPublish();
		$page->Content = 'Changed on Stage';
		$page->write();
		$page->flushCache();
		
		$actions = $page->getCMSActions();
		$this->assertNotNull($actions->dataFieldByName('action_save'));
		$this->assertNotNull($actions->dataFieldByName('action_publish'));
		$this->assertNotNull($actions->dataFieldByName('action_unpublish'));
		$this->assertNotNull($actions->dataFieldByName('action_archive'));
		$this->assertNull($actions->dataFieldByName('action_deletefromlive'));
		$this->assertNotNull($actions->dataFieldByName('action_rollback'));
		$this->assertNull($actions->dataFieldByName('action_revert'));
	}

	public function testActionsViewingOldVersion() {
		$p = new Page();
		$p->Content = 'test page first version';
		$p->write();
		$p->Content = 'new content';
		$p->write();

		// Looking at the old version, the ability to rollback to that version is available
		$version = DB::query('SELECT "Version" FROM "SiteTree_versions" WHERE "Content" = \'test page first version\'')->value();
		$old = Versioned::get_version('Page', $p->ID, $version);
		$actions = $old->getCMSActions();
		$this->assertNull($actions->dataFieldByName('action_save'));
		$this->assertNull($actions->dataFieldByName('action_publish'));
		$this->assertNull($actions->dataFieldByName('action_unpublish'));
		$this->assertNull($actions->dataFieldByName('action_archive'));
		$this->assertNotNull($actions->dataFieldByName('action_email'));
		$this->assertNotNull($actions->dataFieldByName('action_rollback'));
	}

}

class SiteTreeActionsTest_Page extends Page implements TestOnly {
	public function canEdit($member = null) {
		return Permission::checkMember($member, 'SiteTreeActionsTest_Page_CANEDIT');
	}
	
	public function canDelete($member = null) {
		return Permission::checkMember($member, 'SiteTreeActionsTest_Page_CANDELETE');
	}
}
