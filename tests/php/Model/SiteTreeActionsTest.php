<?php

namespace SilverStripe\CMS\Tests\Model;

use Page;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;

/**
 * Possible actions:
 * - action_save
 * - action_publish
 * - action_unpublish
 * - action_archive
 * - action_rollback
 * - action_revert
 */
class SiteTreeActionsTest extends FunctionalTest
{

    protected static $fixture_file = 'SiteTreeActionsTest.yml';

    public function testActionsReadonly()
    {
        // Publish record
        $this->logInWithPermission('ADMIN');
        $page = new SiteTreeActionsTest_Page();
        $page->CanEditType = 'LoggedInUsers';
        $page->write();
        $page->publishRecursive();

        // Log in as another user
        $readonlyEditor = $this->objFromFixture(Member::class, 'cmsreadonlyeditor');
        Security::setCurrentUser($readonlyEditor);

        // Reload latest version
        $page = Page::get()->byID($page->ID);
        $actions = $page->getCMSActions();

        $this->assertNull($actions->dataFieldByName('action_addtocampaign'));
        $this->assertNull($actions->dataFieldByName('action_save'));
        $this->assertNull($actions->dataFieldByName('action_publish'));
        $this->assertNull($actions->dataFieldByName('action_unpublish'));
        $this->assertNull($actions->dataFieldByName('action_archive'));
        $this->assertNull($actions->dataFieldByName('action_rollback'));
        $this->assertNull($actions->dataFieldByName('action_revert'));
    }

    public function testActionsNoDeletePublishedRecord()
    {
        $this->logInWithPermission('ADMIN');

        $page = new SiteTreeActionsTest_Page();
        $page->CanEditType = 'LoggedInUsers';
        $page->write();
        $pageID = $page->ID;
        $page->publishRecursive();
        $page->deleteFromStage(Versioned::DRAFT);

        // Get the live version of the page
        $page = Versioned::get_one_by_stage(SiteTree::class, "Live", "\"SiteTree\".\"ID\" = $pageID");
        $this->assertInstanceOf(SiteTree::class, $page);

        // Check that someone without the right permission can't delete the page
        $editor = $this->objFromFixture(Member::class, 'cmsnodeleteeditor');
        Security::setCurrentUser($editor);

        $actions = $page->getCMSActions();
        $this->assertNull($actions->dataFieldByName('action_archive'));

        // Check that someone with the right permission can delete the page
        /** @var Member $member */
        $member = $this->objFromFixture(Member::class, 'cmseditor');
        Security::setCurrentUser($member);
        $actions = $page->getCMSActions();
        $this->assertNotNull($actions->dataFieldByName('action_archive'));
    }

    public function testActionsPublishedRecord()
    {
        $author = $this->objFromFixture(Member::class, 'cmseditor');
        Security::setCurrentUser($author);

        /** @var Page $page */
        $page = new Page();
        $page->CanEditType = 'LoggedInUsers';
        $page->write();
        $page->publishRecursive();

        // Reload latest version
        $page = Page::get()->byID($page->ID);

        $actions = $page->getCMSActions();

        $this->assertNotNull($actions->dataFieldByName('action_addtocampaign'));
        $this->assertNotNull($actions->dataFieldByName('action_save'));
        $this->assertNotNull($actions->dataFieldByName('action_publish'));
        $this->assertNotNull($actions->dataFieldByName('action_unpublish'));
        $this->assertNotNull($actions->dataFieldByName('action_archive'));
        $this->assertNull($actions->dataFieldByName('action_rollback'));
        $this->assertNull($actions->dataFieldByName('action_revert'));
    }

    public function testActionsDeletedFromStageRecord()
    {
        $author = $this->objFromFixture(Member::class, 'cmseditor');
        Security::setCurrentUser($author);

        $page = new Page();
        $page->CanEditType = 'LoggedInUsers';
        $page->write();
        $this->assertTrue($page->canPublish());
        $pageID = $page->ID;
        $page->publishRecursive();
        $page->deleteFromStage('Stage');

        // Get the live version of the page
        $page = Versioned::get_one_by_stage(SiteTree::class, "Live", "\"SiteTree\".\"ID\" = $pageID");
        $this->assertInstanceOf(SiteTree::class, $page);

        $actions = $page->getCMSActions();

        // Theoretically allow deletions to be staged via add to campaign
        $this->assertNotNull($actions->dataFieldByName('action_addtocampaign'));
        $this->assertNull($actions->dataFieldByName('action_save'));
        $this->assertNull($actions->dataFieldByName('action_publish'));
        $this->assertNull($actions->dataFieldByName('action_unpublish'));
        $this->assertNotNull($actions->dataFieldByName('action_archive'));
        $this->assertNull($actions->dataFieldByName('action_rollback'));
        $this->assertNotNull($actions->dataFieldByName('action_revert'));
    }

    public function testActionsChangedOnStageRecord()
    {
        $author = $this->objFromFixture(Member::class, 'cmseditor');
        Security::setCurrentUser($author);

        $page = new Page();
        $page->CanEditType = 'LoggedInUsers';
        $page->write();
        $this->assertTrue($page->canPublish());
        $page->publishRecursive();
        $page->Content = 'Changed on Stage';
        $page->write();
        $page->flushCache();

        // Reload latest version
        $page = Page::get()->byID($page->ID);

        $actions = $page->getCMSActions();
        $this->assertNotNull($actions->dataFieldByName('action_addtocampaign'));
        $this->assertNotNull($actions->dataFieldByName('action_save'));
        $this->assertNotNull($actions->dataFieldByName('action_publish'));
        $this->assertNotNull($actions->dataFieldByName('action_unpublish'));
        $this->assertNotNull($actions->dataFieldByName('action_archive'));
        $this->assertNotNull($actions->dataFieldByName('action_rollback'));
        $this->assertNull($actions->dataFieldByName('action_revert'));
    }

    public function testActionsViewingOldVersion()
    {
        $p = new Page();
        $p->Content = 'test page first version';
        $p->write();
        $p->Content = 'new content';
        $p->write();

        // Looking at the old version, the ability to rollback to that version is available
        $version = DB::query('SELECT "Version" FROM "SiteTree_Versions" WHERE "Content" = \'test page first version\'')->value();
        $old = Versioned::get_version('Page', $p->ID, $version);
        $actions = $old->getCMSActions();
        $this->assertNull($actions->dataFieldByName('action_addtocampaign'));
        $this->assertNull($actions->dataFieldByName('action_save'));
        $this->assertNull($actions->dataFieldByName('action_publish'));
        $this->assertNull($actions->dataFieldByName('action_unpublish'));
        $this->assertNotNull($actions->dataFieldByName('action_email'));
        $this->assertNotNull($actions->dataFieldByName('action_rollback'));
    }
}
