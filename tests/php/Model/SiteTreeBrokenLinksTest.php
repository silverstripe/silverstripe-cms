<?php

namespace SilverStripe\CMS\Tests\Model;

use Page;
use Silverstripe\Assets\Dev\TestAssetStore;
use SilverStripe\CMS\Model\RedirectorPage;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\CMS\Model\SiteTreeLink;
use SilverStripe\CMS\Model\VirtualPage;
use SilverStripe\CMS\Tests\Model\SiteTreeBrokenLinksTest\NotPageObject;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\Versioned\Versioned;

/**
 * Tests {@see SiteTreeLinkTracking} broken links feature: LinkTracking
 */
class SiteTreeBrokenLinksTest extends SapphireTest
{
    protected static $fixture_file = 'SiteTreeBrokenLinksTest.yml';

    protected static $extra_dataobjects = [
        NotPageObject::class,
    ];

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

        $obj->Content = '<a href="[sitetree_link,id=' . $this->idFromFixture(
            'Page',
            'about'
        ) . ']">this is not a broken link</a>';
        $obj->syncLinkTracking();
        $this->assertFalse($obj->HasBrokenLink, 'Page does NOT have a broken link');
    }

    /**
     * Ensure broken links can be tracked between non-page objects
     */
    public function testBrokenLinksNonPage()
    {
        /** @var Page $aboutPage */
        $aboutPage = $this->objFromFixture('Page', 'about');

        /** @var NotPageObject $obj */
        $obj = $this->objFromFixture(NotPageObject::class, 'object1');
        $obj->Content = '<a href="[sitetree_link,id=3423423]">this is a broken link</a>';
        $obj->AnotherContent = '<a href="[sitetree_link,id=' . $aboutPage->ID . ']">this is not a broken link</a>';
        $obj->write();

        // Two links created for this record
        $this->assertListEquals(
            [
                ['LinkedID' => 3423423],
                ['LinkedID' => $aboutPage->ID],
            ],
            SiteTreeLink::get()->filter([
                'ParentClass' => NotPageObject::class,
                'ParentID' => $obj->ID,
            ])
        );

        // ManyManyThrough relation only links to unbroken pages
        $this->assertListEquals(
            [
                ['Title' => 'About'],
            ],
            $obj->LinkTracking()
        );

        // About-page backlinks contains this object
        $this->assertListEquals(
            [
                ['ID' => $obj->ID]
            ],
            $aboutPage->BackLinkTracking()
        );
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

    public function testDeletingMarksBackLinkedPagesAsBroken()
    {
        // Set up two published pages with a link from content -> about
        $linkDest = $this->objFromFixture('Page', 'about');

        /** @var Page $linkSrc */
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
        $rp->RedirectionType = 'Internal';
        $rp->LinkToID = $p->ID;
        $rp->write();
        $this->assertTrue($rp->publishRecursive());

        // Confirm that there are no broken links to begin with
        $this->assertFalse($p2->HasBrokenLink);
        $this->assertFalse($rp->HasBrokenLink);

        // Unpublishing doesn't affect broken state on live (draft is source of truth)
        /** @var SiteTree $p2Live */
        $p2Live = Versioned::get_by_stage(SiteTree::class, Versioned::LIVE)->byID($p2->ID);
        /** @var SiteTree $rpLive */
        $rpLive = Versioned::get_by_stage(SiteTree::class, Versioned::LIVE)->byID($rp->ID);
        $this->assertEquals(0, $p2Live->HasBrokenLink);
        $this->assertEquals(0, $rpLive->HasBrokenLink);

        // Delete the source page, confirm that the VP, RP and page 2 have broken links on draft
        $p->delete();
        $p2->flushCache();
        /** @var SiteTree $p2 */
        $p2 = DataObject::get_by_id(SiteTree::class, $p2->ID);
        $rp->flushCache();
        /** @var RedirectorPage $rp */
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
        $redirectorPage->RedirectionType = 'Internal';
        $redirectorPage->LinkToID = $page->ID;
        $redirectorPage->write();
        $this->assertTrue($redirectorPage->publishRecursive());

        // Confirm that there are no broken links to begin with
        $this->assertFalse($page2->HasBrokenLink);
        $this->assertFalse($redirectorPage->HasBrokenLink);

        // Delete from draft and confirm that broken links are marked
        $page->delete();

        $page2->flushCache();
        /** @var SiteTree $page2 */
        $page2 = DataObject::get_by_id(SiteTree::class, $page2->ID);
        $redirectorPage->flushCache();
        /** @var RedirectorPage $redirectorPage */
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
