<?php
/**
 * @package cms
 * @subpackage tests
 */
class CMSMainTest extends FunctionalTest {

	static $fixture_file = 'CMSMainTest.yml';
	
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
		
		$response = $this->get('admin/pages/publishall?confirm=1');

		$this->assertContains(
			'Done: Published 30 pages',
			$response->getBody()
		);
	
		// Some modules (e.g., cmsworkflow) will remove this action
		if(isset(CMSBatchActionHandler::$batch_actions['publish'])) {
			$response = Director::test('admin/pages/batchactions/publish', array('csvIDs' => implode(',', array($page1->ID, $page2->ID)), 'ajax' => 1), $this->session());
			$responseData = Convert::json2array($response->getBody());
			$this->assertArrayHasKey($page1->ID, $responseData['modified']);
			$this->assertArrayHasKey($page2->ID, $responseData['modified']);
		}

		// Get the latest version of the redirector page 
		$pageID = $this->idFromFixture('RedirectorPage', 'page5');
		$latestID = DB::query('select max("Version") from "RedirectorPage_versions" where "RecordID"=' . $pageID)->value(); 
		$dsCount = DB::query('select count("Version") from "RedirectorPage_versions" where "RecordID"=' . $pageID . ' and "Version"=' . $latestID)->value(); 
		$this->assertEquals(1, $dsCount, "Published page has no duplicate version records: it has " . $dsCount . " for version " . $latestID); 
		
		$this->session()->clear('loggedInAs');
		
		//$this->assertRegexp('/Done: Published 4 pages/', $response->getBody())
			
		/*
		$response = Director::test("admin/pages/publishitems", array(
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
			if(!$page->stat('can_be_root')) continue;
	
			$page->Title = "Test $class page";
			$page->write();
			$page->flushCache();
			$page = DataObject::get_by_id("SiteTree", $page->ID);
			
			$this->assertTrue($page->getCMSFields() instanceof FieldList);
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
		
		$response = $this->get('admin/pages/edit/show/' . $pageID);
	
		$livePage = Versioned::get_one_by_stage("SiteTree", "Live", "\"SiteTree\".\"ID\" = $pageID");
		$this->assertInstanceOf('SiteTree', $livePage);
		$this->assertTrue($livePage->canDelete());
	
		// Check that the 'restore' button exists as a simple way of checking that the correct page is returned.
		$this->assertRegExp('/<button[^>]+name="action_(restore|revert)"/i', $response->getBody());
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
		$this->assertInstanceOf('Page', $cmsMain->getRecord($page1ID));
		$this->assertInstanceOf('Page', $cmsMain->getRecord($this->idFromFixture('Page','page2')));
	
		// This functionality isn't actually used any more.
		$newPage = $cmsMain->getRecord('new-Page-5');
		$this->assertInstanceOf('Page', $newPage);
		$this->assertEquals('5', $newPage->ParentID);
	
	}
	
	function testDeletedPagesSiteTreeFilter() {
		$id = $this->idFromFixture('Page', 'page3');
		$this->logInWithPermission('ADMIN');
		$result = $this->get('admin/pages/getsubtree?filter=CMSSiteTreeFilter_DeletedPages&ajax=1&ID=' . $id);
		$this->assertEquals(200, $result->getStatusCode());
	}
	
	function testCreationOfTopLevelPage(){
		$cmsUser = $this->objFromFixture('Member', 'allcmssectionsuser');
		$rootEditUser = $this->objFromFixture('Member', 'rootedituser');

		// with insufficient permissions
		$cmsUser->logIn();
		$this->get('admin/pages/add');
		$response = $this->post(
			'admin/pages/add/AddForm', 
			array('ParentID' => '0', 'ClassName' => 'Page', 'Locale' => 'en_US', 'action_doAdd' => 1)
		);
		// should redirect, which is a permission error
		$this->assertEquals(403, $response->getStatusCode(), 'Add TopLevel page must fail for normal user');

		// with correct permissions
		$rootEditUser->logIn();
		$response = $this->get('admin/pages/add');

		$response = $this->post(
			'admin/pages/add/AddForm', 
			array('ParentID' => '0', 'ClassName' => 'Page', 'Locale' => 'en_US', 'action_doAdd' => 1)
		);

		$this->assertEquals(302, $response->getStatusCode(), 'Must be a redirect on success');
		$location=$response->getHeader('Location');
		$this->assertContains('/show/',$location, 'Must redirect to /show/ the new page');
		// TODO Logout
		$this->session()->inst_set('loggedInAs', NULL);
	}

	function testCreationOfRestrictedPage(){
		$adminUser = $this->objFromFixture('Member', 'admin');
		$adminUser->logIn();

		// Create toplevel page
		$this->get('admin/pages/add');
		$response = $this->post(
			'admin/pages/add/AddForm', 
			array('ParentID' => '0', 'PageType' => 'CMSMainTest_ClassA', 'Locale' => 'en_US', 'action_doAdd' => 1)
		);
		$this->assertFalse($response->isError());
		preg_match('/edit\/show\/(\d*)/', $response->getHeader('Location'), $matches);
		$newPageId = $matches[1];

		// Create allowed child
		$this->get('admin/pages/add');
		$response = $this->post(
			'admin/pages/add/AddForm', 
			array('ParentID' => $newPageId, 'PageType' => 'CMSMainTest_ClassB', 'Locale' => 'en_US', 'action_doAdd' => 1)
		);
		$this->assertFalse($response->isError());
		$this->assertNull($response->getBody());

		// Create disallowed child
		$this->get('admin/pages/add');
		$response = $this->post(
			'admin/pages/add/AddForm', 
			array('ParentID' => $newPageId, 'PageType' => 'Page', 'Locale' => 'en_US', 'action_doAdd' => 1)
		);
		$this->assertFalse($response->isError());
		$this->assertContains(
			_t('SiteTree.PageTypeNotAllowed', array('type' => 'Page')),
			$response->getBody()
		);

		$this->session()->inst_set('loggedInAs', NULL);
	}

	function testBreadcrumbs() {
		$page3 = $this->objFromFixture('Page', 'page3');		
		$page31 = $this->objFromFixture('Page', 'page31');		
		$adminuser = $this->objFromFixture('Member', 'admin');
		$this->session()->inst_set('loggedInAs', $adminuser->ID);

		$response = $this->get('admin/pages/edit/show/' . $page31->ID);
		$parser = new CSSContentParser($response->getBody());
		$crumbs = $parser->getBySelector('.breadcrumbs-wrapper .crumb');

		$this->assertNotNull($crumbs);
		$this->assertEquals(3, count($crumbs));
		$this->assertEquals('Page 3', (string)$crumbs[1]);
		$this->assertEquals('Page 3.1', (string)$crumbs[2]);

		$this->session()->inst_set('loggedInAs', null);
	}
}

class CMSMainTest_ClassA extends Page implements TestOnly {
	static $allowed_children = array('CMSMainTest_ClassB');
}

class CMSMainTest_ClassB extends Page implements TestOnly {
	
}