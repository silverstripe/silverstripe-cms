<?php
/**
 * @package cms
 * @subpackage tests
 */
class CMSMainTest extends FunctionalTest {
	static $fixture_file = 'cms/tests/CMSMainTest.yml';
	
	protected $autoFollowRedirection = false;
	
	static protected $orig = array();
		
	static function set_up_once() {
		self::$orig['CMSBatchActionHandler_batch_actions'] = CMSBatchActionHandler::$batch_actions;
		CMSBatchActionHandler::$batch_actions = array(
			'publish' => 'CMSBatchAction_Publish',
			'delete' => 'CMSBatchAction_Delete',
			'deletefromlive' => 'CMSBatchAction_DeleteFromLive',
		);
		
		parent::set_up_once();
	}
	
	static function tear_down_once() {
		CMSBatchActionHandler::$batch_actions = self::$orig['CMSBatchActionHandler_batch_actions'];
		
		parent::tear_down_once();
	}
	
	/**
	 * @todo Test the results of a publication better
	 */
	function testPublish() {
		$page1 = $this->objFromFixture('Page', "page1");
		$page2 = $this->objFromFixture('Page', "page2");
		$this->session()->inst_set('loggedInAs', $this->idFromFixture('Member', 'admin'));
		
		$response = $this->get("admin/cms/publishall?confirm=1");
		
		$this->assertContains(
			sprintf(_t('CMSMain.PUBPAGES',"Done: Published %d pages"), 30), 
			$response->getBody()
		);
		
		// Some modules (e.g., cmsworkflow) will remove this action
		if(isset(CMSBatchActionHandler::$batch_actions['publish'])) {
			$response = $this->post(
				"admin/cms/batchactions/publish", 
				array(
					'csvIDs' => implode(',', array($page1->ID, $page2->ID)), 
					'ajax' => 1
				)
			);
	
			$this->assertContains(sprintf('setNodeTitle(%d, \'Page 1\');', $page1->ID), $response->getBody());
			$this->assertContains(sprintf('setNodeTitle(%d, \'Page 2\');', $page2->ID), $response->getBody());
		}

		// Get the latest version of the redirector page 
		$pageID = $this->idFromFixture('RedirectorPage', 'page5');
		$latestID = DB::query('select max("Version") from "RedirectorPage_versions" where "RecordID"=' . $pageID)->value(); 
		$dsCount = DB::query('select count("Version") from "RedirectorPage_versions" where "RecordID"=' . $pageID . ' and "Version"=' . $latestID)->value(); 
		$this->assertEquals(1, $dsCount, "Published page has no duplicate version records: it has " . $dsCount . " for version " . $latestID); 
		
		$this->session()->clear('loggedInAs');
		
		//$this->assertRegexp('/Done: Published 4 pages/', $response->getBody())
			
		/*
		$response = Director::test("admin/publishitems", array(
			'ID' => ''
			'Title' => ''
			'action_publish' => 'Save and publish',
		), $session);
		$this->assertRegexp('/Done: Published 4 pages/', $response->getBody())
		*/
	}
	
	/**
	 * Test publication of one of every page type
	 */
	function testPublishOneOfEachKindOfPage() {
		return;
		$classes = ClassInfo::subclassesFor("SiteTree");
		array_shift($classes);

		foreach($classes as $class) {
			$page = new $class();
			if($class instanceof TestOnly) continue;
			
			$page->Title = "Test $class page";
			
			$page->write();
			$this->assertEquals("Test $class page", DB::query("SELECT \"Title\" FROM \"SiteTree\" WHERE \"ID\" = $page->ID")->value());
			
			$page->doPublish();
			$this->assertEquals("Test $class page", DB::query("SELECT \"Title\" FROM \"SiteTree_Live\" WHERE \"ID\" = $page->ID")->value());
			
			// Check that you can visit the page
			$this->get($page->URLSegment);
		}
	}

	/**
	 * Test that getCMSFields works on each page type.
	 * Mostly, this is just checking that the method doesn't return an error
	 */
	function testThatGetCMSFieldsWorksOnEveryPageType() {
		$classes = ClassInfo::subclassesFor("SiteTree");
		array_shift($classes);

		foreach($classes as $class) {
			$page = new $class();
			if($page instanceof TestOnly) continue;

			$page->Title = "Test $class page";
			$page->write();
			$page->flushCache();
			$page = DataObject::get_by_id("SiteTree", $page->ID);
			
			$this->assertTrue($page->getCMSFields(null) instanceof FieldSet);
		}
	}
	
	function testCanPublishPageWithUnpublishedParentWithStrictHierarchyOff() {
		$this->logInWithPermission('ADMIN');
		
		SiteTree::set_enforce_strict_hierarchy(true);
		$parentPage = $this->objFromFixture('Page','page3');
		$childPage = $this->objFromFixture('Page','page1');
		
		$parentPage->doUnpublish();
		$childPage->doUnpublish();

		$this->assertContains(
			'action_publish',
			$childPage->getCMSActions()->column('Name'),
			'Can publish a page with an unpublished parent with strict hierarchy off'
		);
		SiteTree::set_enforce_strict_hierarchy(false);
	}	

	/**
	 * Test that a draft-deleted page can still be opened in the CMS
	 */
	function testDraftDeletedPageCanBeOpenedInCMS() {
		$this->session()->inst_set('loggedInAs', $this->idFromFixture('Member', 'admin'));

		// Set up a page that is delete from live
		$page = $this->objFromFixture('Page','page1');
		$pageID = $page->ID;
		$page->doPublish();
		$page->delete();
		
		$response = $this->get('admin/cms/getitem?ID=' . $pageID . '&ajax=1');

		$livePage = Versioned::get_one_by_stage("SiteTree", "Live", "\"SiteTree\".\"ID\" = $pageID");
		$this->assertType('SiteTree', $livePage);
		$this->assertTrue($livePage->canDelete());

		// Check that the 'restore' button exists as a simple way of checking that the correct page is returned.
		$this->assertRegExp('/<input[^>]+type="submit"[^>]+name="action_(restore|revert)"/i', $response->getBody());
	}
	
	/**
	 * Test CMSMain::getRecord()
	 */
	function testGetRecord() {
		// Set up a page that is delete from live
		$page1 = $this->objFromFixture('Page','page1');
		$page1ID = $page1->ID;
		$page1->doPublish();
		$page1->delete();
		
		$cmsMain = new CMSMain();

		// Bad calls
		$this->assertNull($cmsMain->getRecord('0'));
		$this->assertNull($cmsMain->getRecord('asdf'));
		
		// Pages that are on draft and aren't on draft should both work
		$this->assertType('Page', $cmsMain->getRecord($page1ID));
		$this->assertType('Page', $cmsMain->getRecord($this->idFromFixture('Page','page2')));

		// This functionality isn't actually used any more.
		$newPage = $cmsMain->getRecord('new-Page-5');
		$this->assertType('Page', $newPage);
		$this->assertEquals('5', $newPage->ParentID);
	}

	function testGetVersionRecord() {
		$cmsMain = new CMSMain();
		$page1 = $this->objFromFixture('Page', 'page1');
		$page1->Content = 'this is the old content';
		$page1->write();
		$page1->Content = 'this is new content (new version)';
		$page1->write();
		$versionID = DB::query('SELECT "Version" FROM "SiteTree_versions" WHERE "Content" = \'this is the old content\'')->value();
		$_REQUEST['Version'] = $versionID;
		$this->assertEquals($cmsMain->getRecord($page1->ID)->Version, $versionID);
		$this->assertEquals($cmsMain->getRecord($page1->ID)->Content, 'this is the old content');
		unset($_REQUEST['Version']);
	}

	function testDeletedPagesSiteTreeFilter() {
		$id = $this->idFromFixture('Page', 'page3');
		$this->logInWithPermission('ADMIN');
		$result = $this->get('admin/getfilteredsubtree?filter=CMSSiteTreeFilter_DeletedPages&ajax=1&ID=' . $id);
		$this->assertEquals(200, $result->getStatusCode());
	}
	
	function testCreationOfTopLevelPage(){
		$cmsUser = $this->objFromFixture('Member', 'allcmssectionsuser');
		$rootEditUser = $this->objFromFixture('Member', 'rootedituser');

		// with insufficient permissions
		$cmsUser->logIn();
		$response = $this->get('admin');
		$response = $this->submitForm(
			'Form_AddPageOptionsForm', 
			null,
			array('ParentID' => '0', 'PageType' => 'Page', 'Locale' => 'en_US')
		);
		// should redirect, which is a permission error
		$this->assertEquals(403, $response->getStatusCode(), 'Add TopLevel page must fail for normal user');

		// with correct permissions
		$rootEditUser->logIn();
		$response = $this->get('admin');
		$response = $this->submitForm(
			'Form_AddPageOptionsForm', 
			null,
			array('ParentID' => '0', 'PageType' => 'Page', 'Locale' => 'en_US')
		);
		$this->assertEquals(302, $response->getStatusCode(), 'Must be a redirect on success');
		$location=$response->getHeader('Location');
		$this->assertContains('/show/',$location, 'Must redirect to /show/ the new page');
		// TODO Logout
		$this->session()->inst_set('loggedInAs', NULL);
	}
}
