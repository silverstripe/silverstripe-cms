<?php
/**
 * @package cms
 * @subpackage tests
 */
class SiteTreeTest extends SapphireTest {
	
	protected static $fixture_file = 'SiteTreeTest.yml';

	protected $illegalExtensions = array(
		'SiteTree' => array('SiteTreeSubsites', 'Translatable')
	);
	
	protected $extraDataObjects = array(
		'SiteTreeTest_ClassA',
		'SiteTreeTest_ClassB',
		'SiteTreeTest_ClassC',
		'SiteTreeTest_ClassD',
		'SiteTreeTest_ClassCext',
		'SiteTreeTest_NotRoot',
		'SiteTreeTest_StageStatusInherit',
	);

	/**
	 * Ensure any current member is logged out
	 */
	public function logOut() {
		if($member = Member::currentUser()) $member->logOut();
	}
	
	public function testCreateDefaultpages() {
			$remove = SiteTree::get();
			if($remove) foreach($remove as $page) $page->delete();
			// Make sure the table is empty
			$this->assertEquals(DB::query('SELECT COUNT("ID") FROM "SiteTree"')->value(), 0);

			// Disable the creation
			SiteTree::config()->create_default_pages = false;
			singleton('SiteTree')->requireDefaultRecords();

			// The table should still be empty
			$this->assertEquals(DB::query('SELECT COUNT("ID") FROM "SiteTree"')->value(), 0);

			// Enable the creation
			SiteTree::config()->create_default_pages = true;
			singleton('SiteTree')->requireDefaultRecords();

			// The table should now have three rows (home, about-us, contact-us)
			$this->assertEquals(DB::query('SELECT COUNT("ID") FROM "SiteTree"')->value(), 3);
	}

	/**
	 * Test generation of the URLSegment values.
	 *  - Turns things into lowercase-hyphen-format
	 *  - Generates from Title by default, unless URLSegment is explicitly set
	 *  - Resolves duplicates by appending a number
	 *  - renames classes with a class name conflict
	 */
	public function testURLGeneration() {
		$expectedURLs = array(
			'home' => 'home',
			'staff' => 'my-staff',
			'about' => 'about-us',
			'staffduplicate' => 'my-staff-2',
			'product1' => '1-1-test-product',
			'product2' => 'another-product',
			'product3' => 'another-product-2',
			'product4' => 'another-product-3',
			'object'   => 'object',
			'controller' => 'controller-2',
			'numericonly' => '1930',
		);

		foreach($expectedURLs as $fixture => $urlSegment) {
			$obj = $this->objFromFixture('Page', $fixture);
			$this->assertEquals($urlSegment, $obj->URLSegment);
		}
	}

	/**
	 * Test that publication copies data to SiteTree_Live
	 */
	public function testPublishCopiesToLiveTable() {
		$obj = $this->objFromFixture('Page','about');
		$obj->publish('Stage', 'Live');

		$createdID = DB::query("SELECT \"ID\" FROM \"SiteTree_Live\" WHERE \"URLSegment\" = '$obj->URLSegment'")->value();
		$this->assertEquals($obj->ID, $createdID);
	}

	/**
	 * Test that field which are set and then cleared are also transferred to the published site.
	 */
	public function testPublishDeletedFields() {
		$this->logInWithPermission('ADMIN');

		$obj = $this->objFromFixture('Page', 'about');
		$obj->Title = "asdfasdf";
		$obj->write();
		$this->assertTrue($obj->doPublish());

		$this->assertEquals('asdfasdf', DB::query("SELECT \"Title\" FROM \"SiteTree_Live\" WHERE \"ID\" = '$obj->ID'")->value());

		$obj->Title = null;
		$obj->write();
		$this->assertTrue($obj->doPublish());

		$this->assertNull(DB::query("SELECT \"Title\" FROM \"SiteTree_Live\" WHERE \"ID\" = '$obj->ID'")->value());

	}

	public function testParentNodeCachedInMemory() {
		$parent = new SiteTree();
		$parent->Title = 'Section Title';
		$child = new SiteTree();
		$child->Title = 'Page Title';
		$child->setParent($parent);

		$this->assertInstanceOf("SiteTree", $child->Parent);
		$this->assertEquals("Section Title", $child->Parent->Title);
	}

	public function testParentModelReturnType() {
		$parent = new SiteTreeTest_PageNode();
		$child = new SiteTreeTest_PageNode();

		$child->setParent($parent);
		$this->assertInstanceOf('SiteTreeTest_PageNode', $child->Parent);
	}

	/**
	 * Confirm that DataObject::get_one() gets records from SiteTree_Live
	 */
	public function testGetOneFromLive() {
		$s = new SiteTree();
		$s->Title = "V1";
		$s->URLSegment = "get-one-test-page";
		$s->write();
		$s->publish("Stage", "Live");
		$s->Title = "V2";
		$s->write();

		$oldMode = Versioned::get_reading_mode();
		Versioned::reading_stage('Live');

		$checkSiteTree = DataObject::get_one("SiteTree", array(
			'"SiteTree"."URLSegment"' => 'get-one-test-page'
		));
		$this->assertEquals("V1", $checkSiteTree->Title);

		Versioned::set_reading_mode($oldMode);
	}

	public function testChidrenOfRootAreTopLevelPages() {
		$pages = SiteTree::get();
		foreach($pages as $page) $page->publish('Stage', 'Live');
		unset($pages);

		/* If we create a new SiteTree object with ID = 0 */
		$obj = new SiteTree();
		/* Then its children should be the top-level pages */
		$stageChildren = $obj->stageChildren()->map('ID','Title');
		$liveChildren = $obj->liveChildren()->map('ID','Title');
		$allChildren = $obj->AllChildrenIncludingDeleted()->map('ID','Title');

		$this->assertContains('Home', $stageChildren);
		$this->assertContains('Products', $stageChildren);
		$this->assertNotContains('Staff', $stageChildren);

		$this->assertContains('Home', $liveChildren);
		$this->assertContains('Products', $liveChildren);
		$this->assertNotContains('Staff', $liveChildren);

		$this->assertContains('Home', $allChildren);
		$this->assertContains('Products', $allChildren);
		$this->assertNotContains('Staff', $allChildren);
	}

	public function testCanSaveBlankToHasOneRelations() {
		/* DataObject::write() should save to a has_one relationship if you set a field called (relname)ID */
		$page = new SiteTree();
		$parentID = $this->idFromFixture('Page', 'home');
		$page->ParentID = $parentID;
		$page->write();
		$this->assertEquals($parentID, DB::query("SELECT \"ParentID\" FROM \"SiteTree\" WHERE \"ID\" = $page->ID")->value());

		/* You should then be able to save a null/0/'' value to the relation */
		$page->ParentID = null;
		$page->write();
		$this->assertEquals(0, DB::query("SELECT \"ParentID\" FROM \"SiteTree\" WHERE \"ID\" = $page->ID")->value());
	}

	public function testStageStates() {
		// newly created page
		$createdPage = new SiteTree();
		$createdPage->write();
		$this->assertFalse($createdPage->getIsDeletedFromStage());
		$this->assertTrue($createdPage->getIsAddedToStage());
		$this->assertTrue($createdPage->getIsModifiedOnStage());

		// published page
		$publishedPage = new SiteTree();
		$publishedPage->write();
		$publishedPage->publish('Stage','Live');
		$this->assertFalse($publishedPage->getIsDeletedFromStage());
		$this->assertFalse($publishedPage->getIsAddedToStage());
		$this->assertFalse($publishedPage->getIsModifiedOnStage());

		// published page, deleted from stage
		$deletedFromDraftPage = new SiteTree();
		$deletedFromDraftPage->write();
		$deletedFromDraftPageID = $deletedFromDraftPage->ID;
		$deletedFromDraftPage->publish('Stage','Live');
		$deletedFromDraftPage->deleteFromStage('Stage');
		$this->assertTrue($deletedFromDraftPage->getIsDeletedFromStage());
		$this->assertFalse($deletedFromDraftPage->getIsAddedToStage());
		$this->assertFalse($deletedFromDraftPage->getIsModifiedOnStage());

		// published page, deleted from live
		$deletedFromLivePage = new SiteTree();
		$deletedFromLivePage->write();
		$deletedFromLivePage->publish('Stage','Live');
		$deletedFromLivePage->deleteFromStage('Stage');
		$deletedFromLivePage->deleteFromStage('Live');
		$this->assertTrue($deletedFromLivePage->getIsDeletedFromStage());
		$this->assertFalse($deletedFromLivePage->getIsAddedToStage());
		$this->assertFalse($deletedFromLivePage->getIsModifiedOnStage());

		// published page, modified
		$modifiedOnDraftPage = new SiteTree();
		$modifiedOnDraftPage->write();
		$modifiedOnDraftPage->publish('Stage','Live');
		$modifiedOnDraftPage->Content = 'modified';
		$modifiedOnDraftPage->write();
		$this->assertFalse($modifiedOnDraftPage->getIsDeletedFromStage());
		$this->assertFalse($modifiedOnDraftPage->getIsAddedToStage());
		$this->assertTrue($modifiedOnDraftPage->getIsModifiedOnStage());
	}

	/**
	 * Test that a page can be completely deleted and restored to the stage site
	 */
	public function testRestoreToStage() {
		$page = $this->objFromFixture('Page', 'about');
		$pageID = $page->ID;
		$page->delete();
		$this->assertTrue(!DataObject::get_by_id("Page", $pageID));

		$deletedPage = Versioned::get_latest_version('SiteTree', $pageID);
		$resultPage = $deletedPage->doRestoreToStage();

		$requeriedPage = DataObject::get_by_id("Page", $pageID);

		$this->assertEquals($pageID, $resultPage->ID);
		$this->assertEquals($pageID, $requeriedPage->ID);
		$this->assertEquals('About Us', $requeriedPage->Title);
		$this->assertEquals('Page', $requeriedPage->class);


		$page2 = $this->objFromFixture('Page', 'products');
		$page2ID = $page2->ID;
		$page2->doUnpublish();
		$page2->delete();

		// Check that if we restore while on the live site that the content still gets pushed to
		// stage
		Versioned::reading_stage('Live');
		$deletedPage = Versioned::get_latest_version('SiteTree', $page2ID);
		$deletedPage->doRestoreToStage();
		$this->assertFalse((bool)Versioned::get_one_by_stage("Page", "Live", "\"SiteTree\".\"ID\" = " . $page2ID));

		Versioned::reading_stage('Stage');
		$requeriedPage = DataObject::get_by_id("Page", $page2ID);
		$this->assertEquals('Products', $requeriedPage->Title);
		$this->assertEquals('Page', $requeriedPage->class);

	}

	public function testGetByLink() {
		$home     = $this->objFromFixture('Page', 'home');
		$about    = $this->objFromFixture('Page', 'about');
		$staff    = $this->objFromFixture('Page', 'staff');
		$product  = $this->objFromFixture('Page', 'product1');
		$notFound = $this->objFromFixture('ErrorPage', '404');

		SiteTree::config()->nested_urls = false;

		$this->assertEquals($home->ID, SiteTree::get_by_link('/', false)->ID);
		$this->assertEquals($home->ID, SiteTree::get_by_link('/home/', false)->ID);
		$this->assertEquals($about->ID, SiteTree::get_by_link($about->Link(), false)->ID);
		$this->assertEquals($staff->ID, SiteTree::get_by_link($staff->Link(), false)->ID);
		$this->assertEquals($product->ID, SiteTree::get_by_link($product->Link(), false)->ID);
		$this->assertEquals($notFound->ID, SiteTree::get_by_link($notFound->Link(), false)->ID);

		Config::inst()->update('SiteTree', 'nested_urls', true);

		$this->assertEquals($home->ID, SiteTree::get_by_link('/', false)->ID);
		$this->assertEquals($home->ID, SiteTree::get_by_link('/home/', false)->ID);
		$this->assertEquals($about->ID, SiteTree::get_by_link($about->Link(), false)->ID);
		$this->assertEquals($staff->ID, SiteTree::get_by_link($staff->Link(), false)->ID);
		$this->assertEquals($product->ID, SiteTree::get_by_link($product->Link(), false)->ID);
		$this->assertEquals($notFound->ID, SiteTree::get_by_link($notFound->Link(), false)->ID);

		$this->assertEquals (
			$staff->ID, SiteTree::get_by_link('/my-staff/', false)->ID, 'Assert a unique URLSegment can be used for b/c.'
		);
	}

	public function testRelativeLink() {
		$about    = $this->objFromFixture('Page', 'about');
		$staff    = $this->objFromFixture('Page', 'staff');

		Config::inst()->update('SiteTree', 'nested_urls', true);

		$this->assertEquals('about-us/', $about->RelativeLink(), 'Matches URLSegment on top level without parameters');
		$this->assertEquals('about-us/my-staff/', $staff->RelativeLink(), 'Matches URLSegment plus parent on second level without parameters');
		$this->assertEquals('about-us/edit', $about->RelativeLink('edit'), 'Matches URLSegment plus parameter on top level');
		$this->assertEquals('about-us/tom&jerry', $about->RelativeLink('tom&jerry'), 'Doesnt url encode parameter');
	}

	public function testPageLevel() {
		$about = $this->objFromFixture('Page', 'about');
		$staff = $this->objFromFixture('Page', 'staff');
		$this->assertEquals(1, $about->getPageLevel());
		$this->assertEquals(2, $staff->getPageLevel());
	}

	public function testAbsoluteLiveLink() {
		$parent = $this->objFromFixture('Page', 'about');
		$child = $this->objFromFixture('Page', 'staff');

		Config::inst()->update('SiteTree', 'nested_urls', true);

		$child->publish('Stage', 'Live');
		$parent->URLSegment = 'changed-on-live';
		$parent->write();
		$parent->publish('Stage', 'Live');
		$parent->URLSegment = 'changed-on-draft';
		$parent->write();

		$this->assertStringEndsWith('changed-on-live/my-staff/', $child->getAbsoluteLiveLink(false));
		$this->assertStringEndsWith('changed-on-live/my-staff/?stage=Live', $child->getAbsoluteLiveLink());
	}

	public function testDeleteFromStageOperatesRecursively() {
		Config::inst()->update('SiteTree', 'enforce_strict_hierarchy', false);
		$pageAbout = $this->objFromFixture('Page', 'about');
		$pageStaff = $this->objFromFixture('Page', 'staff');
		$pageStaffDuplicate = $this->objFromFixture('Page', 'staffduplicate');

		$pageAbout->delete();

		$this->assertFalse(DataObject::get_by_id('Page', $pageAbout->ID));
		$this->assertTrue(DataObject::get_by_id('Page', $pageStaff->ID) instanceof Page);
		$this->assertTrue(DataObject::get_by_id('Page', $pageStaffDuplicate->ID) instanceof Page);
		Config::inst()->update('SiteTree', 'enforce_strict_hierarchy', true);
	}

	public function testDeleteFromStageOperatesRecursivelyStrict() {
		$pageAbout = $this->objFromFixture('Page', 'about');
		$pageStaff = $this->objFromFixture('Page', 'staff');
		$pageStaffDuplicate = $this->objFromFixture('Page', 'staffduplicate');

		$pageAbout->delete();

		$this->assertFalse(DataObject::get_by_id('Page', $pageAbout->ID));
		$this->assertFalse(DataObject::get_by_id('Page', $pageStaff->ID));
		$this->assertFalse(DataObject::get_by_id('Page', $pageStaffDuplicate->ID));
	}

	public function testDeleteFromLiveOperatesRecursively() {
		Config::inst()->update('SiteTree', 'enforce_strict_hierarchy', false);
		$this->logInWithPermission('ADMIN');

		$pageAbout = $this->objFromFixture('Page', 'about');
		$pageAbout->doPublish();
		$pageStaff = $this->objFromFixture('Page', 'staff');
		$pageStaff->doPublish();
		$pageStaffDuplicate = $this->objFromFixture('Page', 'staffduplicate');
		$pageStaffDuplicate->doPublish();

		$parentPage = $this->objFromFixture('Page', 'about');

		$parentPage->doDeleteFromLive();

		Versioned::reading_stage('Live');

		$this->assertFalse(DataObject::get_by_id('Page', $pageAbout->ID));
		$this->assertTrue(DataObject::get_by_id('Page', $pageStaff->ID) instanceof Page);
		$this->assertTrue(DataObject::get_by_id('Page', $pageStaffDuplicate->ID) instanceof Page);
		Versioned::reading_stage('Stage');
		Config::inst()->update('SiteTree', 'enforce_strict_hierarchy', true);
	}

	public function testUnpublishDoesNotDeleteChildrenWithLooseHierachyOn() {
		Config::inst()->update('SiteTree', 'enforce_strict_hierarchy', false);
		$this->logInWithPermission('ADMIN');

		$pageAbout = $this->objFromFixture('Page', 'about');
		$pageAbout->doPublish();
		$pageStaff = $this->objFromFixture('Page', 'staff');
		$pageStaff->doPublish();
		$pageStaffDuplicate = $this->objFromFixture('Page', 'staffduplicate');
		$pageStaffDuplicate->doPublish();

		$parentPage = $this->objFromFixture('Page', 'about');
		$parentPage->doUnpublish();

		Versioned::reading_stage('Live');
		$this->assertFalse(DataObject::get_by_id('Page', $pageAbout->ID));
		$this->assertTrue(DataObject::get_by_id('Page', $pageStaff->ID) instanceof Page);
		$this->assertTrue(DataObject::get_by_id('Page', $pageStaffDuplicate->ID) instanceof Page);
		Versioned::reading_stage('Stage');
		Config::inst()->update('SiteTree', 'enforce_strict_hierarchy', true);
	}

	public function testDeleteFromLiveOperatesRecursivelyStrict() {
		$this->logInWithPermission('ADMIN');

		$pageAbout = $this->objFromFixture('Page', 'about');
		$pageAbout->doPublish();
		$pageStaff = $this->objFromFixture('Page', 'staff');
		$pageStaff->doPublish();
		$pageStaffDuplicate = $this->objFromFixture('Page', 'staffduplicate');
		$pageStaffDuplicate->doPublish();

		$parentPage = $this->objFromFixture('Page', 'about');
		$parentPage->doDeleteFromLive();

		Versioned::reading_stage('Live');
		$this->assertFalse(DataObject::get_by_id('Page', $pageAbout->ID));
		$this->assertFalse(DataObject::get_by_id('Page', $pageStaff->ID));
		$this->assertFalse(DataObject::get_by_id('Page', $pageStaffDuplicate->ID));
		Versioned::reading_stage('Stage');
	}

	/**
	 * Simple test to confirm that querying from a particular archive date doesn't throw
	 * an error
	 */
	public function testReadArchiveDate() {
		$date = '2009-07-02 14:05:07';
		Versioned::reading_archived_date($date);
		SiteTree::get()->where(array(
			'"SiteTree"."ParentID"' => 0
		));
		Versioned::reading_archived_date(null);
		$this->assertEquals(
			Versioned::get_reading_mode(),
			'Archive.'
		);
	}

	public function testEditPermissions() {
		$editor = $this->objFromFixture("Member", "editor");

		$home = $this->objFromFixture("Page", "home");
		$staff = $this->objFromFixture("Page", "staff");
		$products = $this->objFromFixture("Page", "products");
		$product1 = $this->objFromFixture("Page", "product1");
		$product4 = $this->objFromFixture("Page", "product4");

		// Test logged out users cannot edit
		$this->logOut();
		$this->assertFalse($staff->canEdit());

		// Can't edit a page that is locked to admins
		$this->assertFalse($home->canEdit($editor));

		// Can edit a page that is locked to editors
		$this->assertTrue($products->canEdit($editor));

		// Can edit a child of that page that inherits
		$this->assertTrue($product1->canEdit($editor));

		// Can't edit a child of that page that has its permissions overridden
		$this->assertFalse($product4->canEdit($editor));
	}

	public function testCanEditWithAccessToAllSections() {
		$page = new Page();
		$page->write();
		$allSectionMember = $this->objFromFixture('Member', 'allsections');
		$securityAdminMember = $this->objFromFixture('Member', 'securityadmin');

		$this->assertTrue($page->canEdit($allSectionMember));
		$this->assertFalse($page->canEdit($securityAdminMember));
	}

	public function testCreatePermissions() {
		// Test logged out users cannot create
		$this->logOut();
		$this->assertFalse(singleton('SiteTree')->canCreate());

		// Login with another permission
		$this->logInWithPermission('DUMMY');
		$this->assertFalse(singleton('SiteTree')->canCreate());

		// Login with basic CMS permission
		$perms = SiteConfig::config()->required_permission;
		$this->logInWithPermission(reset($perms));
		$this->assertTrue(singleton('SiteTree')->canCreate());

		// Test creation underneath a parent which this user doesn't have access to
		$parent = $this->objFromFixture('Page', 'about');
		$this->assertFalse(singleton('SiteTree')->canCreate(null, array('Parent' => $parent)));

		// Test creation underneath a parent which doesn't allow a certain child
		$parentB = new SiteTreeTest_ClassB();
		$parentB->Title = 'Only Allows SiteTreeTest_ClassC';
		$parentB->write();
		$this->assertTrue(singleton('SiteTreeTest_ClassA')->canCreate(null));
		$this->assertFalse(singleton('SiteTreeTest_ClassA')->canCreate(null, array('Parent' => $parentB)));
		$this->assertTrue(singleton('SiteTreeTest_ClassC')->canCreate(null, array('Parent' => $parentB)));
	}
	
	public function testEditPermissionsOnDraftVsLive() {
		// Create an inherit-permission page
		$page = new Page();
		$page->write();
		$page->CanEditType = "Inherit";
		$page->doPublish();
		$pageID = $page->ID;

		// Lock down the site config
		$sc = $page->SiteConfig;
		$sc->CanEditType = 'OnlyTheseUsers';
		$sc->EditorGroups()->add($this->idFromFixture('Group', 'admins'));
		$sc->write();

		// Confirm that Member.editor can't edit the page
		$this->objFromFixture('Member','editor')->logIn();
		$this->assertFalse($page->canEdit());

		// Change the page to be editable by Group.editors, but do not publish
		$this->objFromFixture('Member','admin')->logIn();
		$page->CanEditType = 'OnlyTheseUsers';
		$page->EditorGroups()->add($this->idFromFixture('Group', 'editors'));
		$page->write();
		// Clear permission cache
		SiteTree::on_db_reset();

		// Confirm that Member.editor can now edit the page
		$this->objFromFixture('Member','editor')->logIn();
		$this->assertTrue($page->canEdit());

		// Publish the changes to the page
		$this->objFromFixture('Member','admin')->logIn();
		$page->doPublish();

		// Confirm that Member.editor can still edit the page
		$this->objFromFixture('Member','editor')->logIn();
		$this->assertTrue($page->canEdit());
    }

	public function testCompareVersions() {
		// Necessary to avoid
		$oldCleanerClass = Diff::$html_cleaner_class;
		Diff::$html_cleaner_class = 'SiteTreeTest_NullHtmlCleaner';

		$page = new Page();
		$page->write();
		$this->assertEquals(1, $page->Version);

		// Use inline element to avoid double wrapping applied to
		// blocklevel elements depending on HTMLCleaner implementation:
		// <ins><p> gets converted to <ins><p><inst>
		$page->Content = "<span>This is a test</span>";
		$page->write();
		$this->assertEquals(2, $page->Version);

		$diff = $page->compareVersions(1, 2);

		$processedContent = trim($diff->Content);
		$processedContent = preg_replace('/\s*</','<',$processedContent);
		$processedContent = preg_replace('/>\s*/','>',$processedContent);
		$this->assertEquals("<ins><span>This is a test</span></ins>", $processedContent);

		Diff::$html_cleaner_class = $oldCleanerClass;
	}

	public function testAuthorIDAndPublisherIDFilledOutOnPublish() {
		// Ensure that we have a member ID who is doing all this work
		$member = Member::currentUser();
		if($member) {
			$memberID = $member->ID;
		} else {
			$memberID = $this->idFromFixture("Member", "admin");
			Session::set("loggedInAs", $memberID);
		}

		// Write the page
		$about = $this->objFromFixture('Page','about');
		$about->Title = "Another title";
		$about->write();

		// Check the version created
		$savedVersion = DB::query("SELECT \"AuthorID\", \"PublisherID\" FROM \"SiteTree_versions\"
			WHERE \"RecordID\" = $about->ID ORDER BY \"Version\" DESC")->first();
		$this->assertEquals($memberID, $savedVersion['AuthorID']);
		$this->assertEquals(0, $savedVersion['PublisherID']);

		// Publish the page
		$about->doPublish();
		$publishedVersion = DB::query("SELECT \"AuthorID\", \"PublisherID\" FROM \"SiteTree_versions\"
			WHERE \"RecordID\" = $about->ID ORDER BY \"Version\" DESC")->first();

		// Check the version created
		$this->assertEquals($memberID, $publishedVersion['AuthorID']);
		$this->assertEquals($memberID, $publishedVersion['PublisherID']);

	}

	public function testLinkShortcodeHandler() {
		$aboutPage = $this->objFromFixture('Page', 'about');
		$errorPage = $this->objFromFixture('ErrorPage', '404');
		$redirectPage = $this->objFromFixture('RedirectorPage', 'external');

		$parser = new ShortcodeParser();
		$parser->register('sitetree_link', array('SiteTree', 'link_shortcode_handler'));

		$aboutShortcode = sprintf('[sitetree_link,id=%d]', $aboutPage->ID);
		$aboutEnclosed  = sprintf('[sitetree_link,id=%d]Example Content[/sitetree_link]', $aboutPage->ID);

		$aboutShortcodeExpected = $aboutPage->Link();
		$aboutEnclosedExpected  = sprintf('<a href="%s">Example Content</a>', $aboutPage->Link());

		$this->assertEquals($aboutShortcodeExpected, $parser->parse($aboutShortcode), 'Test that simple linking works.');
		$this->assertEquals($aboutEnclosedExpected, $parser->parse($aboutEnclosed), 'Test enclosed content is linked.');

		$aboutPage->delete();

		$this->assertEquals($aboutShortcodeExpected, $parser->parse($aboutShortcode), 'Test that deleted pages still link.');
		$this->assertEquals($aboutEnclosedExpected, $parser->parse($aboutEnclosed));

		$aboutShortcode = '[sitetree_link,id="-1"]';
		$aboutEnclosed  = '[sitetree_link,id="-1"]Example Content[/sitetree_link]';

		$aboutShortcodeExpected = $errorPage->Link();
		$aboutEnclosedExpected  = sprintf('<a href="%s">Example Content</a>', $errorPage->Link());

		$this->assertEquals($aboutShortcodeExpected, $parser->parse($aboutShortcode), 'Test link to 404 page if no suitable matches.');
		$this->assertEquals($aboutEnclosedExpected, $parser->parse($aboutEnclosed));

		$redirectShortcode = sprintf('[sitetree_link,id=%d]', $redirectPage->ID);
		$redirectEnclosed  = sprintf('[sitetree_link,id=%d]Example Content[/sitetree_link]', $redirectPage->ID);
		$redirectExpected = 'http://www.google.com?a&amp;b';

		$this->assertEquals($redirectExpected, $parser->parse($redirectShortcode));
		$this->assertEquals(sprintf('<a href="%s">Example Content</a>', $redirectExpected), $parser->parse($redirectEnclosed));

		$this->assertEquals('', $parser->parse('[sitetree_link]'), 'Test that invalid ID attributes are not parsed.');
		$this->assertEquals('', $parser->parse('[sitetree_link,id="text"]'));
		$this->assertEquals('', $parser->parse('[sitetree_link]Example Content[/sitetree_link]'));
	}

	public function testIsCurrent() {
		$aboutPage = $this->objFromFixture('Page', 'about');
		$errorPage = $this->objFromFixture('ErrorPage', '404');

		Director::set_current_page($aboutPage);
		$this->assertTrue($aboutPage->isCurrent(), 'Assert that basic isSection checks works.');
		$this->assertFalse($errorPage->isCurrent());

		Director::set_current_page($errorPage);
		$this->assertTrue($errorPage->isCurrent(), 'Assert isSection works on error pages.');
		$this->assertFalse($aboutPage->isCurrent());

		Director::set_current_page($aboutPage);
		$this->assertTrue (
			DataObject::get_one('SiteTree', array(
				'"SiteTree"."Title"' => 'About Us'
			))->isCurrent(),
			'Assert that isCurrent works on another instance with the same ID.'
		);

		Director::set_current_page($newPage = new SiteTree());
		$this->assertTrue($newPage->isCurrent(), 'Assert that isCurrent works on unsaved pages.');
	}

	public function testIsSection() {
		$about = $this->objFromFixture('Page', 'about');
		$staff = $this->objFromFixture('Page', 'staff');
		$ceo   = $this->objFromFixture('Page', 'ceo');

		Director::set_current_page($about);
		$this->assertTrue($about->isSection());
		$this->assertFalse($staff->isSection());
		$this->assertFalse($ceo->isSection());

		Director::set_current_page($staff);
		$this->assertTrue($about->isSection());
		$this->assertTrue($staff->isSection());
		$this->assertFalse($ceo->isSection());

		Director::set_current_page($ceo);
		$this->assertTrue($about->isSection());
		$this->assertTrue($staff->isSection());
		$this->assertTrue($ceo->isSection());
	}

	public function testURLSegmentAutoUpdate() {
		$sitetree = new SiteTree();
		$sitetree->Title = _t(
			'CMSMain.NEWPAGE',
			array('pagetype' => $sitetree->i18n_singular_name())
		);
		$sitetree->write();
		$this->assertEquals($sitetree->URLSegment, 'new-page',
			'Sets based on default title on first save'
		);
		
		$sitetree->Title = 'Changed';
		$sitetree->write();
		$this->assertEquals($sitetree->URLSegment, 'changed',
			'Auto-updates when set to default title'
		);

		$sitetree->Title = 'Changed again';
		$sitetree->write();
		$this->assertEquals($sitetree->URLSegment, 'changed',
			'Does not auto-update once title has been changed'
		);
	}

	public function testURLSegmentAutoUpdateLocalized() {
		$oldLocale = i18n::get_locale();
		i18n::set_locale('de_DE');

		$sitetree = new SiteTree();
		$sitetree->Title = _t(
			'CMSMain.NEWPAGE',
			array('pagetype' => $sitetree->i18n_singular_name())
		);
		$sitetree->write();
		$this->assertEquals($sitetree->URLSegment, 'neue-seite',
			'Sets based on default title on first save'
		);

		$sitetree->Title = 'Changed';
		$sitetree->write();
		$this->assertEquals($sitetree->URLSegment, 'changed',
			'Auto-updates when set to default title'
		);

		$sitetree->Title = 'Changed again';
		$sitetree->write();
		$this->assertEquals($sitetree->URLSegment, 'changed',
			'Does not auto-update once title has been changed'
		);

		i18n::set_locale($oldLocale);
	}

	/**
	 * @covers SiteTree::validURLSegment
	 */
	public function testValidURLSegmentURLSegmentConflicts() {
		$sitetree = new SiteTree();
		SiteTree::config()->nested_urls = false;

		$sitetree->URLSegment = 'home';
		$this->assertFalse($sitetree->validURLSegment(), 'URLSegment conflicts are recognised');
		$sitetree->URLSegment = 'home-noconflict';
		$this->assertTrue($sitetree->validURLSegment());

		$sitetree->ParentID   = $this->idFromFixture('Page', 'about');
		$sitetree->URLSegment = 'home';
		$this->assertFalse($sitetree->validURLSegment(), 'Conflicts are still recognised with a ParentID value');

		Config::inst()->update('SiteTree', 'nested_urls', true);

		$sitetree->ParentID   = 0;
		$sitetree->URLSegment = 'home';
		$this->assertFalse($sitetree->validURLSegment(), 'URLSegment conflicts are recognised');

		$sitetree->ParentID = $this->idFromFixture('Page', 'about');
		$this->assertTrue($sitetree->validURLSegment(), 'URLSegments can be the same across levels');

		$sitetree->URLSegment = 'my-staff';
		$this->assertFalse($sitetree->validURLSegment(), 'Nested URLSegment conflicts are recognised');
		$sitetree->URLSegment = 'my-staff-noconflict';
		$this->assertTrue($sitetree->validURLSegment());
	}

	/**
	 * @covers SiteTree::validURLSegment
	 */
	public function testValidURLSegmentClassNameConflicts() {
		$sitetree = new SiteTree();
		$sitetree->URLSegment = 'Controller';

		$this->assertFalse($sitetree->validURLSegment(), 'Class name conflicts are recognised');
	}

	/**
	 * @covers SiteTree::validURLSegment
	 */
	public function testValidURLSegmentControllerConflicts() {
		Config::inst()->update('SiteTree', 'nested_urls', true);

		$sitetree = new SiteTree();
		$sitetree->ParentID = $this->idFromFixture('SiteTreeTest_Conflicted', 'parent');

		$sitetree->URLSegment = 'index';
		$this->assertFalse($sitetree->validURLSegment(), 'index is not a valid URLSegment');

		$sitetree->URLSegment = 'conflicted-action';
		$this->assertFalse($sitetree->validURLSegment(), 'allowed_actions conflicts are recognised');

		$sitetree->URLSegment = 'conflicted-template';
		$this->assertFalse($sitetree->validURLSegment(), 'Action-specific template conflicts are recognised');

		$sitetree->URLSegment = 'valid';
		$this->assertTrue($sitetree->validURLSegment(), 'Valid URLSegment values are allowed');
	}

	public function testURLSegmentPrioritizesExtensionVotes() {
		$sitetree = new SiteTree();
		$sitetree->URLSegment = 'unique-segment';
		$this->assertTrue($sitetree->validURLSegment());

		SiteTree::add_extension('SiteTreeTest_Extension');
		$sitetree = new SiteTree();
		$sitetree->URLSegment = 'unique-segment';
		$this->assertFalse($sitetree->validURLSegment());
		SiteTree::remove_extension('SiteTreeTest_Extension');
	}

	public function testURLSegmentMultiByte() {
		$origAllow = Config::inst()->get('URLSegmentFilter', 'default_allow_multibyte');
		Config::inst()->update('URLSegmentFilter', 'default_allow_multibyte', true);
		$sitetree = new SiteTree();
		$sitetree->write();

		$sitetree->URLSegment = 'brötchen';
		$sitetree->write();
		$sitetree = DataObject::get_by_id('SiteTree', $sitetree->ID, false);
		$this->assertEquals($sitetree->URLSegment, rawurlencode('brötchen'));

		$sitetree->publish('Stage', 'Live');
		$sitetree = DataObject::get_by_id('SiteTree', $sitetree->ID, false);
		$this->assertEquals($sitetree->URLSegment, rawurlencode('brötchen'));
		$sitetreeLive = Versioned::get_one_by_stage('SiteTree', 'Live', '"SiteTree"."ID" = ' .$sitetree->ID, false);
		$this->assertEquals($sitetreeLive->URLSegment, rawurlencode('brötchen'));

		Config::inst()->update('URLSegmentFilter', 'default_allow_multibyte', $origAllow);
	}

	public function testVersionsAreCreated() {
		$p = new Page();
		$p->Content = "one";
		$p->write();
		$this->assertEquals(1, $p->Version);

		// No changes don't bump version
		$p->write();
		$this->assertEquals(1, $p->Version);

		$p->Content = "two";
		$p->write();
		$this->assertEquals(2, $p->Version);

		// Only change meta-data don't bump version
		$p->HasBrokenLink = true;
		$p->write();
		$p->HasBrokenLink = false;
		$p->write();
		$this->assertEquals(2, $p->Version);

		$p->Content = "three";
		$p->write();
		$this->assertEquals(3, $p->Version);

	}

	public function testPageTypeClasses() {
		$classes = SiteTree::page_type_classes();
		$this->assertNotContains('SiteTree', $classes, 'Page types do not include base class');
		$this->assertContains('Page', $classes, 'Page types do contain subclasses');

		// Testing what happens in an incorrect config value is set - hide_ancestor should be a string
		Config::inst()->update('SiteTreeTest_ClassA', 'hide_ancestor', true);
		$newClasses = SiteTree::page_type_classes();
		$this->assertEquals(
			$classes,
			$newClasses,
			'Setting hide_ancestor to a boolean (incorrect) value caused a page class to be hidden'
		);
	}

	public function testAllowedChildren() {
		$page = new SiteTree();
		$this->assertContains(
			'VirtualPage',
			$page->allowedChildren(),
			'Includes core subclasses by default'
		);

		$classA = new SiteTreeTest_ClassA();
		$this->assertEquals(
			array('SiteTreeTest_ClassB'),
			$classA->allowedChildren(),
			'Direct setting of allowed children'
		);

		$classB = new SiteTreeTest_ClassB();
		$this->assertEquals(
			array('SiteTreeTest_ClassC', 'SiteTreeTest_ClassCext'),
			$classB->allowedChildren(),
			'Includes subclasses'
		);

		$classD = new SiteTreeTest_ClassD();
		$this->assertEquals(
			array('SiteTreeTest_ClassC'),
			$classD->allowedChildren(),
			'Excludes subclasses if class is prefixed by an asterisk'
		);

		$classC = new SiteTreeTest_ClassC();
		$this->assertEquals(
			array(),
			$classC->allowedChildren(),
			'Null setting'
		);
	}

	public function testAllowedChildrenValidation() {
		$page = new SiteTree();
		$page->write();
		$classA = new SiteTreeTest_ClassA();
		$classA->write();
		$classB = new SiteTreeTest_ClassB();
		$classB->write();
		$classC = new SiteTreeTest_ClassC();
		$classC->write();
		$classD = new SiteTreeTest_ClassD();
		$classD->write();
		$classCext = new SiteTreeTest_ClassCext();
		$classCext->write();

		$classB->ParentID = $page->ID;
		$valid = $classB->doValidate();
		$this->assertTrue($valid->valid(), "Does allow children on unrestricted parent");

		$classB->ParentID = $classA->ID;
		$valid = $classB->doValidate();
		$this->assertTrue($valid->valid(), "Does allow child specifically allowed by parent");

		$classC->ParentID = $classA->ID;
		$valid = $classC->doValidate();
		$this->assertFalse($valid->valid(), "Doesnt allow child on parents specifically restricting children");

		$classB->ParentID = $classC->ID;
		$valid = $classB->doValidate();
		$this->assertFalse($valid->valid(), "Doesnt allow child on parents disallowing all children");

		$classB->ParentID = $classC->ID;
		$valid = $classB->doValidate();
		$this->assertFalse($valid->valid(), "Doesnt allow child on parents disallowing all children");

		$classCext->ParentID = $classD->ID;
		$valid = $classCext->doValidate();
		$this->assertFalse($valid->valid(), "Doesnt allow child where only parent class is allowed on parent node, and asterisk prefixing is used");
	}

	public function testClassDropdown() {
		$sitetree = new SiteTree();
		$method = new ReflectionMethod($sitetree, 'getClassDropdown');
		$method->setAccessible(true);

		Session::set("loggedInAs", null);
		$this->assertArrayNotHasKey('SiteTreeTest_ClassA', $method->invoke($sitetree));

		$this->loginWithPermission('ADMIN');
		$this->assertArrayHasKey('SiteTreeTest_ClassA', $method->invoke($sitetree));

		$this->loginWithPermission('CMS_ACCESS_CMSMain');
		$this->assertArrayHasKey('SiteTreeTest_ClassA', $method->invoke($sitetree));

		Session::set("loggedInAs", null);
	}

	public function testCanBeRoot() {
		$page = new SiteTree();
		$page->ParentID = 0;
		$page->write();

		$notRootPage = new SiteTreeTest_NotRoot();
		$notRootPage->ParentID = 0;
		$isDetected = false;
		try {
			$notRootPage->write();
		} catch(ValidationException $e) {
			$this->assertContains('is not allowed on the root level', $e->getMessage());
			$isDetected = true;
		}

		if(!$isDetected) $this->fail('Fails validation with $can_be_root=false');
	}

	public function testModifyStatusFlagByInheritance(){
		$node = new SiteTreeTest_StageStatusInherit();
		$treeTitle = $node->getTreeTitle();
		$this->assertContains('InheritedTitle', $treeTitle);
		$this->assertContains('inherited-class', $treeTitle);
	}

	public function testMenuTitleIsUnsetWhenEqualsTitle() {
		$page = new SiteTree();
		$page->Title = 'orig';
		$page->MenuTitle = 'orig';
		$page->write();

		// change menu title
		$page->MenuTitle = 'changed';
		$page->write();
		$page = SiteTree::get()->byID($page->ID);
		$this->assertEquals('changed', $page->getField('MenuTitle'));

		// change menu title back
		$page->MenuTitle = 'orig';
		$page->write();
		$page = SiteTree::get()->byID($page->ID);
		$this->assertEquals(null, $page->getField('MenuTitle'));
	}

	public function testMetaTagGeneratorDisabling() {
		$generator = Config::inst()->get('SiteTree', 'meta_generator');

		$page = new SiteTreeTest_PageNode();

		$meta = $page->MetaTags();
		$this->assertEquals(
			1,
			preg_match('/.*meta name="generator" content="SilverStripe - http:\/\/silverstripe.org".*/', $meta),
			'test default functionality - uses value from Config');

		// test proper escaping of quotes in attribute value
		Config::inst()->update('SiteTree', 'meta_generator', 'Generator with "quotes" in it');
		$meta = $page->MetaTags();
		$this->assertEquals(
			1,
			preg_match('/.*meta name="generator" content="Generator with &quot;quotes&quot; in it".*/', $meta),
			'test proper escaping of values from Config');

		// test empty generator - no tag should appear at all
		Config::inst()->update('SiteTree', 'meta_generator', '');
		$meta = $page->MetaTags();
		$this->assertEquals(
			0,
			preg_match('/.*meta name=.generator..*/', $meta),
			'test blank value means no tag generated');

		// reset original value
		Config::inst()->update('SiteTree', 'meta_generator', $generator);
	}


	public function testGetBreadcrumbItems() {
		$page = $this->objFromFixture("Page", "breadcrumbs");
		$this->assertEquals($page->getBreadcrumbItems()->count(), 1, "Only display current page.");

		// Test breadcrumb order
		$page = $this->objFromFixture("Page", "breadcrumbs5");
		$breadcrumbs = $page->getBreadcrumbItems();
		$this->assertEquals($breadcrumbs->count(), 5, "Display all breadcrumbs");
		$this->assertEquals($breadcrumbs->first()->Title, "Breadcrumbs", "Breadcrumbs should be the first item.");
		$this->assertEquals($breadcrumbs->last()->Title, "Breadcrumbs 5", "Breadcrumbs 5 should be last item.");

		// Test breadcrumb max depth
		$breadcrumbs = $page->getBreadcrumbItems(2);
		$this->assertEquals($breadcrumbs->count(), 2, "Max depth should limit the breadcrumbs to 2 items.");
		$this->assertEquals($breadcrumbs->first()->Title, "Breadcrumbs 4", "First item should be Breadrcumbs 4.");
		$this->assertEquals($breadcrumbs->last()->Title, "Breadcrumbs 5", "Breadcrumbs 5 should be last.");
	}
	
	/**
	 * Tests SiteTree::MetaTags
	 * Note that this test makes no assumption on the closing of tags (other than <title></title>)
	 */
	public function testMetaTags() {
		$this->logInWithPermission('ADMIN');
		$page = $this->objFromFixture('Page', 'metapage');

		// Test with title
		$meta = $page->MetaTags();
		$charset = Config::inst()->get('ContentNegotiator', 'encoding');
		$this->assertContains('<meta http-equiv="Content-type" content="text/html; charset='.$charset.'"', $meta);
		$this->assertContains('<meta name="description" content="The &lt;br /&gt; and &lt;br&gt; tags"', $meta);
		$this->assertContains('<link rel="canonical" href="http://www.mysite.com/html-and-xml"', $meta);
		$this->assertContains('<meta name="x-page-id" content="'.$page->ID.'"', $meta);
		$this->assertContains('<meta name="x-cms-edit-link" content="'.$page->CMSEditLink().'" />', $meta);
		$this->assertContains('<title>HTML &amp; XML</title>', $meta);

		// Test without title
		$meta = $page->MetaTags(false);
		$this->assertNotContains('<title>', $meta);
	}

	/**
	 * Test that orphaned pages are handled correctly
	 */
	public function testOrphanedPages() {
		$origStage = Versioned::get_reading_mode();

		// Setup user who can view draft content, but lacks cms permission.
		// To users such as this, orphaned pages should be inaccessible. canView for these pages is only
		// necessary for admin / cms users, who require this permission to edit / rearrange these pages.
		$permission = new Permission();
		$permission->Code = 'VIEW_DRAFT_CONTENT';
		$group = new Group(array('Title' => 'Staging Users'));
		$group->write();
		$group->Permissions()->add($permission);
		$member = new Member();
		$member->Email = 'someguy@example.com';
		$member->write();
		$member->Groups()->add($group);

		// both pages are viewable in stage
		Versioned::reading_stage('Stage');
		$about = $this->objFromFixture('Page', 'about');
		$staff = $this->objFromFixture('Page', 'staff');
		$this->assertFalse($about->isOrphaned());
		$this->assertFalse($staff->isOrphaned());
		$this->assertTrue($about->canView($member));
		$this->assertTrue($staff->canView($member));

		// Publishing only the child page to live should orphan the live record, but not the staging one
		$staff->publish('Stage', 'Live');
		$this->assertFalse($staff->isOrphaned());
		$this->assertTrue($staff->canView($member));
		Versioned::reading_stage('Live');
		$staff = $this->objFromFixture('Page', 'staff'); // Live copy of page
		$this->assertTrue($staff->isOrphaned()); // because parent isn't published
		$this->assertFalse($staff->canView($member));

		// Publishing the parent page should restore visibility
		Versioned::reading_stage('Stage');
		$about = $this->objFromFixture('Page', 'about');
		$about->publish('Stage', 'Live');
		Versioned::reading_stage('Live');
		$staff = $this->objFromFixture('Page', 'staff');
		$this->assertFalse($staff->isOrphaned());
		$this->assertTrue($staff->canView($member));

		// Removing staging page should not prevent live page being visible
		$about->deleteFromStage('Stage');
		$staff->deleteFromStage('Stage');
		$staff = $this->objFromFixture('Page', 'staff');
		$this->assertFalse($staff->isOrphaned());
		$this->assertTrue($staff->canView($member));

		// Cleanup
		Versioned::set_reading_mode($origStage);

	}

	/**
	 * Test archived page behaviour
	 */
	public function testArchivedPages() {
		$this->logInWithPermission('ADMIN');

		$page = $this->objFromFixture('Page', 'home');
		$this->assertTrue($page->canAddChildren());
		$this->assertFalse($page->getIsDeletedFromStage());
		$this->assertFalse($page->isPublished());

		// Publish
		$page->doPublish();
		$this->assertTrue($page->canAddChildren());
		$this->assertFalse($page->getIsDeletedFromStage());
		$this->assertTrue($page->isPublished());

		// Archive
		$page->doArchive();
		$this->assertFalse($page->canAddChildren());
		$this->assertTrue($page->getIsDeletedFromStage());
		$this->assertFalse($page->isPublished());
	}

}

/**#@+
 * @ignore
 */

class SiteTreeTest_PageNode extends Page implements TestOnly { }
class SiteTreeTest_PageNode_Controller extends Page_Controller implements TestOnly {
}

class SiteTreeTest_Conflicted extends Page implements TestOnly { }
class SiteTreeTest_Conflicted_Controller extends Page_Controller implements TestOnly {
	
	private static $allowed_actions = array (
		'conflicted-action'
	);
	
	public function hasActionTemplate($template) {
		if($template == 'conflicted-template') {
			return true;
		} else {
			return parent::hasActionTemplate($template);
		}
	}
	
}

class SiteTreeTest_NullHtmlCleaner extends HTMLCleaner {
	public function cleanHTML($html) {
		return $html;
	}
}

class SiteTreeTest_ClassA extends Page implements TestOnly {

	private static $need_permission = array('ADMIN', 'CMS_ACCESS_CMSMain');
	
	private static $allowed_children = array('SiteTreeTest_ClassB');
}

class SiteTreeTest_ClassB extends Page implements TestOnly {
	// Also allowed subclasses
	private static $allowed_children = array('SiteTreeTest_ClassC');
}

class SiteTreeTest_ClassC extends Page implements TestOnly {
	private static $allowed_children = array();
}

class SiteTreeTest_ClassD extends Page implements TestOnly {
	// Only allows this class, no children classes
	private static $allowed_children = array('*SiteTreeTest_ClassC');
}

class SiteTreeTest_ClassCext extends SiteTreeTest_ClassC implements TestOnly {
	// Override SiteTreeTest_ClassC definitions
	private static $allowed_children = array('SiteTreeTest_ClassB');
}

class SiteTreeTest_NotRoot extends Page implements TestOnly {
	private static $can_be_root = false;
}

class SiteTreeTest_StageStatusInherit extends SiteTree implements TestOnly {
	public function getStatusFlags($cached = true){
		$flags = parent::getStatusFlags($cached);
		$flags['inherited-class'] = "InheritedTitle";
		return $flags;
	}
}

class SiteTreeTest_Extension extends DataExtension implements TestOnly {

	public function augmentValidURLSegment() {
		return false;
	}

}
