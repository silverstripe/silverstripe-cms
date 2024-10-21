<?php

namespace SilverStripe\CMS\Tests\Controllers;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Versioned\Versioned;
use SilverStripe\CMS\Controllers\CMSSiteTreeFilter_Search;
use SilverStripe\CMS\Controllers\CMSSiteTreeFilter_ChangedPages;
use SilverStripe\CMS\Controllers\CMSSiteTreeFilter_DeletedPages;
use SilverStripe\CMS\Controllers\CMSSiteTreeFilter_StatusDraftPages;
use SilverStripe\CMS\Controllers\CMSSiteTreeFilter_StatusRemovedFromDraftPages;
use SilverStripe\CMS\Controllers\CMSSiteTreeFilter_StatusDeletedPages;
use SilverStripe\Dev\SapphireTest;

class CMSSiteTreeFilterTest extends SapphireTest
{
    protected static $fixture_file = 'CMSSiteTreeFilterTest.yml';

    public function testSearchFilterEmpty()
    {
        $page1 = $this->objFromFixture(SiteTree::class, 'page1');
        $page2 = $this->objFromFixture(SiteTree::class, 'page2');

        $f = new CMSSiteTreeFilter_Search();
        $results = $f->pagesIncluded();

        $this->assertTrue($f->isRecordIncluded($page1));
        $this->assertTrue($f->isRecordIncluded($page2));
    }

    public function testSearchFilterByTitle()
    {
        $page1 = $this->objFromFixture(SiteTree::class, 'page1');
        $page2 = $this->objFromFixture(SiteTree::class, 'page2');

        $f = new CMSSiteTreeFilter_Search(['Title' => 'Page 1']);
        $results = $f->pagesIncluded();

        $this->assertTrue($f->isRecordIncluded($page1));
        $this->assertFalse($f->isRecordIncluded($page2));
        $this->assertEquals(1, count($results ?? []));
        $this->assertEquals(
            ['ID' => $page1->ID, 'ParentID' => 0],
            $results[0]
        );
    }

    public function testUrlSegmentFilter()
    {
        $page = $this->objFromFixture(SiteTree::class, 'page8');

        $filter = CMSSiteTreeFilter_Search::create(['Term' => 'lake-wanaka+adventure']);
        $this->assertTrue($filter->isRecordIncluded($page));

        $filter = CMSSiteTreeFilter_Search::create(['URLSegment' => 'lake-wanaka+adventure']);
        $this->assertTrue($filter->isRecordIncluded($page));
    }

    public function testIncludesParentsForNestedMatches()
    {
        $parent = $this->objFromFixture(SiteTree::class, 'page3');
        $child = $this->objFromFixture(SiteTree::class, 'page3b');

        $f = new CMSSiteTreeFilter_Search(['Title' => 'Page 3b']);
        $results = $f->pagesIncluded();

        $this->assertTrue($f->isRecordIncluded($parent));
        $this->assertTrue($f->isRecordIncluded($child));
        $this->assertEquals(1, count($results ?? []));
        $this->assertEquals(
            ['ID' => $child->ID, 'ParentID' => $parent->ID],
            $results[0]
        );
    }

    public function testChangedPagesFilter()
    {
        /** @var Page $unchangedPage */
        $unchangedPage = $this->objFromFixture(SiteTree::class, 'page1');
        $unchangedPage->publishRecursive();

        /** @var Page $changedPage */
        $changedPage = $this->objFromFixture(SiteTree::class, 'page2');
        $changedPage->Title = 'Original';
        $changedPage->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        $changedPage->Title = 'Changed';
        $changedPage->write();
        $changedPageVersion = $changedPage->Version;

        // Check that only changed pages are returned
        $f = new CMSSiteTreeFilter_ChangedPages(['Term' => 'Changed']);
        $results = $f->pagesIncluded();

        $this->assertTrue($f->isRecordIncluded($changedPage));
        $this->assertFalse($f->isRecordIncluded($unchangedPage));
        $this->assertEquals(1, count($results ?? []));
        $this->assertEquals(
            ['ID' => $changedPage->ID, 'ParentID' => 0],
            $results[0]
        );

        // Check that only changed pages are returned
        $f = new CMSSiteTreeFilter_ChangedPages(['Term' => 'No Matches']);
        $results = $f->pagesIncluded();
        $this->assertEquals(0, count($results ?? []));

        // If we roll back to an earlier version than what's on the published site, we should still show the changed
        $changedPage->Title = 'Changed 2';
        $changedPage->write();
        $changedPage->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        $changedPage->rollbackRecursive($changedPageVersion);

        $f = new CMSSiteTreeFilter_ChangedPages(['Term' => 'Changed']);
        $results = $f->pagesIncluded();

        $this->assertEquals(1, count($results ?? []));
        $this->assertEquals(['ID' => $changedPage->ID, 'ParentID' => 0], $results[0]);
    }

    public function testDeletedPagesFilter()
    {
        $deletedPage = $this->objFromFixture(SiteTree::class, 'page2');
        $deletedPage->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        $deletedPageID = $deletedPage->ID;
        $deletedPage->delete();
        $deletedPage = Versioned::get_one_by_stage(
            SiteTree::class,
            'Live',
            ['"SiteTree_Live"."ID"' => $deletedPageID]
        );

        $f = new CMSSiteTreeFilter_DeletedPages(['Term' => 'Page']);
        $this->assertTrue($f->isRecordIncluded($deletedPage));

        // Check that only changed pages are returned
        $f = new CMSSiteTreeFilter_DeletedPages(['Term' => 'No Matches']);
        $this->assertFalse($f->isRecordIncluded($deletedPage));
    }

    public function testStatusDraftPagesFilter()
    {
        $draftPage = $this->objFromFixture(SiteTree::class, 'page4');
        $draftPage = Versioned::get_one_by_stage(
            SiteTree::class,
            'Stage',
            sprintf('"SiteTree"."ID" = %d', $draftPage->ID)
        );

        // Check draft page is shown
        $f = new CMSSiteTreeFilter_StatusDraftPages(['Term' => 'Page']);
        $this->assertTrue($f->isRecordIncluded($draftPage));

        // Check filter respects parameters
        $f = new CMSSiteTreeFilter_StatusDraftPages(['Term' => 'No Match']);
        $this->assertEmpty($f->isRecordIncluded($draftPage));

        // Ensures empty array returned if no data to show
        $f = new CMSSiteTreeFilter_StatusDraftPages();
        $draftPage->delete();
        $this->assertEmpty($f->isRecordIncluded($draftPage));
    }

    public function testDateFromToLastSameDate()
    {
        $draftPage = $this->objFromFixture(SiteTree::class, 'page4');
        // Grab the date
        $date = substr($draftPage->LastEdited ?? '', 0, 10);
        // Filter with that date
        $filter = new CMSSiteTreeFilter_Search([
            'LastEditedFrom' => $date,
            'LastEditedTo' => $date,
        ]);
        $this->assertTrue(
            $filter->isRecordIncluded($draftPage),
            'Using the same date for from and to should show find that page'
        );
    }

    public function testStatusRemovedFromDraftFilter()
    {
        $removedDraftPage = $this->objFromFixture(SiteTree::class, 'page6');
        $removedDraftPage->publishRecursive();
        $removedDraftPage->deleteFromStage('Stage');
        $removedDraftPage = Versioned::get_one_by_stage(
            SiteTree::class,
            'Live',
            sprintf('"SiteTree"."ID" = %d', $removedDraftPage->ID)
        );

        // Check live-only page is included
        $f = new CMSSiteTreeFilter_StatusRemovedFromDraftPages(['LastEditedFrom' => '2000-01-01 00:00']);
        $this->assertTrue($f->isRecordIncluded($removedDraftPage));

        // Check filter is respected
        $f = new CMSSiteTreeFilter_StatusRemovedFromDraftPages(['LastEditedTo' => '1999-01-01 00:00']);
        $this->assertEmpty($f->isRecordIncluded($removedDraftPage));

        // Ensures empty array returned if no data to show
        $f = new CMSSiteTreeFilter_StatusRemovedFromDraftPages();
        $removedDraftPage->delete();
        $this->assertEmpty($f->isRecordIncluded($removedDraftPage));
    }

    public function testStatusDeletedFilter()
    {
        $deletedPage = $this->objFromFixture(SiteTree::class, 'page7');
        $deletedPage->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        $deletedPageID = $deletedPage->ID;

        // Can't use straight $blah->delete() as that blows it away completely and test fails
        $deletedPage->deleteFromStage(Versioned::LIVE);
        $deletedPage->deleteFromStage(Versioned::DRAFT);
        $checkParentExists = Versioned::get_latest_version(SiteTree::class, $deletedPageID);

        // Check deleted page is included
        $f = new CMSSiteTreeFilter_StatusDeletedPages(['Title' => 'Page']);
        $this->assertTrue($f->isRecordIncluded($checkParentExists));

        // Check filter is respected
        $f = new CMSSiteTreeFilter_StatusDeletedPages(['Title' => 'Bobby']);
        $this->assertFalse($f->isRecordIncluded($checkParentExists));
    }
}
