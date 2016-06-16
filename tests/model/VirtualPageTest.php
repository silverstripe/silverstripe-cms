<?php


use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Versioning\Versioned;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\ValidationException;
use SilverStripe\ORM\FieldType\DBVarchar;
use SilverStripe\ORM\DataExtension;


class VirtualPageTest extends FunctionalTest {
	protected static $fixture_file = 'VirtualPageTest.yml';
	protected static $use_draft_site = false;
	protected $autoFollowRedirection = false;

	protected $extraDataObjects = array(
		'VirtualPageTest_ClassA',
		'VirtualPageTest_ClassB',
		'VirtualPageTest_ClassC',
		'VirtualPageTest_NotRoot',
		'VirtualPageTest_PageExtension',
		'VirtualPageTest_PageWithAllowedChildren',
		'VirtualPageTest_TestDBField',
		'VirtualPageTest_VirtualPageSub',
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

		// Add extra fields
		Config::inst()->update('VirtualPage', 'initially_copied_fields', array('MyInitiallyCopiedField'));
		Config::inst()->update('VirtualPage', 'non_virtual_fields', array('MyNonVirtualField', 'MySharedNonVirtualField'));
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
		$master->publishRecursive();

		$master->Title = "New title";
		$master->MenuTitle = "New menutitle";
		$master->Content = "<p>New content</p>";
		$master->write();

		$vp1 = DataObject::get_by_id("VirtualPage", $this->idFromFixture('VirtualPage', 'vp1'));
		$vp2 = DataObject::get_by_id("VirtualPage", $this->idFromFixture('VirtualPage', 'vp2'));
		$this->assertTrue($vp1->publishRecursive());
		$this->assertTrue($vp2->publishRecursive());

		$master->publishRecursive();

		Versioned::set_stage(Versioned::LIVE);
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
		Versioned::set_stage(Versioned::DRAFT);
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
		$p->publishRecursive();

		// Virtual page has this content
		$vp = new VirtualPage();
		$vp->CopyContentFromID = $p->ID;
		$vp->write();

		$vp->publishRecursive();

		// Don't publish this change - published page will still say 'published content'
		$p->Content = "draft content";
		$p->write();

		// The draft content of the virtual page should say 'draft content'
		/** @var VirtualPage $vpDraft */
		$vpDraft = Versioned::get_by_stage("VirtualPage", Versioned::DRAFT)->byID($vp->ID);
		$this->assertEquals('draft content', $vpDraft->CopyContentFrom()->Content);
		$this->assertEquals('draft content', $vpDraft->Content);

		// The published content of the virtual page should say 'published content'
		/** @var VirtualPage $vpLive */
		$vpLive = Versioned::get_by_stage("VirtualPage", Versioned::LIVE)->byID($vp->ID);
		$this->assertEquals('published content', $vpLive->CopyContentFrom()->Content);
		$this->assertEquals('published content', $vpLive->Content);

		// Publishing the virtualpage should, however, trigger publishing of the live page
		$vpDraft->publishRecursive();

		// Everything is published live
		$vpLive = Versioned::get_by_stage("VirtualPage", Versioned::LIVE)->byID($vp->ID);
		$this->assertEquals('draft content', $vpLive->CopyContentFrom()->Content);
		$this->assertEquals('draft content', $vpLive->Content);
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
		$p->publishRecursive();
		$this->assertTrue($vp->canPublish());
	}

	public function testCanDeleteOrphanedVirtualPagesFromLive() {
		// An unpublished source page
		$p = new Page();
		$p->Content = "test content";
		$p->write();
		$p->publishRecursive();
		$pID = $p->ID;

		$vp = new VirtualPage();
		$vp->CopyContentFromID = $p->ID;
		$vp->write();
		$this->assertTrue($vp->canPublish());
		$this->assertTrue($vp->publishRecursive());

		// Delete the source page semi-manually, without triggering
		// the cascade publish back to the virtual page.
		Versioned::set_stage(Versioned::LIVE);
		$livePage = Versioned::get_by_stage('SiteTree', Versioned::LIVE)->byID($pID);
		$livePage->delete();
		Versioned::set_stage(Versioned::DRAFT);

		// Confirm that we can unpublish, but not publish
		$this->assertFalse($p->IsPublished(), 'Copied page has orphaned the virtual page on live');
		$this->assertTrue($vp->isPublished(), 'Virtual page remains on live');
		$this->assertTrue($vp->canUnpublish());
		$this->assertFalse($vp->canPublish());

		// Confirm that the action really works
		$this->assertTrue($vp->doUnpublish());
		$this->assertEquals(
			0,
			DB::prepared_query(
				"SELECT count(*) FROM \"SiteTree_Live\" WHERE \"ID\" = ?",
				array($vp->ID)
			)->value()
		);
	}

	public function testCanEdit() {
		$parentPage = $this->objFromFixture('Page', 'master3');
		$virtualPage = $this->objFromFixture('VirtualPage', 'vp3');
		$bob = $this->objFromFixture('Member', 'bob');
		$andrew = $this->objFromFixture('Member', 'andrew');

		// Bob can edit the mirrored page, but he shouldn't be able to edit the virtual page.
		$this->logInAs($bob);
		$this->assertTrue($parentPage->canEdit());
		$this->assertFalse($virtualPage->canEdit());

		//  Andrew can only edit the virtual page, but not the original.
		$this->logInAs($andrew);
		$this->assertFalse($parentPage->canEdit());
		$this->assertTrue($virtualPage->canEdit());
	}

	public function testCanView() {
		$parentPage = $this->objFromFixture('Page', 'master3');
		$parentPage->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
		$virtualPage = $this->objFromFixture('VirtualPage', 'vp3');
		$virtualPage->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
		$cindy = $this->objFromFixture('Member', 'cindy');
		$alice = $this->objFromFixture('Member', 'alice');

		// Cindy can see both pages
		$this->logInAs($cindy);
		$this->assertTrue($parentPage->canView());
		$this->assertTrue($virtualPage->canView());

		// Alice can't see the virtual page, since it's restricted to cindy
		$this->logInAs($alice);
		$this->assertTrue($parentPage->canView());
		$this->assertFalse($virtualPage->canView());
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
		$p->publishRecursive();
		$this->assertTrue($vp->getIsAddedToStage());

		// A new VP created after P's initial construction
		$vp2 = new VirtualPage();
		$vp2->CopyContentFromID = $p->ID;
		$vp2->write();
		$this->assertTrue($vp2->getIsAddedToStage());

		// Also remains orange after a republish
		$p->Content = "new content";
		$p->write();
		$p->publishRecursive();
		$this->assertTrue($vp2->getIsAddedToStage());

		// VP is now published
		$vp->publishRecursive();

		$this->assertTrue($vp->getExistsOnLive());
		$this->assertFalse($vp->getIsModifiedOnStage());

		// P edited, P goes green. Change set interface should indicate to the user that the owned page has
		// modifications, although the virtual page record itself will not appear as having pending changes.
		$p->Content = "third content";
		$p->write();

		$this->assertTrue($p->getIsModifiedOnStage());
		$this->assertFalse($vp->getIsModifiedOnStage());

		// Publish, VP goes black
		$p->publishRecursive();
		$this->assertTrue($vp->getExistsOnLive());
		$this->assertFalse($vp->getIsModifiedOnStage());
	}

	public function testUnpublishingSourcePageOfAVirtualPageAlsoUnpublishesVirtualPage() {
		// Create page and virutal page
		$p = new Page();
		$p->Title = "source";
		$p->write();
		$this->assertTrue($p->publishRecursive());
		$vp = new VirtualPage();
		$vp->CopyContentFromID = $p->ID;
		$vp->write();
		$this->assertTrue($vp->publishRecursive());

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
		$this->assertTrue($p->publishRecursive());
		$vp = new VirtualPage();
		$vp->CopyContentFromID = $p->ID;
		$vp->write();
		$this->assertTrue($vp->publishRecursive());

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
		$this->assertTrue($pLive->doUnpublish());
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
		$this->assertEquals(
			'original',
			$virtual->MyInitiallyCopiedField,
			'Fields listed in $initially_copied_fields are not copied on subsequent copyFrom() invocations'
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

	public function testPageTypeChangePropagatesToLive() {
		$page = new SiteTree();
		$page->Title = 'published title';
		$page->MySharedNonVirtualField = 'original';
		$page->write();
		$page->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);

		$virtual = new VirtualPageTest_VirtualPageSub();
		$virtual->CopyContentFromID = $page->ID;
		$virtual->MySharedNonVirtualField = 'virtual published field';
		$virtual->write();
		$virtual->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);

		$page->Title = 'original'; // 'Title' is a virtual field
		// Publication would causes the virtual field to copy through onBeforeWrite(),
		// but we want to test that it gets copied on class name change instead
		$page->write();


		$nonVirtual = $virtual;
		$nonVirtual->ClassName = 'VirtualPageTest_ClassA';
		$nonVirtual->MySharedNonVirtualField = 'changed on new type';
		$nonVirtual->write(); // not publishing the page type change here

		// Stage record is changed to the new type and no longer acts as a virtual page
		$nonVirtualStage = Versioned::get_one_by_stage('SiteTree', 'Stage', '"SiteTree"."ID" = ' . $nonVirtual->ID, false);
		$this->assertNotNull($nonVirtualStage);
		$this->assertEquals('VirtualPageTest_ClassA', $nonVirtualStage->ClassName);
		$this->assertEquals('changed on new type', $nonVirtualStage->MySharedNonVirtualField);
		$this->assertEquals('original', $nonVirtualStage->Title,
			'Copies virtual fields from original draft into new instance on type change '
		);

		// Virtual page on live keeps working as it should
		$virtualLive = Versioned::get_one_by_stage('SiteTree', 'Live', '"SiteTree_Live"."ID" = ' . $virtual->ID, false);
		$this->assertNotNull($virtualLive);
		$this->assertEquals('VirtualPageTest_VirtualPageSub', $virtualLive->ClassName);
		$this->assertEquals('virtual published field', $virtualLive->MySharedNonVirtualField);
		$this->assertEquals('published title', $virtualLive->Title);

		// Change live page
		$page->Title = 'title changed on original';
		$page->MySharedNonVirtualField = 'changed only on original';
		$page->write();
		$page->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);

		// Virtual page only notices changes to virtualised fields (Title)
		$virtualLive = Versioned::get_one_by_stage('SiteTree', 'Live', '"SiteTree_Live"."ID" = ' . $virtual->ID, false);
		$this->assertEquals('virtual published field', $virtualLive->MySharedNonVirtualField);
		$this->assertEquals('title changed on original', $virtualLive->Title);
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
		$rp->publishRecursive();

		$vp = new VirtualPage(array('URLSegment' => 'vptest', 'CopyContentFromID' => $rp->ID));
		$vp->write();
		$vp->publishRecursive();

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

class VirtualPageTest_TestDBField extends DBVarchar implements TestOnly {
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
