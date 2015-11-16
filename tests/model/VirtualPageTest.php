<?php

class VirtualPageTest extends FunctionalTest {
	protected static $fixture_file = 'VirtualPageTest.yml';
	protected static $use_draft_site = false;
	protected $autoFollowRedirection = false;
	
	protected $extraDataObjects = array(
		'VirtualPageTest_ClassA',
		'VirtualPageTest_ClassB',
		'VirtualPageTest_VirtualPageSub',
		'VirtualPageTest_PageWithAllowedChildren'
	);

	protected $illegalExtensions = array(
		'SiteTree' => array('SiteTreeSubsites', 'Translatable')
	);

	protected $requiredExtensions = array(
		'SiteTree' => array('VirtualPageTest_PageExtension')
	);

	public function setUp() {
		parent::setUp();

		// Ensure we always have permission to save/publish
		$this->logInWithPermission("ADMIN");

		$this->origInitiallyCopiedFields = VirtualPage::config()->initially_copied_fields;
		Config::inst()->remove('VirtualPage', 'initially_copied_fields');
		VirtualPage::config()->initially_copied_fields = array_merge(
			$this->origInitiallyCopiedFields,
			array('MyInitiallyCopiedField')
		);
		
		$this->origNonVirtualField = VirtualPage::config()->non_virtual_fields;
		Config::inst()->remove('VirtualPage', 'non_virtual_fields');
		VirtualPage::config()->non_virtual_fields = array_merge(
			$this->origNonVirtualField,
			array('MyNonVirtualField', 'MySharedNonVirtualField')
		);
	}

	public function tearDown() {
		parent::tearDown();

		Config::inst()->remove('VirtualPage', 'initially_copied_fields');
		Config::inst()->remove('VirtualPage', 'non_virtual_fields');
		VirtualPage::config()->initially_copied_fields = $this->origInitiallyCopiedFields;
		VirtualPage::config()->non_virtual_fields = $this->origNonVirtualField;
	}
	
	/**
	 * Test that, after you update the source page of a virtual page, all the virtual pages
	 * are updated
	 */
	public function testEditingSourcePageUpdatesVirtualPages() {
		$master = $this->objFromFixture('Page', 'master');
		$master->Title = "New title";
		$master->MenuTitle = "New menutitle";
		$master->Content = "<p>New content</p>";
		$master->write();
		
		$vp1 = $this->objFromFixture('VirtualPage', 'vp1');
		$vp2 = $this->objFromFixture('VirtualPage', 'vp2');
		
		$this->assertEquals("New title", $vp1->Title);
		$this->assertEquals("New title", $vp2->Title);
		$this->assertEquals("New menutitle", $vp1->MenuTitle);
		$this->assertEquals("New menutitle", $vp2->MenuTitle);
		$this->assertEquals("<p>New content</p>", $vp1->Content);
		$this->assertEquals("<p>New content</p>", $vp2->Content);
	}

	/**
	 * Test that, after you publish the source page of a virtual page, all the already published
	 * virtual pages are published
	 */
	public function testPublishingSourcePagePublishesAlreadyPublishedVirtualPages() {
		$this->logInWithPermission('ADMIN');

		$master = $this->objFromFixture('Page', 'master');
		$master->doPublish();

		$master->Title = "New title";
		$master->MenuTitle = "New menutitle";
		$master->Content = "<p>New content</p>";
		$master->write();

		$vp1 = DataObject::get_by_id("VirtualPage", $this->idFromFixture('VirtualPage', 'vp1'));
		$vp2 = DataObject::get_by_id("VirtualPage", $this->idFromFixture('VirtualPage', 'vp2'));
		$this->assertTrue($vp1->doPublish());
		$this->assertTrue($vp2->doPublish());

		$master->doPublish();

		Versioned::reading_stage("Live");
		$vp1 = DataObject::get_by_id("VirtualPage", $this->idFromFixture('VirtualPage', 'vp1'));
		$vp2 = DataObject::get_by_id("VirtualPage", $this->idFromFixture('VirtualPage', 'vp2'));
		
		$this->assertNotNull($vp1);
		$this->assertNotNull($vp2);
		
		$this->assertEquals("New title", $vp1->Title);
		$this->assertEquals("New title", $vp2->Title);
		$this->assertEquals("New menutitle", $vp1->MenuTitle);
		$this->assertEquals("New menutitle", $vp2->MenuTitle);
		$this->assertEquals("<p>New content</p>", $vp1->Content);
		$this->assertEquals("<p>New content</p>", $vp2->Content);
		Versioned::reading_stage("Stage");
	}
	
	/**
	 * Test that virtual pages get the content from the master page when they are created.
	 */
	public function testNewVirtualPagesGrabTheContentFromTheirMaster() {
		$vp = new VirtualPage();
		$vp->write();
		
		$vp->CopyContentFromID = $this->idFromFixture('Page', 'master');
		$vp->write();
		
		$this->assertEquals("My Page", $vp->Title);
		$this->assertEquals("My Page Nav", $vp->MenuTitle);

		$vp->CopyContentFromID = $this->idFromFixture('Page', 'master2');
		$vp->write();

		$this->assertEquals("My Other Page", $vp->Title);
		$this->assertEquals("My Other Page Nav", $vp->MenuTitle);
	}
	
	/**
	 * Virtual pages are always supposed to chose the same content as the published source page.
	 * This means that when you publish them, they should show the published content of the source
	 * page, not the draft content at the time when you clicked 'publish' in the CMS.
	 */
	public function testPublishingAVirtualPageCopiedPublishedContentNotDraftContent() {
		$p = new Page();
		$p->Content = "published content";
		$p->write();
		$p->doPublish();
		
		// Don't publish this change - published page will still say 'published content'
		$p->Content = "draft content";
		$p->write();
		
		$vp = new VirtualPage();
		$vp->CopyContentFromID = $p->ID;
		$vp->write();
		
		$vp->doPublish();
		
		// The draft content of the virtual page should say 'draft content'
		$this->assertEquals('draft content',
			DB::query('SELECT "Content" from "SiteTree" WHERE "ID" = ' . $vp->ID)->value());

		// The published content of the virtual page should say 'published content'
		$this->assertEquals('published content',
			DB::query('SELECT "Content" from "SiteTree_Live" WHERE "ID" = ' . $vp->ID)->value());
	}

	public function testCantPublishVirtualPagesBeforeTheirSource() {
		// An unpublished source page
		$p = new Page();
		$p->Content = "test content";
		$p->write();
		
		// With no source page, we can't publish
		$vp = new VirtualPage();
		$vp->write();
		$this->assertFalse($vp->canPublish());

		// When the source page isn't published, we can't publish
		$vp->CopyContentFromID = $p->ID;
		$vp->write();
		$this->assertFalse($vp->canPublish());
		
		// Once the source page gets published, then we can publish
		$p->doPublish();
		$this->assertTrue($vp->canPublish());
	}

	public function testCanDeleteOrphanedVirtualPagesFromLive() {
		// An unpublished source page
		$p = new Page();
		$p->Content = "test content";
		$p->write();
		$p->doPublish();
		
		$vp = new VirtualPage();
		$vp->CopyContentFromID = $p->ID;
		$vp->write();

		// Delete the source page
		$this->assertTrue($vp->canPublish());
		$this->assertTrue($p->doDeleteFromLive());
		
		// Confirm that we can unpublish, but not publish
		$this->assertTrue($vp->canDeleteFromLive());
		$this->assertFalse($vp->canPublish());
		
		// Confirm that the action really works
		$this->assertTrue($vp->doDeleteFromLive());
		$this->assertNull(DB::query("SELECT \"ID\" FROM \"SiteTree_Live\" WHERE \"ID\" = $vp->ID")->value());
	}
	
	public function testVirtualPagesArentInappropriatelyPublished() {
		// Fixture
		$p = new Page();
		$p->Content = "test content";
		$p->write();
		$vp = new VirtualPage();
		$vp->CopyContentFromID = $p->ID;
		$vp->write();

		// VP is oragne
		$this->assertTrue($vp->getIsAddedToStage());

		// VP is still orange after we publish
		$p->doPublish();
		$this->fixVersionNumberCache($vp);
		$this->assertTrue($vp->getIsAddedToStage());
		
		// A new VP created after P's initial construction
		$vp2 = new VirtualPage();
		$vp2->CopyContentFromID = $p->ID;
		$vp2->write();
		$this->assertTrue($vp2->getIsAddedToStage());
		
		// Also remains orange after a republish
		$p->Content = "new content";
		$p->write();
		$p->doPublish();
		$this->fixVersionNumberCache($vp2);
		$this->assertTrue($vp2->getIsAddedToStage());
		
		// VP is now published
		$vp->doPublish();

		$this->fixVersionNumberCache($vp);
		$this->assertTrue($vp->getExistsOnLive());
		$this->assertFalse($vp->getIsModifiedOnStage());
		
		// P edited, VP and P both go green
		$p->Content = "third content";
		$p->write();

		$this->fixVersionNumberCache($vp, $p);
		$this->assertTrue($p->getIsModifiedOnStage());
		$this->assertTrue($vp->getIsModifiedOnStage());

		// Publish, VP goes black
		$p->doPublish();
		$this->fixVersionNumberCache($vp);
		$this->assertTrue($vp->getExistsOnLive());
		$this->assertFalse($vp->getIsModifiedOnStage());
	}
	
	public function testVirtualPagesCreateVersionRecords() {
		$source = $this->objFromFixture('Page', 'master');
		$source->Title = "T0";
		$source->write();
		$source->doPublish();
		
		// Creating a new VP to ensure that Version #s are out of alignment
		$vp = new VirtualPage();
		$vp->CopyContentFromID = $source->ID;
		$vp->write();

		$source->Title = "T1";
		$source->write();
		$source->Title = "T2";
		$source->write();
		
		$this->assertEquals($vp->ID, DB::query("SELECT \"RecordID\" FROM \"SiteTree_versions\"
			WHERE \"RecordID\" = $vp->ID AND \"Title\" = 'T1'")->value());
		$this->assertEquals($vp->ID, DB::query("SELECT \"RecordID\" FROM \"SiteTree_versions\" 
			WHERE \"RecordID\" = $vp->ID AND \"Title\" = 'T2'")->value());
		$this->assertEquals($vp->ID, DB::query("SELECT \"RecordID\" FROM \"SiteTree_versions\"
			WHERE \"RecordID\" = $vp->ID AND \"Version\" = $vp->Version")->value());
			
		$vp->doPublish();

		// Check that the published content is copied from the published page, with a legal
		// version
		$liveVersion = DB::query("SELECT \"Version\" FROM \"SiteTree_Live\" WHERE \"ID\" = $vp->ID")->value();

		$this->assertEquals("T0", DB::query("SELECT \"Title\" FROM \"SiteTree_Live\" 
				WHERE \"ID\" = $vp->ID")->value());

		// SiteTree_Live.Version should reference a legal entry in SiteTree_versions for the
		// virtual page
		$this->assertEquals("T0", DB::query("SELECT \"Title\" FROM \"SiteTree_versions\" 
				WHERE \"RecordID\" = $vp->ID AND \"Version\" = $liveVersion")->value());
	}
	
	public function fixVersionNumberCache($page) {
		$pages = func_get_args();
		foreach($pages as $p) {
			Versioned::prepopulate_versionnumber_cache('SiteTree', 'Stage', array($p->ID));
			Versioned::prepopulate_versionnumber_cache('SiteTree', 'Live', array($p->ID));
		}
	}

	public function testUnpublishingSourcePageOfAVirtualPageAlsoUnpublishesVirtualPage() {
		// Create page and virutal page
		$p = new Page();
		$p->Title = "source";
		$p->write();
		$this->assertTrue($p->doPublish());
		$vp = new VirtualPage();
		$vp->CopyContentFromID = $p->ID;
		$vp->write();
		$this->assertTrue($vp->doPublish());
		
		// All is fine, the virtual page doesn't have a broken link
		$this->assertFalse($vp->HasBrokenLink);
		
		// Unpublish the source page, confirm that the virtual page has also been unpublished
		$p->doUnpublish();

		// The draft VP still has the CopyContentFromID link
		$vp->flushCache();
		$vp = DataObject::get_by_id('SiteTree', $vp->ID);
		$this->assertEquals($p->ID, $vp->CopyContentFromID);

		$vpLive = Versioned::get_one_by_stage('SiteTree', 'Live', '"SiteTree"."ID" = ' . $vp->ID);
		$this->assertNull($vpLive);
		
		// Delete from draft, confirm that the virtual page has a broken link on the draft site
		$p->delete();
		$vp->flushCache();
		$vp = DataObject::get_by_id('SiteTree', $vp->ID);
		$this->assertEquals(1, $vp->HasBrokenLink);
	}	

	public function testDeletingFromLiveSourcePageOfAVirtualPageAlsoUnpublishesVirtualPage() {
		// Create page and virutal page
		$p = new Page();
		$p->Title = "source";
		$p->write();
		$this->assertTrue($p->doPublish());
		$vp = new VirtualPage();
		$vp->CopyContentFromID = $p->ID;
		$vp->write();
		$this->assertTrue($vp->doPublish());
		
		// All is fine, the virtual page doesn't have a broken link
		$this->assertFalse($vp->HasBrokenLink);
		
		// Delete the source page from draft, confirm that this creates a broken link
		$pID = $p->ID;
		$p->delete();
		$vp->flushCache();
		$vp = DataObject::get_by_id('SiteTree', $vp->ID);
		$this->assertEquals(1, $vp->HasBrokenLink);
		
		// Delete the source page form live, confirm that the virtual page has also been unpublished
		$pLive = Versioned::get_one_by_stage('SiteTree', 'Live', '"SiteTree"."ID" = ' . $pID);
		$this->assertTrue($pLive->doDeleteFromLive());
		$vpLive = Versioned::get_one_by_stage('SiteTree', 'Live', '"SiteTree"."ID" = ' . $vp->ID);
		$this->assertNull($vpLive);
		
		// Delete from draft, confirm that the virtual page has a broken link on the draft site
		$pLive->delete();
		$vp->flushCache();
		$vp = DataObject::get_by_id('SiteTree', $vp->ID);
		$this->assertEquals(1, $vp->HasBrokenLink);
	}	
	
	/**
	 * Base functionality tested in {@link SiteTreeTest->testAllowedChildrenValidation()}.
	 */
	public function testAllowedChildrenLimitedOnVirtualPages() {
		$classA = new SiteTreeTest_ClassA();
		$classA->write();
		$classB = new SiteTreeTest_ClassB();
		$classB->write();
		$classBVirtual = new VirtualPage();
		$classBVirtual->CopyContentFromID = $classB->ID;
		$classBVirtual->write();
		$classC = new SiteTreeTest_ClassC();
		$classC->write();
		$classCVirtual = new VirtualPage();
		$classCVirtual->CopyContentFromID = $classC->ID;
		$classCVirtual->write();
		
		$classBVirtual->ParentID = $classA->ID;
		$valid = $classBVirtual->doValidate();
		$this->assertTrue($valid->valid(), "Does allow child linked to virtual page type allowed by parent");
		
		$classCVirtual->ParentID = $classA->ID;
		$valid = $classCVirtual->doValidate();
		$this->assertFalse($valid->valid(), "Doesn't allow child linked to virtual page type disallowed by parent");
	}
	
	public function testGetVirtualFields() {
		// Needs association with an original, otherwise will just return the "base" virtual fields
		$page = new VirtualPageTest_ClassA();
		$page->write();
		$virtual = new VirtualPage();
		$virtual->CopyContentFromID = $page->ID;
		$virtual->write();

		$this->assertContains('MyVirtualField', $virtual->getVirtualFields());
		$this->assertNotContains('MyNonVirtualField', $virtual->getVirtualFields());
		$this->assertNotContains('MyInitiallyCopiedField', $virtual->getVirtualFields());
	}
	
	public function testCopyFrom() {
		$original = new VirtualPageTest_ClassA();
		$original->MyInitiallyCopiedField = 'original';
		$original->MyVirtualField = 'original';
		$original->MyNonVirtualField = 'original';
		$original->write();

		$virtual = new VirtualPage();
		$virtual->CopyContentFromID = $original->ID;
		$virtual->write();
		
		$virtual->copyFrom($original);
		// Using getField() to avoid side effects from an overloaded __get()
		$this->assertEquals(
			'original', 
			$virtual->getField('MyInitiallyCopiedField'),
			'Fields listed in $initially_copied_fields are copied on first copyFrom() invocation'
		);
		$this->assertEquals(
			'original', 
			$virtual->getField('MyVirtualField'),
			'Fields not listed in $initially_copied_fields are copied in copyFrom()'
		);
		$this->assertNull(
			$virtual->getField('MyNonVirtualField'),
			'Fields listed in $non_virtual_fields are not copied in copyFrom()'
		);
		
		$original->MyInitiallyCopiedField = 'changed';
		$original->write();
		$virtual->copyFrom($original);
		$this->assertEquals(
			'original', 
			$virtual->MyInitiallyCopiedField,
			'Fields listed in $initially_copied_fields are not copied on subsequent copyFrom() invocations'
		);
	}
	
	public function testWriteWithoutVersion() {
		$original = new SiteTree();
		$original->write();
		// Create a second version (different behaviour),
		// as SiteTree->onAfterWrite() checks for Version == 1
		$original->Title = 'prepare';
		$original->write();
		$originalVersion = $original->Version;

		$virtual = new VirtualPage();
		$virtual->CopyContentFromID = $original->ID;
		$virtual->write();
		// Create a second version, see above.
		$virtual->Title = 'prepare';
		$virtual->write();
		$virtualVersion = $virtual->Version;
		
		$virtual->Title = 'changed 1';
		$virtual->writeWithoutVersion();
		$this->assertEquals(
			$virtual->Version, 
			$virtualVersion, 
			'writeWithoutVersion() on VirtualPage doesnt increment version'
		);

		$original->Title = 'changed 2';
		$original->writeWithoutVersion();

		DataObject::flush_and_destroy_cache();
		$virtual = DataObject::get_by_id('VirtualPage', $virtual->ID, false);
		$this->assertEquals(
			$virtual->Version, 
			$virtualVersion, 
			'writeWithoutVersion() on original page doesnt increment version on related VirtualPage'
		);
		
		$original->Title = 'changed 3';
		$original->write();
		DataObject::flush_and_destroy_cache();
		$virtual = DataObject::get_by_id('VirtualPage', $virtual->ID, false);
		$this->assertGreaterThan(
			$virtualVersion, 
			$virtual->Version, 
			'write() on original page does increment version on related VirtualPage'
		);
	}

	public function testCanBeRoot() {
		$page = new SiteTree();
		$page->ParentID = 0;
		$page->write();

		$notRootPage = new VirtualPageTest_NotRoot();
		// we don't want the original on root, but rather the VirtualPage pointing to it
		$notRootPage->ParentID = $page->ID; 
		$notRootPage->write();

		$virtual = new VirtualPage();
		$virtual->CopyContentFromID = $page->ID;
		$virtual->write();

		$virtual = DataObject::get_by_id('VirtualPage', $virtual->ID, false);
		$virtual->CopyContentFromID = $notRootPage->ID;
		$virtual->flushCache();

		$isDetected = false;
		try {
			$virtual->write();
		} catch(ValidationException $e) {
			$this->assertContains('is not allowed on the root level', $e->getMessage());
			$isDetected = true;
		} 

		if(!$isDetected) $this->fail('Fails validation with $can_be_root=false');
	}

	public function testPageTypeChangeDoesntKeepOrphanedVirtualPageRecord() {
		$page = new SiteTree();
		$page->write();
		$page->publish('Stage', 'Live');

		$virtual = new VirtualPageTest_VirtualPageSub();
		$virtual->CopyContentFromID = $page->ID;
		$virtual->write();
		$virtual->publish('Stage', 'Live');

		$nonVirtual = $virtual;
		$nonVirtual->ClassName = 'VirtualPageTest_ClassA';
		$nonVirtual->write(); // not publishing

		$this->assertNotNull(
			DB::query(sprintf('SELECT "ID" FROM "SiteTree" WHERE "ID" = %d', $nonVirtual->ID))->value(),
			"Shared base database table entry exists after type change"
		);
		$this->assertNull(
			DB::query(sprintf('SELECT "ID" FROM "VirtualPage" WHERE "ID" = %d', $nonVirtual->ID))->value(),
			"Base database table entry no longer exists after type change"
		);
		$this->assertNull(
			DB::query(sprintf('SELECT "ID" FROM "VirtualPageTest_VirtualPageSub" WHERE "ID" = %d', $nonVirtual->ID))->value(),
			"Sub database table entry no longer exists after type change"
		);
		$this->assertNull(
			DB::query(sprintf('SELECT "ID" FROM "VirtualPage_Live" WHERE "ID" = %d', $nonVirtual->ID))->value(),
			"Base live database table entry no longer exists after type change"
		);
		$this->assertNull(
			DB::query(sprintf('SELECT "ID" FROM "VirtualPageTest_VirtualPageSub_Live" WHERE "ID" = %d', $nonVirtual->ID))->value(),
			"Sub live database table entry no longer exists after type change"
		);
	}

	public function testPageTypeChangePropagatesToLive() {
		$page = new SiteTree();
		$page->MySharedNonVirtualField = 'original';
		$page->write();
		$page->publish('Stage', 'Live');

		$virtual = new VirtualPageTest_VirtualPageSub();
		$virtual->CopyContentFromID = $page->ID;
		$virtual->write();
		$virtual->publish('Stage', 'Live');

		$page->Title = 'original'; // 'Title' is a virtual field
		// Publication would causes the virtual field to copy through onBeforeWrite(),
		// but we want to test that it gets copied on class name change instead
		$page->write();

		$nonVirtual = $virtual;
		$nonVirtual->ClassName = 'VirtualPageTest_ClassA';
		$nonVirtual->MySharedNonVirtualField = 'changed on new type';
		$nonVirtual->write(); // not publishing the page type change here

		$this->assertEquals('original', $nonVirtual->Title,
			'Copies virtual fields from original draft into new instance on type change '
		);

		$nonVirtualLive = Versioned::get_one_by_stage('SiteTree', 'Live', '"SiteTree_Live"."ID" = ' . $nonVirtual->ID);
		$this->assertNotNull($nonVirtualLive);
		$this->assertEquals('VirtualPageTest_ClassA', $nonVirtualLive->ClassName);
		$this->assertEquals('changed on new type', $nonVirtualLive->MySharedNonVirtualField);

		$page->MySharedNonVirtualField = 'changed only on original';
		$page->write();
		$page->publish('Stage', 'Live');

		$nonVirtualLive = Versioned::get_one_by_stage('SiteTree', 'Live', '"SiteTree_Live"."ID" = ' . $nonVirtual->ID, false);
		$this->assertEquals('changed on new type', $nonVirtualLive->MySharedNonVirtualField,
			'No field copying from previous original after page type changed'
		);
	}

	public function testVirtualPageFindsCorrectCasting() {
		$page = new VirtualPageTest_ClassA();
		$page->CastingTest = "Some content";
		$page->write();
		$virtual = new VirtualPage();
		$virtual->CopyContentFromID = $page->ID;
		$virtual->write();

		$this->assertEquals('VirtualPageTest_TestDBField', $virtual->castingHelper('CastingTest'));
		$this->assertEquals('SOME CONTENT', $virtual->obj('CastingTest')->forTemplate());
	}

	public function testVirtualPageAsAnAllowedChild() {
		$parentPage = new VirtualPageTest_PageWithAllowedChildren();
		$parentPage->write();

		$childPage = new VirtualPageTest_ClassA();
		$childPage->ParentID = $parentPage->ID;
		$childPage->write();

		// Check we're allowed to create a VirtualPage without linking it to a page yet
		$childVirtualPage = new VirtualPage();
		$childVirtualPage->ParentID = $parentPage->ID;
		try {
			$childVirtualPage->write();
		} catch(ValidationException $e) {
			$this->fail('Failed to write VirtualPage when it is an allowed child');
		}

		// Check that we can link a VirtualPage to a page type that's an allowed child
		$childVirtualPage->CopyContentFromID = $childPage->ID;
		try {
			$childVirtualPage->write();
		} catch(ValidationException $e) {
			$this->fail('Failed to write VirtualPage when it is linked to an allowed child');
		}

		// Check that we CAN'T link a VirtualPage to a page that is NOT an allowed child
		$disallowedChild = new VirtualPageTest_ClassB();
		$disallowedChild->write();
		$childVirtualPage->CopyContentFromID = $disallowedChild->ID;
		$isDetected = false;
		try {
			$childVirtualPage->write();
		} catch(ValidationException $e) {
			$this->assertContains('not allowed as child of this parent page', $e->getMessage());
			$isDetected = true;
		} 

		if(!$isDetected) $this->fail("Shouldn't be allowed to write a VirtualPage that links to a disallowed child");
	}

	public function testVirtualPagePointingToRedirectorPage() {
		if (!class_exists('RedirectorPage')) {
			$this->markTestSkipped('RedirectorPage required');
		}

		$rp = new RedirectorPage(array('ExternalURL' => 'http://google.com', 'RedirectionType' => 'External'));
		$rp->write();
		$rp->doPublish();
		
		$vp = new VirtualPage(array('URLSegment' => 'vptest', 'CopyContentFromID' => $rp->ID));
		$vp->write();
		$vp->doPublish();

		$response = $this->get($vp->Link());
		$this->assertEquals(301, $response->getStatusCode());
		$this->assertEquals('http://google.com', $response->getHeader('Location'));
	}
}

class VirtualPageTest_ClassA extends Page implements TestOnly {
	
	private static $db = array(
		'MyInitiallyCopiedField' => 'Text',
		'MyVirtualField' => 'Text',
		'MyNonVirtualField' => 'Text',
		'CastingTest' => 'VirtualPageTest_TestDBField'
	);
	
	private static $allowed_children = array('VirtualPageTest_ClassB');
}

class VirtualPageTest_ClassB extends Page implements TestOnly {
	private static $allowed_children = array('VirtualPageTest_ClassC'); 
}

class VirtualPageTest_ClassC extends Page implements TestOnly {
	private static $allowed_children = array();
}

class VirtualPageTest_NotRoot extends Page implements TestOnly {
	private static $can_be_root = false;
}

class VirtualPageTest_TestDBField extends Varchar implements TestOnly {
	public function forTemplate() {
		return strtoupper($this->XML());
	}
}

class VirtualPageTest_VirtualPageSub extends VirtualPage implements TestOnly {
	private static $db = array(
		'MyProperty' => 'Varchar',
	);
}

class VirtualPageTest_PageExtension extends DataExtension implements TestOnly {

	private static $db = array(
		// These fields are just on an extension to simulate shared properties between Page and VirtualPage.
		// Not possible through direct $db definitions due to VirtualPage inheriting from Page, and Page being defined elsewhere.
		'MySharedVirtualField' => 'Text',
		'MySharedNonVirtualField' => 'Text',
	);

}

class VirtualPageTest_PageWithAllowedChildren extends Page implements TestOnly {
	private static $allowed_children = array(
		'VirtualPageTest_ClassA',
		'VirtualPage'
	);
}
