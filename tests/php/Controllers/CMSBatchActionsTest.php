<?php

namespace SilverStripe\CMS\Tests\Controllers;

use SilverStripe\CMS\BatchActions\CMSBatchAction_Archive;
use SilverStripe\CMS\BatchActions\CMSBatchAction_Publish;
use SilverStripe\CMS\BatchActions\CMSBatchAction_Unpublish;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Versioned\Versioned;

/**
 * Tests CMS Specific subclasses of {@see CMSBatchAction}
 */
class CMSBatchActionsTest extends SapphireTest
{
    protected static $fixture_file = 'CMSBatchActionsTest.yml';

    protected function setUp(): void
    {
        parent::setUp();

        $this->logInWithPermission('ADMIN');

        // Tests assume strict hierarchy is enabled
        Config::inst()->set(SiteTree::class, 'enforce_strict_hierarchy', true);

        // published page
        $published = $this->objFromFixture(SiteTree::class, 'published');
        $published->publishSingle();

        // Deleted / archived page
        $archived = $this->objFromFixture(SiteTree::class, 'archived');
        $archived->doArchive(); // should archive all children

        // Unpublished
        $unpublished = $this->objFromFixture(SiteTree::class, 'unpublished');
        $unpublished->publishSingle();
        $unpublished->doUnpublish();

        // Modified
        $modified = $this->objFromFixture(SiteTree::class, 'modified');
        $modified->publishSingle();
        $modified->Title = 'modified2';
        $modified->write();
    }

    /**
     * Test which pages can be published via batch actions
     */
    public function testBatchPublishApplicable()
    {
        $this->logInWithPermission('ADMIN');
        $pages = Versioned::get_including_deleted(SiteTree::class);
        $ids = $pages->column('ID');
        $action = new CMSBatchAction_Publish();

        // Test applicable pages
        $applicable = $action->applicablePages($ids);
        $this->assertContains($this->idFromFixture(SiteTree::class, 'published'), $applicable);
        $this->assertNotContains($this->idFromFixture(SiteTree::class, 'archived'), $applicable);
        $this->assertNotContains($this->idFromFixture(SiteTree::class, 'archivedx'), $applicable);
        $this->assertNotContains($this->idFromFixture(SiteTree::class, 'archivedy'), $applicable);
        $this->assertContains($this->idFromFixture(SiteTree::class, 'unpublished'), $applicable);
        $this->assertContains($this->idFromFixture(SiteTree::class, 'modified'), $applicable);
    }


    /**
     * Test which pages can be unpublished via batch actions
     */
    public function testBatchUnpublishApplicable()
    {
        $this->logInWithPermission('ADMIN');
        $pages = Versioned::get_including_deleted(SiteTree::class);
        $ids = $pages->column('ID');
        $action = new CMSBatchAction_Unpublish();

        // Test applicable page
        $applicable = $action->applicablePages($ids);
        $this->assertContains($this->idFromFixture(SiteTree::class, 'published'), $applicable);
        $this->assertNotContains($this->idFromFixture(SiteTree::class, 'archived'), $applicable);
        $this->assertNotContains($this->idFromFixture(SiteTree::class, 'archivedx'), $applicable);
        $this->assertNotContains($this->idFromFixture(SiteTree::class, 'archivedy'), $applicable);
        $this->assertNotContains($this->idFromFixture(SiteTree::class, 'unpublished'), $applicable);
        $this->assertContains($this->idFromFixture(SiteTree::class, 'modified'), $applicable);
    }

    /**
     * Test which pages can be archived via delete batch actions
     */
    public function testBatchDeleteApplicable()
    {
        $this->logInWithPermission('ADMIN');
        $pages = Versioned::get_including_deleted(SiteTree::class);
        $ids = $pages->column('ID');
        $action = new CMSBatchAction_Archive();

        // Test applicable pages
        $applicable = $action->applicablePages($ids);
        $this->assertContains($this->idFromFixture(SiteTree::class, 'published'), $applicable);
        $this->assertNotContains($this->idFromFixture(SiteTree::class, 'archived'), $applicable);
        $this->assertContains($this->idFromFixture(SiteTree::class, 'unpublished'), $applicable);
        $this->assertContains($this->idFromFixture(SiteTree::class, 'modified'), $applicable);
    }
}
