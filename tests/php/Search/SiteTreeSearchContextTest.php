<?php

namespace SilverStripe\CMS\Tests\Search;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Versioned\Versioned;
use SilverStripe\CMS\Search\SiteTreeSearchContext;
use SilverStripe\Dev\SapphireTest;

/**
 * Very low-touch check that this search context works as expected
 * i.e.
 * - Filtering works as expected
 * - It uses its custom filter when applied
 * - That doesn't override other filter params
 */
class SiteTreeSearchContextTest extends SapphireTest
{
    protected static $fixture_file = 'SiteTreeSearchContextTest.yml';

    public function testSearchFilterEmpty()
    {
        $page1ID = $this->idFromFixture(SiteTree::class, 'page1');
        $page2 = $this->objFromFixture(SiteTree::class, 'page2');
        $page3ID = $this->idFromFixture(SiteTree::class, 'page3');

        $page2->delete();

        $searchContext = new SiteTreeSearchContext(SiteTree::class);
        $results = $searchContext->getQuery([]);

        $this->assertTrue(in_array($page1ID, $results->column('ID')));
        $this->assertTrue(in_array($page3ID, $results->column('ID')));
        $this->assertFalse(in_array($page2->ID, $results->column('ID')));
    }

    public function testSearchFilterByTitle()
    {
        $page = $this->objFromFixture(SiteTree::class, 'page1');
        $searchContext = new SiteTreeSearchContext(SiteTree::class);
        $results = $searchContext->getQuery(['q' => 'Home']);
        $this->assertSame([$page->ID], $results->column('ID'));
    }

    public function testUrlSegmentFilter()
    {
        $page = $this->objFromFixture(SiteTree::class, 'page8');
        $searchContext = new SiteTreeSearchContext(SiteTree::class);
        $results = $searchContext->getQuery(['q' => 'lake wanaka']);
        $this->assertSame([$page->ID], $results->column('ID'));
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

        // Check that only changed pages are returned
        $searchContext = new SiteTreeSearchContext(SiteTree::class);
        $results = $searchContext->getQuery(['q' => 'Changed']);

        $this->assertSame([$changedPage->ID], $results->column('ID'));
        $this->assertSame('Changed', $results->first()->Title);

        // Check that filter doesn't override other query portions
        $results = $searchContext->getQuery(['q' => 'No Matches']);
        $this->assertCount(0, $results);
    }
}
