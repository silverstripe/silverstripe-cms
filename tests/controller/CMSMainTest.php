<?php
/**
 * @package cms
 * @subpackage tests
 */
class CMSMainTest extends FunctionalTest {

	protected static $fixture_file = 'CMSMainTest.yml';
	
	static protected $orig = array();
	
	function testSiteTreeHints() {
		$cache = SS_Cache::factory('CMSMain_SiteTreeHints');
		$cache->clean(Zend_Cache::CLEANING_MODE_ALL);

		$rawHints = singleton('CMSMain')->SiteTreeHints();
		$this->assertNotNull($rawHints);

		$rawHints = preg_replace('/^"(.*)"$/', '$1', Convert::xml2raw($rawHints));
		$hints = Convert::json2array($rawHints);

		$this->assertArrayHasKey('Root', $hints);
		$this->assertArrayHasKey('Page', $hints);
		$this->assertArrayHasKey('All', $hints);

		$this->assertArrayHasKey(
			'CMSMainTest_ClassA',
			$hints['All'],
			'Global list shows allowed classes'
		);

		$this->assertArrayNotHasKey(
			'CMSMainTest_HiddenClass',
			$hints['All'],
			'Global list does not list hidden classes'
		);

		$this->assertNotContains(
			'CMSMainTest_ClassA',
			$hints['Root']['disallowedChildren'],
			'Limits root classes'
		);

		$this->assertContains(
			'CMSMainTest_NotRoot',
			$hints['Root']['disallowedChildren'],
			'Limits root classes'
		);
		$this->assertNotContains(
			'CMSMainTest_ClassA',
			// Lenient checks because other modules might influence state
			(array)@$hints['Page']['disallowedChildren'],
			'Does not limit types on unlimited parent'
		);
		$this->assertContains(
			'Page',
			$hints['CMSMainTest_ClassA']['disallowedChildren'], 
			'Limited parent lists disallowed classes'
		);
		$this->assertNotContains(
			'CMSMainTest_ClassB',
			$hints['CMSMainTest_ClassA']['disallowedChildren'], 
			'Limited parent omits explicitly allowed classes in disallowedChildren'
		);
		
	}
	
	/**
	 * @todo Test the results of a publication better
	 */
	public function testPublish() {
		$page1 = $this->objFromFixture('Page', "page1");
		$page2 = $this->objFromFixture('Page', "page2");
		$this->session()->inst_set('loggedInAs', $this->idFromFixture('Member', 'admin'));
		
		$response = $this->get('admin/pages/publishall?confirm=1');
		$this->assertContains(
			'Done: Published 30 pages',
			$response->getBody()
		);

		$actions = CMSBatchActionHandler::config()->batch_actions;
	
		// Some modules (e.g., cmsworkflow) will remove this action
		$actions = CMSBatchActionHandler::config()->batch_actions;
		if(isset($actions['publish'])) {
			$response = $this->get('admin/pages/batchactions/publish?ajax=1&csvIDs=' . implode(',', array($page1->ID, $page2->ID)));
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
	public function testPublishOneOfEachKindOfPage() {
		$this->markTestIncomplete();

		// $classes = ClassInfo::subclassesFor("SiteTree");
		// array_shift($classes);
	
		// foreach($classes as $class) {
		// 	$page = new $class();
		// 	if($class instanceof TestOnly) continue;
			
		// 	$page->Title = "Test $class page";
			
		// 	$page->write();
		// 	$this->assertEquals("Test $class page", DB::query("SELECT \"Title\" FROM \"SiteTree\" WHERE \"ID\" = $page->ID")->value());
			
		// 	$page->doPublish();
		// 	$this->assertEquals("Test $class page", DB::query("SELECT \"Title\" FROM \"SiteTree_Live\" WHERE \"ID\" = $page->ID")->value());
			
		// 	// Check that you can visit the page
		// 	$this->get($page->URLSegment);
		// }
	}
	
	/**
	 * Test that getCMSFields works on each page type.
	 * Mostly, this is just checking that the method doesn't return an error
	 */
	public function testThatGetCMSFieldsWorksOnEveryPageType() {
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
	
	public function testCanPublishPageWithUnpublishedParentWithStrictHierarchyOff() {
		$this->logInWithPermission('ADMIN');
		
		Config::inst()->update('SiteTree', 'enforce_strict_hierarchy', true);
		$parentPage = $this->objFromFixture('Page','page3');
		$childPage = $this->objFromFixture('Page','page1');
		
		$parentPage->doUnpublish();
		$childPage->doUnpublish();

		$actions = $childPage->getCMSActions()->dataFields();
		$this->assertArrayHasKey(
			'action_publish',
			$actions,
			'Can publish a page with an unpublished parent with strict hierarchy off'
		);
		Config::inst()->update('SiteTree', 'enforce_strict_hierarchy', false);
	}	
	
	/**
	 * Test that a draft-deleted page can still be opened in the CMS
	 */
	public function testDraftDeletedPageCanBeOpenedInCMS() {
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
	public function testGetRecord() {
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
	
	public function testDeletedPagesSiteTreeFilter() {
		$id = $this->idFromFixture('Page', 'page3');
		$this->logInWithPermission('ADMIN');
		$result = $this->get('admin/pages/getsubtree?filter=CMSSiteTreeFilter_DeletedPages&ajax=1&ID=' . $id);
		$this->assertEquals(200, $result->getStatusCode());
	}
	
	public function testCreationOfTopLevelPage(){
		$origFollow = $this->autoFollowRedirection;
		$this->autoFollowRedirection = false;

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

		$this->autoFollowRedirection = $origFollow;
	}

	public function testCreationOfRestrictedPage(){
		$origFollow = $this->autoFollowRedirection;
		$this->autoFollowRedirection = false;

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
			htmlentities(_t('SiteTree.PageTypeNotAllowed', array('type' => 'Page'))),
			$response->getBody()
		);

		$this->session()->inst_set('loggedInAs', NULL);

		$this->autoFollowRedirection = $origFollow;
	}

	public function testBreadcrumbs() {
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

	public function testGetNewItem() {
		$controller = new CMSMain();
		$id = 'new-Page-0';

		// Test success
		$page = $controller->getNewItem($id, false);

		$this->assertEquals($page->Title, 'New Page');
		$this->assertNotEquals($page->Sort, 0);
		$this->assertInstanceOf('Page', $page);

		// Test failure
		try {
			$id = 'new-Member-0';
			$member = $controller->getNewItem($id, false);
			$this->fail('Should not be able to create a Member object');
		} catch(SS_HTTPResponse_Exception $e) {
			$this->assertEquals($controller->getResponse()->getStatusCode(), 302);
		}
	}
}

class CMSMainTest_ClassA extends Page implements TestOnly {
	private static $allowed_children = array('CMSMainTest_ClassB');
}

class CMSMainTest_ClassB extends Page implements TestOnly {
	
}

class CMSMainTest_NotRoot extends Page implements TestOnly {
	private static $can_be_root = false;
}

class CMSMainTest_HiddenClass extends Page implements TestOnly, HiddenClass {
	
}
