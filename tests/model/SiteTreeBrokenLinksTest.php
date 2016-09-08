<?php

use SilverStripe\ORM\Versioning\Versioned;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\CMS\Model\VirtualPage;
use SilverStripe\CMS\Model\RedirectorPage;
use SilverStripe\Assets\File;
use SilverStripe\Dev\SapphireTest;



/**
 * Tests {@see SiteTreeLinkTracking} broken links feature: LinkTracking
 *
 * @package cms
 * @subpackage tests
 */
class SiteTreeBrokenLinksTest extends SapphireTest {
	protected static $fixture_file = 'SiteTreeBrokenLinksTest.yml';

	public function setUp() {
		parent::setUp();

		Versioned::set_stage(Versioned::DRAFT);
		AssetStoreTest_SpyStore::activate('SiteTreeBrokenLinksTest');
		$this->logInWithPermission('ADMIN');
	}

	public function tearDown() {
		AssetStoreTest_SpyStore::reset();
		parent::tearDown();
	}

	public function testBrokenLinksBetweenPages() {
		$obj = $this->objFromFixture('Page','content');

		$obj->Content = '<a href="[sitetree_link,id=3423423]">this is a broken link</a>';
		$obj->syncLinkTracking();
		$this->assertTrue($obj->HasBrokenLink, 'Page has a broken link');

		$obj->Content = '<a href="[sitetree_link,id=' . $this->idFromFixture('Page','about') .']">this is not a broken link</a>';
		$obj->syncLinkTracking();
		$this->assertFalse($obj->HasBrokenLink, 'Page does NOT have a broken link');
	}

	public function testBrokenAnchorBetweenPages() {
		$obj = $this->objFromFixture('Page','content');
		$target = $this->objFromFixture('Page', 'about');

		$obj->Content = "<a href=\"[sitetree_link,id={$target->ID}]#no-anchor-here\">this is a broken link</a>";
		$obj->syncLinkTracking();
		$this->assertTrue($obj->HasBrokenLink, 'Page has a broken link');

		$obj->Content = "<a href=\"[sitetree_link,id={$target->ID}]#yes-anchor-here\">this is not a broken link</a>";
		$obj->syncLinkTracking();
		$this->assertFalse($obj->HasBrokenLink, 'Page does NOT have a broken link');
	}

	public function testBrokenVirtualPages() {
		$obj = $this->objFromFixture('Page','content');
		$vp = new VirtualPage();

		$vp->CopyContentFromID = $obj->ID;
		$vp->syncLinkTracking();
		$this->assertFalse($vp->HasBrokenLink, 'Working virtual page is NOT marked as broken');

		$vp->CopyContentFromID = 12345678;
		$vp->syncLinkTracking();
		$this->assertTrue($vp->HasBrokenLink, 'Broken virtual page IS marked as such');
	}

	public function testBrokenInternalRedirectorPages() {
		$obj = $this->objFromFixture('Page','content');
		$rp = new RedirectorPage();

		$rp->RedirectionType = 'Internal';

		$rp->LinkToID = $obj->ID;
		$rp->syncLinkTracking();
		$this->assertFalse($rp->HasBrokenLink, 'Working redirector page is NOT marked as broken');

		$rp->LinkToID = 12345678;
		$rp->syncLinkTracking();
		$this->assertTrue($rp->HasBrokenLink, 'Broken redirector page IS marked as such');
	}

	public function testDeletingFileMarksBackedPagesAsBroken() {
		// Test entry
		$file = new File();
		$file->setFromString('test', 'test-file.txt');
		$file->write();

		$obj = $this->objFromFixture('Page','content');
		$obj->Content = sprintf(
			'<p><a href="[file_link,id=%d]">Working Link</a></p>',
			$file->ID
		);
		$obj->write();
		$this->assertTrue($obj->publishRecursive());
		// Confirm that it isn't marked as broken to begin with
		$obj->flushCache();
		$obj = DataObject::get_by_id("SilverStripe\\CMS\\Model\\SiteTree", $obj->ID);
		$this->assertEquals(0, $obj->HasBrokenFile);

		$liveObj = Versioned::get_one_by_stage("SilverStripe\\CMS\\Model\\SiteTree", "Live","\"SiteTree\".\"ID\" = $obj->ID");
		$this->assertEquals(0, $liveObj->HasBrokenFile);

		// Delete the file
		$file->delete();

		// Confirm that it is marked as broken in stage
		$obj->flushCache();
		$obj = DataObject::get_by_id("SilverStripe\\CMS\\Model\\SiteTree", $obj->ID);
		$this->assertEquals(1, $obj->HasBrokenFile);

		// Publishing this page marks it as broken on live too
		$obj->publishRecursive();
		$liveObj = Versioned::get_one_by_stage("SilverStripe\\CMS\\Model\\SiteTree", "Live", "\"SiteTree\".\"ID\" = $obj->ID");
		$this->assertEquals(1, $liveObj->HasBrokenFile);
	}

	public function testDeletingMarksBackLinkedPagesAsBroken() {
		// Set up two published pages with a link from content -> about
		$linkDest = $this->objFromFixture('Page','about');

		$linkSrc = $this->objFromFixture('Page','content');
		$linkSrc->Content = "<p><a href=\"[sitetree_link,id=$linkDest->ID]\">about us</a></p>";
		$linkSrc->write();

		// Confirm no broken link
		$this->assertEquals(0, (int)$linkSrc->HasBrokenLink);

		// Delete page from draft
		$linkDestID = $linkDest->ID;
		$linkDest->delete();

		// Confirm draft has broken link
		$linkSrc->flushCache();
		$linkSrc = $this->objFromFixture('Page', 'content');

		$this->assertEquals(1, (int)$linkSrc->HasBrokenLink);
	}

	public function testPublishingSourceBeforeDestHasBrokenLink() {
		$this->markTestSkipped("Test disabled until versioned many_many implemented");

		$this->logInWithPermission('ADMIN');

		// Set up two draft pages with a link from content -> about
		$linkDest = $this->objFromFixture('Page','about');
		// Ensure that it's not on the published site
		$linkDest->doUnpublish();

		$linkSrc = $this->objFromFixture('Page','content');
		$linkSrc->Content = "<p><a href=\"[sitetree_link,id=$linkDest->ID]\">about us</a></p>";
		$linkSrc->write();

		// Publish the source of the link, while the dest is still unpublished.
		$linkSrc->publishRecursive();

		// Verify that the link isn't broken on draft but is broken on published
		$this->assertEquals(0, (int)$linkSrc->HasBrokenLink);
		$this->assertEquals(1, DB::query("SELECT \"HasBrokenLink\" FROM \"SiteTree_Live\"
			WHERE \"ID\" = $linkSrc->ID")->value());
	}

	public function testRestoreFixesBrokenLinks() {
		$this->markTestSkipped("Test disabled until versioned many_many implemented");
		// Create page and virtual page
		$p = new Page();
		$p->Title = "source";
		$p->write();
		$pageID = $p->ID;
		$this->assertTrue($p->publishRecursive());

		// Content links are one kind of link to pages
		$p2 = new Page();
		$p2->Title = "regular link";
		$p2->Content = "<a href=\"[sitetree_link,id=$p->ID]\">test</a>";
		$p2->write();
		$this->assertTrue($p2->publishRecursive());

		// Virtual pages are another
		$vp = new VirtualPage();
		$vp->CopyContentFromID = $p->ID;
		$vp->write();

		// Redirector links are a third
		$rp = new RedirectorPage();
		$rp->Title = "redirector";
		$rp->LinkType = 'Internal';
		$rp->LinkToID = $p->ID;
		$rp->write();
		$this->assertTrue($rp->publishRecursive());

		// Confirm that there are no broken links to begin with
		$this->assertFalse($p2->HasBrokenLink);
		$this->assertFalse($vp->HasBrokenLink);
		$this->assertFalse($rp->HasBrokenLink);

		// Unpublish the source page, confirm that the page 2 and RP has a broken link on published
		$p->doUnpublish();
		$p2Live = Versioned::get_one_by_stage('SilverStripe\\CMS\\Model\\SiteTree', 'Live', '"SiteTree"."ID" = ' . $p2->ID);
		$rpLive = Versioned::get_one_by_stage('SilverStripe\\CMS\\Model\\SiteTree', 'Live', '"SiteTree"."ID" = ' . $rp->ID);
		$this->assertEquals(1, $p2Live->HasBrokenLink);
		$this->assertEquals(1, $rpLive->HasBrokenLink);

		// Delete the source page, confirm that the VP, RP and page 2 have broken links on draft
		$p->delete();
		$vp->flushCache();
		$vp = DataObject::get_by_id('SilverStripe\\CMS\\Model\\SiteTree', $vp->ID);
		$p2->flushCache();
		$p2 = DataObject::get_by_id('SilverStripe\\CMS\\Model\\SiteTree', $p2->ID);
		$rp->flushCache();
		$rp = DataObject::get_by_id('SilverStripe\\CMS\\Model\\SiteTree', $rp->ID);
		$this->assertEquals(1, $p2->HasBrokenLink);
		$this->assertEquals(1, $vp->HasBrokenLink);
		$this->assertEquals(1, $rp->HasBrokenLink);

		// Restore the page to stage, confirm that this fixes the links
		$p = Versioned::get_latest_version('SilverStripe\\CMS\\Model\\SiteTree', $pageID);
		$p->doRestoreToStage();

		$p2->flushCache();
		$p2 = DataObject::get_by_id('SilverStripe\\CMS\\Model\\SiteTree', $p2->ID);
		$vp->flushCache();
		$vp = DataObject::get_by_id('SilverStripe\\CMS\\Model\\SiteTree', $vp->ID);
		$rp->flushCache();
		$rp = DataObject::get_by_id('SilverStripe\\CMS\\Model\\SiteTree', $rp->ID);
		$this->assertFalse((bool)$p2->HasBrokenLink);
		$this->assertFalse((bool)$vp->HasBrokenLink);
		$this->assertFalse((bool)$rp->HasBrokenLink);

		// Publish and confirm that the p2 and RP broken links are fixed on published
		$this->assertTrue($p->publishRecursive());
		$p2Live = Versioned::get_one_by_stage('SilverStripe\\CMS\\Model\\SiteTree', 'Live', '"SiteTree"."ID" = ' . $p2->ID);
		$rpLive = Versioned::get_one_by_stage('SilverStripe\\CMS\\Model\\SiteTree', 'Live', '"SiteTree"."ID" = ' . $rp->ID);
		$this->assertFalse((bool)$p2Live->HasBrokenLink);
		$this->assertFalse((bool)$rpLive->HasBrokenLink);

	}

	public function testRevertToLiveFixesBrokenLinks() {
		// Create page and virutal page
		$p = new Page();
		$p->Title = "source";
		$p->write();
		$pageID = $p->ID;
		$this->assertTrue($p->publishRecursive());

		// Content links are one kind of link to pages
		$p2 = new Page();
		$p2->Title = "regular link";
		$p2->Content = "<a href=\"[sitetree_link,id=$p->ID]\">test</a>";
		$p2->write();
		$this->assertTrue($p2->publishRecursive());

		// Virtual pages are another
		$vp = new VirtualPage();
		$vp->CopyContentFromID = $p->ID;
		$vp->write();

		// Redirector links are a third
		$rp = new RedirectorPage();
		$rp->Title = "redirector";
		$rp->LinkType = 'Internal';
		$rp->LinkToID = $p->ID;
		$rp->write();
		$this->assertTrue($rp->publishRecursive());

		// Confirm that there are no broken links to begin with
		$this->assertFalse($p2->HasBrokenLink);
		$this->assertFalse($vp->HasBrokenLink);
		$this->assertFalse($rp->HasBrokenLink);

		// Delete from draft and confirm that broken links are marked
		$pID = $p->ID;
		$p->delete();

		$vp->flushCache();
		$vp = DataObject::get_by_id('SilverStripe\\CMS\\Model\\SiteTree', $vp->ID);
		$p2->flushCache();
		$p2 = DataObject::get_by_id('SilverStripe\\CMS\\Model\\SiteTree', $p2->ID);
		$rp->flushCache();
		$rp = DataObject::get_by_id('SilverStripe\\CMS\\Model\\SiteTree', $rp->ID);
		$this->assertEquals(1, $p2->HasBrokenLink);
		$this->assertEquals(1, $vp->HasBrokenLink);
		$this->assertEquals(1, $rp->HasBrokenLink);

		// Call doRevertToLive and confirm that broken links are restored
		$pLive = Versioned::get_one_by_stage('SilverStripe\\CMS\\Model\\SiteTree', 'Live', '"SiteTree"."ID" = ' . $pID);
		$pLive->doRevertToLive();

		$p2->flushCache();
		$p2 = DataObject::get_by_id('SilverStripe\\CMS\\Model\\SiteTree', $p2->ID);
		$vp->flushCache();
		$vp = DataObject::get_by_id('SilverStripe\\CMS\\Model\\SiteTree', $vp->ID);
		$rp->flushCache();
		$rp = DataObject::get_by_id('SilverStripe\\CMS\\Model\\SiteTree', $rp->ID);
		$this->assertFalse((bool)$p2->HasBrokenLink);
		$this->assertFalse((bool)$vp->HasBrokenLink);
		$this->assertFalse((bool)$rp->HasBrokenLink);

	}

	public function testBrokenAnchorLinksInAPage() {
		$obj = $this->objFromFixture('Page','content');
		$origContent = $obj->Content;

		$obj->Content = $origContent . '<a href="#no-anchor-here">this links to a non-existent in-page anchor or skiplink</a>';
		$obj->syncLinkTracking();
		$this->assertTrue($obj->HasBrokenLink, 'Page has a broken anchor/skiplink');

		$obj->Content = $origContent . '<a href="#yes-anchor-here">this links to an existent in-page anchor/skiplink</a>';
		$obj->syncLinkTracking();
		$this->assertFalse($obj->HasBrokenLink, 'Page doesn\'t have a broken anchor or skiplink');
	}

}

