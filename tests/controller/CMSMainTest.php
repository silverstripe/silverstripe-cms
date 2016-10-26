<?php

use SilverStripe\ORM\DB;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ValidationException;
use SilverStripe\ORM\Versioning\Versioned;
use SilverStripe\ORM\HiddenClass;
use SilverStripe\CMS\Controllers\CMSMain;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Admin\CMSBatchActionHandler;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Core\Cache;
use SilverStripe\Core\Convert;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\CSSContentParser;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Dev\FunctionalTest;




/**
 * @package cms
 * @subpackage tests
 */
class CMSMainTest extends FunctionalTest {

	protected static $fixture_file = 'CMSMainTest.yml';

	static protected $orig = array();

	public function setUp() {
		parent::setUp();

		// Clear automatically created siteconfigs (in case one was created outside of the specified fixtures).
		$ids = $this->allFixtureIDs(SiteConfig::class);
		if($ids) {
			foreach(SiteConfig::get()->exclude('ID', $ids) as $config) {
				$config->delete();
			}
		}
	}

	function testSiteTreeHints() {
		$cache = Cache::factory('CMSMain_SiteTreeHints');
		// Login as user with root creation privileges
		$user = $this->objFromFixture('SilverStripe\\Security\\Member', 'rootedituser');
		$user->logIn();
		$cache->clean(Zend_Cache::CLEANING_MODE_ALL);

		$rawHints = singleton('SilverStripe\\CMS\\Controllers\\CMSMain')->SiteTreeHints();
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

	}

	public function testChildFilter() {
		$this->logInWithPermission('ADMIN');

		// Check page A
		$pageA = new CMSMainTest_ClassA();
		$pageA->write();
		$pageB = new CMSMainTest_ClassB();
		$pageB->write();

		// Check query
		$response = $this->get('admin/pages/childfilter?ParentID=' . $pageA->ID);
		$children = json_decode($response->getBody());
		$this->assertFalse($response->isError());

		// Page A can't have unrelated children
		$this->assertContains(
				'Page',
				$children,
				'Limited parent lists disallowed classes'
		);

		// But it can create a ClassB
		$this->assertNotContains(
				'CMSMainTest_ClassB',
				$children,
				'Limited parent omits explicitly allowed classes in disallowedChildren'
		);
	}

	/**
	 * @todo Test the results of a publication better
	 */
	public function testPublish() {
		$page1 = $this->objFromFixture(Page::class, "page1");
		$page2 = $this->objFromFixture(Page::class, "page2");
		$this->session()->inst_set('loggedInAs', $this->idFromFixture('SilverStripe\\Security\\Member', 'admin'));

		$response = $this->get('admin/pages/publishall?confirm=1');
		$this->assertContains(
				'Done: Published 30 pages',
				$response->getBody()
		);

		// Some modules (e.g., cmsworkflow) will remove this action
		$actions = CMSBatchActionHandler::config()->batch_actions;
		if (isset($actions['publish'])) {
			$response = $this->get('admin/pages/batchactions/publish?ajax=1&csvIDs=' . implode(',', array($page1->ID, $page2->ID)));
			$responseData = Convert::json2array($response->getBody());
			$this->assertArrayHasKey($page1->ID, $responseData['modified']);
			$this->assertArrayHasKey($page2->ID, $responseData['modified']);
		}

		// Get the latest version of the redirector page
		$pageID = $this->idFromFixture('SilverStripe\\CMS\\Model\\RedirectorPage', 'page5');
		$latestID = DB::prepared_query('select max("Version") from "RedirectorPage_Versions" where "RecordID" = ?', array($pageID))->value();
		$dsCount = DB::prepared_query('select count("Version") from "RedirectorPage_Versions" where "RecordID" = ? and "Version"= ?', array($pageID, $latestID))->value();
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

		// 	$page->publishRecursive();
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
		$classes = ClassInfo::subclassesFor("SilverStripe\\CMS\\Model\\SiteTree");
		array_shift($classes);

		foreach ($classes as $class) {
			$page = new $class();
			if ($page instanceof TestOnly) continue;
			if (!$page->stat('can_be_root')) continue;

			$page->Title = "Test $class page";
			$page->write();
			$page->flushCache();
			$page = DataObject::get_by_id("SilverStripe\\CMS\\Model\\SiteTree", $page->ID);

			$this->assertTrue($page->getCMSFields() instanceof FieldList);
		}
	}

	public function testCanPublishPageWithUnpublishedParentWithStrictHierarchyOff() {
		$this->logInWithPermission('ADMIN');

		Config::inst()->update('SilverStripe\\CMS\\Model\\SiteTree', 'enforce_strict_hierarchy', true);
		$parentPage = $this->objFromFixture(Page::class, 'page3');
		$childPage = $this->objFromFixture(Page::class, 'page1');

		$parentPage->doUnpublish();
		$childPage->doUnpublish();

		$actions = $childPage->getCMSActions()->dataFields();
		$this->assertArrayHasKey(
				'action_publish',
				$actions,
				'Can publish a page with an unpublished parent with strict hierarchy off'
		);
		Config::inst()->update('SilverStripe\\CMS\\Model\\SiteTree', 'enforce_strict_hierarchy', false);
	}

	/**
	 * Test that a draft-deleted page can still be opened in the CMS
	 */
	public function testDraftDeletedPageCanBeOpenedInCMS() {
		$this->session()->inst_set('loggedInAs', $this->idFromFixture('SilverStripe\\Security\\Member', 'admin'));

		// Set up a page that is delete from live
		$page = $this->objFromFixture(Page::class, 'page1');
		$pageID = $page->ID;
		$page->publishRecursive();
		$page->delete();

		$response = $this->get('admin/pages/edit/show/' . $pageID);

		$livePage = Versioned::get_one_by_stage("SilverStripe\\CMS\\Model\\SiteTree", "Live", array(
				'"SiteTree"."ID"' => $pageID
		));
		$this->assertInstanceOf('SilverStripe\\CMS\\Model\\SiteTree', $livePage);
		$this->assertTrue($livePage->canDelete());

		// Check that the 'restore' button exists as a simple way of checking that the correct page is returned.
		$this->assertRegExp('/<button[^>]+name="action_(restore|revert)"/i', $response->getBody());
	}

	/**
	 * Test CMSMain::getRecord()
	 */
	public function testGetRecord() {
		$this->logInWithPermission('ADMIN');

		// Set up a page that is delete from live
		$page1 = $this->objFromFixture(Page::class, 'page1');
		$page1ID = $page1->ID;
		$page1->publishRecursive();
		$page1->delete();

		$cmsMain = new CMSMain();

		// Bad calls
		$this->assertNull($cmsMain->getRecord('0'));
		$this->assertNull($cmsMain->getRecord('asdf'));

		// Pages that are on draft and aren't on draft should both work
		$this->assertInstanceOf('Page', $cmsMain->getRecord($page1ID));
		$this->assertInstanceOf('Page', $cmsMain->getRecord($this->idFromFixture('Page', 'page2')));

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

	public function testCreationOfTopLevelPage() {
		$origFollow = $this->autoFollowRedirection;
		$this->autoFollowRedirection = false;

		$cmsUser = $this->objFromFixture('SilverStripe\\Security\\Member', 'allcmssectionsuser');
		$rootEditUser = $this->objFromFixture('SilverStripe\\Security\\Member', 'rootedituser');

		// with insufficient permissions
		$cmsUser->logIn();
		$this->get('admin/pages/add');
		$response = $this->post(
			'admin/pages/add/AddForm',
			array(
				'ParentID' => '0',
				'PageType' => 'Page',
				'Locale' => 'en_US',
				'action_doAdd' => 1,
				'ajax' => 1,
			), array(
				'X-Pjax' => 'CurrentForm,Breadcrumbs',
			)
		);
		// should redirect, which is a permission error
		$this->assertEquals(403, $response->getStatusCode(), 'Add TopLevel page must fail for normal user');

		// with correct permissions
		$rootEditUser->logIn();
		$response = $this->get('admin/pages/add');

		$response = $this->post(
			'admin/pages/add/AddForm',
			array(
				'ParentID' => '0',
				'PageType' => 'Page',
				'Locale' => 'en_US',
				'action_doAdd' => 1,
				'ajax' => 1,
			), array(
				'X-Pjax' => 'CurrentForm,Breadcrumbs',
			)
		);

		$location = $response->getHeader('X-ControllerURL');
		$this->assertNotEmpty($location, 'Must be a redirect on success');
		$this->assertContains('/show/', $location, 'Must redirect to /show/ the new page');
		// TODO Logout
		$this->session()->inst_set('loggedInAs', NULL);

		$this->autoFollowRedirection = $origFollow;
	}

	public function testCreationOfRestrictedPage() {
		$origFollow = $this->autoFollowRedirection;
		$this->autoFollowRedirection = false;

		$adminUser = $this->objFromFixture('SilverStripe\\Security\\Member', 'admin');
		$adminUser->logIn();

		// Create toplevel page
		$this->get('admin/pages/add');
		$response = $this->post(
			'admin/pages/add/AddForm',
			array(
				'ParentID' => '0',
				'PageType' => 'CMSMainTest_ClassA',
				'Locale' => 'en_US',
				'action_doAdd' => 1,
				'ajax' => 1
			), array(
				'X-Pjax' => 'CurrentForm,Breadcrumbs',
			)
		);
		$this->assertFalse($response->isError());
		$ok = preg_match('/edit\/show\/(\d*)/', $response->getHeader('X-ControllerURL'), $matches);
		$this->assertNotEmpty($ok);
		$newPageId = $matches[1];

		// Create allowed child
		$this->get('admin/pages/add');
		$response = $this->post(
			'admin/pages/add/AddForm',
			array(
				'ParentID' => $newPageId,
				'PageType' => 'CMSMainTest_ClassB',
				'Locale' => 'en_US',
				'action_doAdd' => 1,
				'ajax' => 1
			), array(
				'X-Pjax' => 'CurrentForm,Breadcrumbs',
			)
		);
		$this->assertFalse($response->isError());
		$this->assertEmpty($response->getBody());

		// Verify that the page was created and redirected to accurately
		$newerPage = SiteTree::get()->byID($newPageId)->AllChildren()->first();
		$this->assertNotEmpty($newerPage);
		$ok = preg_match('/edit\/show\/(\d*)/', $response->getHeader('X-ControllerURL'), $matches);
		$this->assertNotEmpty($ok);
		$newerPageID = $matches[1];
		$this->assertEquals($newerPage->ID, $newerPageID);

		// Create disallowed child
		$this->get('admin/pages/add');
		$response = $this->post(
			'admin/pages/add/AddForm',
			array(
				'ParentID' => $newPageId,
				'PageType' => 'Page',
				'Locale' => 'en_US',
				'action_doAdd' => 1,
				'ajax' => 1
			), array(
				'X-Pjax' => 'CurrentForm,Breadcrumbs',
			)
		);
		$this->assertEquals(403, $response->getStatusCode(), 'Add disallowed child should fail');

		$this->session()->inst_set('loggedInAs', NULL);

		$this->autoFollowRedirection = $origFollow;
	}

	public function testBreadcrumbs() {
		$page3 = $this->objFromFixture(Page::class, 'page3');
		$page31 = $this->objFromFixture(Page::class, 'page31');
		$adminuser = $this->objFromFixture('SilverStripe\\Security\\Member', 'admin');
		$this->session()->inst_set('loggedInAs', $adminuser->ID);

		$response = $this->get('admin/pages/edit/show/' . $page31->ID);
		$parser = new CSSContentParser($response->getBody());
		$crumbs = $parser->getBySelector('.breadcrumbs-wrapper .crumb');

		$this->assertNotNull($crumbs);
		$this->assertEquals(2, count($crumbs));
		$this->assertEquals('Page 3', (string)$crumbs[0]);
		$this->assertEquals('Page 3.1', (string)$crumbs[1]);

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
		} catch (HTTPResponse_Exception $e) {
			$this->assertEquals($controller->getResponse()->getStatusCode(), 302);
		}
	}

	/**
	 * Tests filtering in {@see CMSMain::getList()}
	 */
	public function testGetList() {
		$controller = new CMSMain();

		// Test all pages (stage)
		$pages = $controller->getList()->sort('Title');
		$this->assertEquals(28, $pages->count());
		$this->assertEquals(
				array('Home', 'Page 1', 'Page 10', 'Page 11', 'Page 12'),
				$pages->Limit(5)->column('Title')
		);

		// Change state of tree
		$page1 = $this->objFromFixture(Page::class, 'page1');
		$page3 = $this->objFromFixture(Page::class, 'page3');
		$page11 = $this->objFromFixture(Page::class, 'page11');
		$page12 = $this->objFromFixture(Page::class, 'page12');
		// Deleted
		$page1->doUnpublish();
		$page1->delete();
		// Live and draft
		$page11->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
		// Live only
		$page12->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
		$page12->delete();

		// Re-test all pages (stage)
		$pages = $controller->getList()->sort('Title');
		$this->assertEquals(26, $pages->count());
		$this->assertEquals(
				array('Home', 'Page 10', 'Page 11', 'Page 13', 'Page 14'),
				$pages->Limit(5)->column('Title')
		);

		// Test deleted page filter
		$params = array(
				'FilterClass' => 'SilverStripe\\CMS\\Controllers\\CMSSiteTreeFilter_StatusDeletedPages'
		);
		$pages = $controller->getList($params);
		$this->assertEquals(1, $pages->count());
		$this->assertEquals(
				array('Page 1'),
				$pages->column('Title')
		);

		// Test live, but not on draft filter
		$params = array(
			'FilterClass' => 'SilverStripe\\CMS\\Controllers\\CMSSiteTreeFilter_StatusRemovedFromDraftPages'
		);
		$pages = $controller->getList($params);
		$this->assertEquals(1, $pages->count());
		$this->assertEquals(
				array('Page 12'),
				$pages->column('Title')
		);

		// Test live pages filter
		$params = array(
			'FilterClass' => 'SilverStripe\\CMS\\Controllers\\CMSSiteTreeFilter_PublishedPages'
		);
		$pages = $controller->getList($params);
		$this->assertEquals(2, $pages->count());
		$this->assertEquals(
				array('Page 11', 'Page 12'),
				$pages->column('Title')
		);

		// Test that parentID is ignored when filtering
		$pages = $controller->getList($params, $page3->ID);
		$this->assertEquals(2, $pages->count());
		$this->assertEquals(
				array('Page 11', 'Page 12'),
				$pages->column('Title')
		);

		// Test that parentID is respected when not filtering
		$pages = $controller->getList(array(), $page3->ID);
		$this->assertEquals(2, $pages->count());
		$this->assertEquals(
				array('Page 3.1', 'Page 3.2'),
				$pages->column('Title')
		);
	}

	/**
	 * Testing retrieval and type of CMS edit form.
	 */
	public function testGetEditForm() {
		// Login is required prior to accessing a CMS form.
		$this->loginWithPermission('ADMIN');

		// Get a associated with a fixture page.
		$page = $this->objFromFixture(Page::class, 'page1');
		$controller = new CMSMain();
		$form = $controller->getEditForm($page->ID);
		$this->assertInstanceOf("SilverStripe\\Forms\\Form", $form);

		// Ensure that the form will not "validate" on delete or "unpublish" actions.
		$exemptActions = $form->getValidationExemptActions();
		$this->assertContains("delete", $exemptActions);
		$this->assertContains("unpublish", $exemptActions);
	}

	/**
	 * Test that changed classes save with the correct class name
	 */
	public function testChangeClass() {
		$this->logInWithPermission('ADMIN');
		$cms = new CMSMain();
		$page = new CMSMainTest_ClassA();
		$page->Title = 'Class A';
		$page->write();

		$form = $cms->getEditForm($page->ID);
		$form->loadDataFrom(['ClassName' => 'CMSMainTest_ClassB']);
		$result = $cms->save([
			'ID' => $page->ID,
			'ClassName' => 'CMSMainTest_ClassB'
		], $form);
		$this->assertEquals(200, $result->getStatusCode());

		$newPage = SiteTree::get()->byID($page->ID);

		$this->assertInstanceOf('CMSMainTest_ClassB', $newPage);
		$this->assertEquals('CMSMainTest_ClassB', $newPage->ClassName);
		$this->assertEquals('Class A', $newPage->Title);

	}
}

class CMSMainTest_ClassA extends Page implements TestOnly {
	private static $allowed_children = array('CMSMainTest_ClassB');

	protected function onBeforeWrite()
	{
		parent::onBeforeWrite();

		if ($this->ClassName !== self::class) {
			throw new ValidationException("Class saved with incorrect ClassName");
		}
	}
}

class CMSMainTest_ClassB extends Page implements TestOnly {

	protected function onBeforeWrite()
	{
		parent::onBeforeWrite();

		if ($this->ClassName !== self::class) {
			throw new ValidationException("Class saved with incorrect ClassName");
		}
	}

}

class CMSMainTest_NotRoot extends Page implements TestOnly {
	private static $can_be_root = false;
}

class CMSMainTest_HiddenClass extends Page implements TestOnly, HiddenClass {

}
