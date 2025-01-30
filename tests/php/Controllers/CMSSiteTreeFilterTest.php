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

    public function testSearchFilterDefault()
    {
        $page1ID = $this->idFromFixture(SiteTree::class, 'page1');
        $page2 = $this->objFromFixture(SiteTree::class, 'page2');
        $page3ID = $this->idFromFixture(SiteTree::class, 'page3');

        $page2->delete();

        $f = new CMSSiteTreeFilter_Search();
        $results = $f->getFilteredPages(SiteTree::get());

        $this->assertTrue(in_array($page1ID, $results->column('ID')));
        $this->assertTrue(in_array($page3ID, $results->column('ID')));
        // Deleted page is not included
        $this->assertFalse(in_array($page2->ID, $results->column('ID')));
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
        $f = new CMSSiteTreeFilter_ChangedPages();
        $results = $f->getFilteredPages(SiteTree::get());

        $this->assertSame([$changedPage->ID], $results->column('ID'));
        $this->assertSame('Changed', $results->first()->Title);

        // If we roll back to an earlier version than what's on the published site, we should show the currently "modified" version
        $changedPage->Title = 'Changed 2';
        $changedPage->write();
        $changedPage->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        $changedPage->rollbackRecursive($changedPageVersion);

        $f = new CMSSiteTreeFilter_ChangedPages();
        $results = $f->getFilteredPages(SiteTree::get());

        $this->assertSame([$changedPage->ID], $results->column('ID'));
        $this->assertSame('Changed', $results->first()->Title);
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

        $f = new CMSSiteTreeFilter_DeletedPages();
        $results = $f->getFilteredPages(SiteTree::get());
        // Check this page is included even though it was deleted
        $this->assertTrue(in_array($deletedPageID, $results->column('ID')));
    }

    public function testStatusDraftPagesFilter()
    {
        $draftPage = $this->objFromFixture(SiteTree::class, 'page4');

        // Check draft page is shown
        $f = new CMSSiteTreeFilter_StatusDraftPages();
        $results = $f->getFilteredPages(SiteTree::get());
        $this->assertTrue(in_array($draftPage->ID, $results->column('ID')));

        // Published and modified pages not shown
        $draftPage->publishSingle();
        $results = $f->getFilteredPages(SiteTree::get());
        $this->assertFalse(in_array($draftPage->ID, $results->column('ID')));
        $draftPage->Title = 'modified';
        $draftPage->write();
        $results = $f->getFilteredPages(SiteTree::get());
        $this->assertFalse(in_array($draftPage->ID, $results->column('ID')));
    }

    public function testStatusRemovedFromDraftFilter()
    {
        $publishedPage = $this->objFromFixture(SiteTree::class, 'page3');
        $publishedPage->publishSingle();
        $archivePage = $this->objFromFixture(SiteTree::class, 'page1');
        $archivePage->doArchive();

        // Draft, published, and archive pages not included
        $f = new CMSSiteTreeFilter_StatusRemovedFromDraftPages();
        $results = $f->getFilteredPages(SiteTree::get());
        $this->assertSame([], $results->column('ID'));

        // Page is included when draft gets deleted
        $publishedPage->deleteFromStage('Stage');
        $results = $f->getFilteredPages(SiteTree::get());
        $this->assertSame([$publishedPage->ID], $results->column('ID'));
    }

    public function testStatusDeletedFilter()
    {
        $publishedPage = $this->objFromFixture(SiteTree::class, 'page4');
        $publishedPage->publishSingle();
        $notInDraftPage = $this->objFromFixture(SiteTree::class, 'page5');
        $notInDraftPage->publishSingle();
        $notInDraftPage->deleteFromStage('Stage');
        $archivedPage = $this->objFromFixture(SiteTree::class, 'page6');
        $archivedPage->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        $archivedPage->doArchive();

        // Check ONLY the archived page is included
        $f = new CMSSiteTreeFilter_StatusDeletedPages(['Title' => 'Page']);
        $results = $f->getFilteredPages(SiteTree::get());
        $this->assertSame([$archivedPage->ID], $results->column('ID'));
    }
}
