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
        $page1 = $this->objFromFixture('Page', 'page1');
        $page2 = $this->objFromFixture('Page', 'page2');

        $f = new CMSSiteTreeFilter_Search();
        $results = $f->pagesIncluded();

        $this->assertTrue($f->isPageIncluded($page1));
        $this->assertTrue($f->isPageIncluded($page2));
    }

    public function testSearchFilterByTitle()
    {
        $page1 = $this->objFromFixture('Page', 'page1');
        $page2 = $this->objFromFixture('Page', 'page2');

        $f = new CMSSiteTreeFilter_Search(array('Title' => 'Page 1'));
        $results = $f->pagesIncluded();

        $this->assertTrue($f->isPageIncluded($page1));
        $this->assertFalse($f->isPageIncluded($page2));
        $this->assertEquals(1, count($results));
        $this->assertEquals(
            array('ID' => $page1->ID, 'ParentID' => 0),
            $results[0]
        );
    }

    public function testIncludesParentsForNestedMatches()
    {
        $parent = $this->objFromFixture('Page', 'page3');
        $child = $this->objFromFixture('Page', 'page3b');

        $f = new CMSSiteTreeFilter_Search(array('Title' => 'Page 3b'));
        $results = $f->pagesIncluded();

        $this->assertTrue($f->isPageIncluded($parent));
        $this->assertTrue($f->isPageIncluded($child));
        $this->assertEquals(1, count($results));
        $this->assertEquals(
            array('ID' => $child->ID, 'ParentID' => $parent->ID),
            $results[0]
        );
    }

    public function testChangedPagesFilter()
    {
        $unchangedPage = $this->objFromFixture('Page', 'page1');
        $unchangedPage->publishRecursive();

        $changedPage = $this->objFromFixture('Page', 'page2');
        $changedPage->Title = 'Original';
        $changedPage->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        $changedPage->Title = 'Changed';
        $changedPage->write();

        // Check that only changed pages are returned
        $f = new CMSSiteTreeFilter_ChangedPages(array('Term' => 'Changed'));
        $results = $f->pagesIncluded();

        $this->assertTrue($f->isPageIncluded($changedPage));
        $this->assertFalse($f->isPageIncluded($unchangedPage));
        $this->assertEquals(1, count($results));
        $this->assertEquals(
            array('ID' => $changedPage->ID, 'ParentID' => 0),
            $results[0]
        );

        // Check that only changed pages are returned
        $f = new CMSSiteTreeFilter_ChangedPages(array('Term' => 'No Matches'));
        $results = $f->pagesIncluded();
        $this->assertEquals(0, count($results));

        // If we roll back to an earlier version than what's on the published site, we should still show the changed
        $changedPage->Title = 'Changed 2';
        $changedPage->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        $changedPage->doRollbackTo(1);

        $f = new CMSSiteTreeFilter_ChangedPages(array('Term' => 'Changed'));
        $results = $f->pagesIncluded();

        $this->assertEquals(1, count($results));
        $this->assertEquals(array('ID' => $changedPage->ID, 'ParentID' => 0), $results[0]);
    }

    public function testDeletedPagesFilter()
    {
        $deletedPage = $this->objFromFixture('Page', 'page2');
        $deletedPage->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        $deletedPageID = $deletedPage->ID;
        $deletedPage->delete();
        $deletedPage = Versioned::get_one_by_stage(
            SiteTree::class,
            'Live',
            array('"SiteTree_Live"."ID"' => $deletedPageID)
        );

        $f = new CMSSiteTreeFilter_DeletedPages(array('Term' => 'Page'));
        $this->assertTrue($f->isPageIncluded($deletedPage));

        // Check that only changed pages are returned
        $f = new CMSSiteTreeFilter_DeletedPages(array('Term' => 'No Matches'));
        $this->assertFalse($f->isPageIncluded($deletedPage));
    }

    public function testStatusDraftPagesFilter()
    {
        $draftPage = $this->objFromFixture('Page', 'page4');
        $draftPage = Versioned::get_one_by_stage(
            SiteTree::class,
            'Stage',
            sprintf('"SiteTree"."ID" = %d', $draftPage->ID)
        );

        // Check draft page is shown
        $f = new CMSSiteTreeFilter_StatusDraftPages(array('Term' => 'Page'));
        $this->assertTrue($f->isPageIncluded($draftPage));

        // Check filter respects parameters
        $f = new CMSSiteTreeFilter_StatusDraftPages(array('Term' => 'No Match'));
        $this->assertEmpty($f->isPageIncluded($draftPage));

        // Ensures empty array returned if no data to show
        $f = new CMSSiteTreeFilter_StatusDraftPages();
        $draftPage->delete();
        $this->assertEmpty($f->isPageIncluded($draftPage));
    }

    public function testDateFromToLastSameDate()
    {
        $draftPage = $this->objFromFixture('Page', 'page4');
        // Grab the date
        $date = substr($draftPage->LastEdited, 0, 10);
        // Filter with that date
        $filter = new CMSSiteTreeFilter_Search(array(
            'LastEditedFrom' => $date,
            'LastEditedTo' => $date
        ));
        $this->assertTrue($filter->isPageIncluded($draftPage), 'Using the same date for from and to should show find that page');
    }

    public function testStatusRemovedFromDraftFilter()
    {
        $removedDraftPage = $this->objFromFixture('Page', 'page6');
        $removedDraftPage->publishRecursive();
        $removedDraftPage->deleteFromStage('Stage');
        $removedDraftPage = Versioned::get_one_by_stage(
            SiteTree::class,
            'Live',
            sprintf('"SiteTree"."ID" = %d', $removedDraftPage->ID)
        );

        // Check live-only page is included
        $f = new CMSSiteTreeFilter_StatusRemovedFromDraftPages(array('LastEditedFrom' => '2000-01-01 00:00'));
        $this->assertTrue($f->isPageIncluded($removedDraftPage));

        // Check filter is respected
        $f = new CMSSiteTreeFilter_StatusRemovedFromDraftPages(array('LastEditedTo' => '1999-01-01 00:00'));
        $this->assertEmpty($f->isPageIncluded($removedDraftPage));

        // Ensures empty array returned if no data to show
        $f = new CMSSiteTreeFilter_StatusRemovedFromDraftPages();
        $removedDraftPage->delete();
        $this->assertEmpty($f->isPageIncluded($removedDraftPage));
    }

    public function testStatusDeletedFilter()
    {
        $deletedPage = $this->objFromFixture('Page', 'page7');
        $deletedPage->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        $deletedPageID = $deletedPage->ID;

        // Can't use straight $blah->delete() as that blows it away completely and test fails
        $deletedPage->deleteFromStage(Versioned::LIVE);
        $deletedPage->deleteFromStage(Versioned::DRAFT);
        $checkParentExists = Versioned::get_latest_version(SiteTree::class, $deletedPageID);

        // Check deleted page is included
        $f = new CMSSiteTreeFilter_StatusDeletedPages(array('Title' => 'Page'));
        $this->assertTrue($f->isPageIncluded($checkParentExists));

        // Check filter is respected
        $f = new CMSSiteTreeFilter_StatusDeletedPages(array('Title' => 'Bobby'));
        $this->assertFalse($f->isPageIncluded($checkParentExists));
    }
}
