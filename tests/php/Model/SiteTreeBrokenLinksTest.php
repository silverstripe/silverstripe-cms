<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Versioned\Versioned;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\CMS\Model\VirtualPage;
use SilverStripe\CMS\Model\RedirectorPage;
use SilverStripe\Assets\File;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Assets\Tests\Storage\AssetStoreTest\TestAssetStore;
use Page;

/**
 * Tests {@see SiteTreeLinkTracking} broken links feature: LinkTracking
 */
class SiteTreeBrokenLinksTest extends SapphireTest
{
    protected static $fixture_file = 'SiteTreeBrokenLinksTest.yml';

    public function setUp()
    {
        parent::setUp();

        Versioned::set_stage(Versioned::DRAFT);
        TestAssetStore::activate('SiteTreeBrokenLinksTest');
        $this->logInWithPermission('ADMIN');
    }

    public function tearDown()
    {
        TestAssetStore::reset();
        parent::tearDown();
    }

    public function testBrokenLinksBetweenPages()
    {
        /** @var Page $obj */
        $obj = $this->objFromFixture('Page', 'content');

        $obj->Content = '<a href="[sitetree_link,id=3423423]">this is a broken link</a>';
        $obj->syncLinkTracking();
        $this->assertTrue($obj->HasBrokenLink, 'Page has a broken link');

        $obj->Content = '<a href="[sitetree_link,id=' . $this->idFromFixture('Page', 'about') .']">this is not a broken link</a>';
        $obj->syncLinkTracking();
        $this->assertFalse($obj->HasBrokenLink, 'Page does NOT have a broken link');
    }

    public function testBrokenAnchorBetweenPages()
    {
        /** @var Page $obj */
        $obj = $this->objFromFixture('Page', 'content');
        $target = $this->objFromFixture('Page', 'about');

        $obj->Content = "<a href=\"[sitetree_link,id={$target->ID}]#no-anchor-here\">this is a broken link</a>";
        $obj->syncLinkTracking();
        $this->assertTrue($obj->HasBrokenLink, 'Page has a broken link');

        $obj->Content = "<a href=\"[sitetree_link,id={$target->ID}]#yes-anchor-here\">this is not a broken link</a>";
        $obj->syncLinkTracking();
        $this->assertFalse($obj->HasBrokenLink, 'Page does NOT have a broken link');
    }

    public function testBrokenVirtualPages()
    {
        $obj = $this->objFromFixture('Page', 'content');
        $vp = new VirtualPage();

        $vp->CopyContentFromID = $obj->ID;
        $vp->syncLinkTracking();
        $this->assertFalse($vp->HasBrokenLink, 'Working virtual page is NOT marked as broken');

        $vp->CopyContentFromID = 12345678;
        $vp->syncLinkTracking();
        $this->assertTrue($vp->HasBrokenLink, 'Broken virtual page IS marked as such');
    }

    public function testBrokenInternalRedirectorPages()
    {
        $obj = $this->objFromFixture('Page', 'content');
        $rp = new RedirectorPage();

        $rp->RedirectionType = 'Internal';

        $rp->LinkToID = $obj->ID;
        $rp->syncLinkTracking();
        $this->assertFalse($rp->HasBrokenLink, 'Working redirector page is NOT marked as broken');

        $rp->LinkToID = 12345678;
        $rp->syncLinkTracking();
        $this->assertTrue($rp->HasBrokenLink, 'Broken redirector page IS marked as such');
    }

    public function testDeletingFileMarksBackedPagesAsBroken()
    {
        // Test entry
        $file = new File();
        $file->setFromString('test', 'test-file.txt');
        $file->write();

        /** @var Page $obj */
        $obj = $this->objFromFixture('Page', 'content');
        $obj->Content = sprintf(
            '<p><a href="[file_link,id=%d]">Working Link</a></p>',
            $file->ID
        );
        $obj->write();
        $this->assertTrue($obj->publishRecursive());
        // Confirm that it isn't marked as broken to begin with

        $obj = SiteTree::get()->byID($obj->ID);
        $this->assertEquals(0, $obj->HasBrokenFile);

        $liveObj = Versioned::get_one_by_stage(SiteTree::class, Versioned::LIVE, "\"SiteTree\".\"ID\" = $obj->ID");
        $this->assertEquals(0, $liveObj->HasBrokenFile);

        // Delete the file
        $file->delete();

        // Confirm that it is marked as broken in stage
        $obj = SiteTree::get()->byID($obj->ID);
        $this->assertEquals(1, $obj->HasBrokenFile);

        // Publishing this page marks it as broken on live too
        $obj->publishRecursive();
        $liveObj = Versioned::get_one_by_stage(SiteTree::class, Versioned::LIVE, "\"SiteTree\".\"ID\" = $obj->ID");
        $this->assertEquals(1, $liveObj->HasBrokenFile);
    }

    public function testDeletingMarksBackLinkedPagesAsBroken()
    {
        // Set up two published pages with a link from content -> about
        $linkDest = $this->objFromFixture('Page', 'about');

        $linkSrc = $this->objFromFixture('Page', 'content');
        $linkSrc->Content = "<p><a href=\"[sitetree_link,id=$linkDest->ID]\">about us</a></p>";
        $linkSrc->write();

        // Confirm no broken link
        $this->assertEquals(0, (int)$linkSrc->HasBrokenLink);

        // Delete page from draft
        $linkDest->delete();

        // Confirm draft has broken link
        $linkSrc->flushCache();
        $linkSrc = $this->objFromFixture('Page', 'content');

        $this->assertEquals(1, (int)$linkSrc->HasBrokenLink);
    }

    public function testPublishingSourceBeforeDestHasBrokenLink()
    {
        $this->logInWithPermission('ADMIN');

        // Set up two draft pages with a link from content -> about
        /** @var Page $linkDest */
        $linkDest = $this->objFromFixture('Page', 'about');
        // Ensure that it's not on the published site
        $linkDest->doUnpublish();

        /** @var Page $linkSrc */
        $linkSrc = $this->objFromFixture('Page', 'content');
        $linkSrc->Content = "<p><a href=\"[sitetree_link,id=$linkDest->ID]\">about us</a></p>";
        $linkSrc->write();

        // Publish the source of the link, while the dest is still unpublished.
        $linkSrc->publishRecursive();

        // Verify that the link is not marked as broken on draft (source of truth)
        $this->assertEquals(0, (int)$linkSrc->HasBrokenLink);

        // Live doesn't have separate broken link tracking
        $this->assertEquals(0, DB::query("SELECT \"HasBrokenLink\" FROM \"SiteTree_Live\"
			WHERE \"ID\" = $linkSrc->ID")->value());
    }

    public function testRestoreFixesBrokenLinks()
    {
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

        // Redirector links are a third
        $rp = new RedirectorPage();
        $rp->Title = "redirector";
        $rp->LinkType = 'Internal';
        $rp->LinkToID = $p->ID;
        $rp->write();
        $this->assertTrue($rp->publishRecursive());

        // Confirm that there are no broken links to begin with
        $this->assertFalse($p2->HasBrokenLink);
        $this->assertFalse($rp->HasBrokenLink);

        // Unpublishing doesn't affect broken state on live (draft is source of truth)
        $p->doUnpublish();
        $p2Live = Versioned::get_one_by_stage(SiteTree::class, 'Live', '"SiteTree"."ID" = ' . $p2->ID);
        $rpLive = Versioned::get_one_by_stage(SiteTree::class, 'Live', '"SiteTree"."ID" = ' . $rp->ID);
        $this->assertEquals(0, $p2Live->HasBrokenLink);
        $this->assertEquals(0, $rpLive->HasBrokenLink);

        // Delete the source page, confirm that the VP, RP and page 2 have broken links on draft
        $p->delete();
        $p2->flushCache();
        $p2 = DataObject::get_by_id(SiteTree::class, $p2->ID);
        $rp->flushCache();
        $rp = DataObject::get_by_id(SiteTree::class, $rp->ID);
        $this->assertEquals(1, $p2->HasBrokenLink);
        $this->assertEquals(1, $rp->HasBrokenLink);

        // Restore the page to stage, confirm that this fixes the links
        /** @var SiteTree $p */
        $p = Versioned::get_latest_version(SiteTree::class, $pageID);
        $p->doRestoreToStage();

        $p2->flushCache();
        $p2 = DataObject::get_by_id(SiteTree::class, $p2->ID);
        $rp->flushCache();
        $rp = DataObject::get_by_id(SiteTree::class, $rp->ID);
        $this->assertFalse((bool)$p2->HasBrokenLink);
        $this->assertFalse((bool)$rp->HasBrokenLink);

        // Publish and confirm that the p2 and RP broken links are fixed on published
        $this->assertTrue($p->publishRecursive());
        $p2Live = Versioned::get_one_by_stage(SiteTree::class, 'Live', '"SiteTree"."ID" = ' . $p2->ID);
        $rpLive = Versioned::get_one_by_stage(SiteTree::class, 'Live', '"SiteTree"."ID" = ' . $rp->ID);
        $this->assertFalse((bool)$p2Live->HasBrokenLink);
        $this->assertFalse((bool)$rpLive->HasBrokenLink);
    }

    public function testRevertToLiveFixesBrokenLinks()
    {
        // Create page and virutal page
        $page = new Page();
        $page->Title = "source";
        $page->write();
        $pageID = $page->ID;
        $this->assertTrue($page->publishRecursive());

        // Content links are one kind of link to pages
        $page2 = new Page();
        $page2->Title = "regular link";
        $page2->Content = "<a href=\"[sitetree_link,id={$pageID}]\">test</a>";
        $page2->write();
        $this->assertTrue($page2->publishRecursive());

        // Redirector links are a third
        $redirectorPage = new RedirectorPage();
        $redirectorPage->Title = "redirector";
        $redirectorPage->LinkType = 'Internal';
        $redirectorPage->LinkToID = $page->ID;
        $redirectorPage->write();
        $this->assertTrue($redirectorPage->publishRecursive());

        // Confirm that there are no broken links to begin with
        $this->assertFalse($page2->HasBrokenLink);
        $this->assertFalse($redirectorPage->HasBrokenLink);

        // Delete from draft and confirm that broken links are marked
        $page->delete();

        $page2->flushCache();
        $page2 = DataObject::get_by_id(SiteTree::class, $page2->ID);
        $redirectorPage->flushCache();
        $redirectorPage = DataObject::get_by_id(SiteTree::class, $redirectorPage->ID);
        $this->assertEquals(1, $page2->HasBrokenLink);
        $this->assertEquals(1, $redirectorPage->HasBrokenLink);

        // Call doRevertToLive and confirm that broken links are restored
        /** @var Page $pageLive */
        $pageLive = Versioned::get_one_by_stage(SiteTree::class, 'Live', '"SiteTree"."ID" = ' . $pageID);
        $pageLive->doRevertToLive();

        $page2->flushCache();
        $page2 = DataObject::get_by_id(SiteTree::class, $page2->ID);
        $redirectorPage->flushCache();
        $redirectorPage = DataObject::get_by_id(SiteTree::class, $redirectorPage->ID);
        $this->assertFalse((bool)$page2->HasBrokenLink);
        $this->assertFalse((bool)$redirectorPage->HasBrokenLink);
    }

    public function testBrokenAnchorLinksInAPage()
    {
        /** @var Page $obj */
        $obj = $this->objFromFixture('Page', 'content');
        $origContent = $obj->Content;

        $obj->Content = $origContent . '<a href="#no-anchor-here">this links to a non-existent in-page anchor or skiplink</a>';
        $obj->syncLinkTracking();
        $this->assertTrue($obj->HasBrokenLink, 'Page has a broken anchor/skiplink');

        $obj->Content = $origContent . '<a href="#yes-anchor-here">this links to an existent in-page anchor/skiplink</a>';
        $obj->syncLinkTracking();
        $this->assertFalse($obj->HasBrokenLink, 'Page doesn\'t have a broken anchor or skiplink');
    }
}
