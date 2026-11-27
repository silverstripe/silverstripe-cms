<?php

namespace SilverStripe\CMS\Tests\Controllers;

use SilverStripe\CMS\BatchActions\CMSBatchAction_Archive;
use SilverStripe\CMS\BatchActions\CMSBatchAction_Publish;
use SilverStripe\CMS\BatchActions\CMSBatchAction_Unpublish;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\CMS\Tests\Controllers\CMSBatchActionsTest\OwnerPage;
use SilverStripe\CMS\Tests\Controllers\CMSBatchActionsTest\RestrictedOwnedRecord;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;

/**
 * Tests CMS Specific subclasses of {@see CMSBatchAction}
 */
class CMSBatchActionsTest extends SapphireTest
{
    protected static $fixture_file = 'CMSBatchActionsTest.yml';

    protected static $extra_dataobjects = [
        OwnerPage::class,
        RestrictedOwnedRecord::class,
    ];

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


    public function testBatchPublishCountsPageBlockedByOwnedRecordAsFailure(): void
    {
        $owned = RestrictedOwnedRecord::create(['Title' => 'Blocked']);
        $owned->write();
        $blockedPage = OwnerPage::create(['Title' => 'Blocked page', 'OwnedID' => $owned->ID]);
        $blockedPage->write();
        $plainPage = SiteTree::create(['Title' => 'Plain page']);
        $plainPage->write();
        $member = $this->createMemberWithPermission(['CMS_ACCESS_CMSMain', 'SITETREE_EDIT_ALL', 'SITETREE_PUBLISH_ALL']);
        Security::setCurrentUser($member);
        $action = new CMSBatchAction_Publish();
        $ids = [$blockedPage->ID, $plainPage->ID];
        $this->assertEqualsCanonicalizing($ids, $action->applicablePages($ids));
        $pages = SiteTree::get()->filter(['ID' => $ids]);
        $response = $action->run($pages);
        $this->assertSame('Published 1 pages, 1 failures', $response->getStatusDescription());
        $this->assertNull(Versioned::get_by_stage(OwnerPage::class, Versioned::LIVE)->byID($blockedPage->ID));
        $this->assertNotNull(Versioned::get_by_stage(SiteTree::class, Versioned::LIVE)->byID($plainPage->ID));
        $this->assertFalse($owned->isPublished());
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
