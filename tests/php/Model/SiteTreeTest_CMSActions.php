<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Subsites\Extensions\SiteTreeSubsites;
use TractorCow\Fluent\Extension\FluentSiteTreeExtension;

class SiteTreeTest_CMSActions extends SapphireTest
{
    protected static $fixture_file = 'SiteTreeTest.yml';

    protected static $illegal_extensions = [
        SiteTree::class => [
            SiteTreeSubsites::class,
            FluentSiteTreeExtension::class,
        ],
    ];

    protected static $extra_dataobjects = [
        SiteTreeTest_ClassA::class,
        SiteTreeTest_ClassB::class,
        SiteTreeTest_ClassC::class,
        SiteTreeTest_ClassD::class,
        SiteTreeTest_ClassCext::class,
        SiteTreeTest_NotRoot::class,
        SiteTreeTest_StageStatusInherit::class,
        SiteTreeTest_DataObject::class,
    ];

    public function testGetCMSActions()
    {
        // Create new page on DRAFT
        $page = SiteTree::create();
        $page->Content = md5(rand(0, PHP_INT_MAX));
        $page->write();

        // BEGIN DRAFT
        $actions = $page->getCMSActions();
        $this->assertNotNull(
            $actions->fieldByName('MajorActions.action_save'),
            'save action present for a saved draft page'
        );
        $this->assertNotNull(
            $actions->fieldByName('MajorActions.action_publish'),
            'publish action present for a saved draft page'
        );
        $this->assertNotNull(
            $actions->fieldByName('ActionMenus.MoreOptions.action_archive'),
            'archive action present for a saved draft page'
        );
        $this->assertNotNull(
            $actions->fieldByName('ActionMenus.MoreOptions.action_addtocampaign'),
            'addtocampaign action present for a saved draft page'
        );
        $this->assertNull(
            $actions->fieldByName('ActionMenus.MoreOptions.action_unpublish'),
            'no unpublish action present for a saved draft page'
        );
        $this->assertNull(
            $actions->fieldByName('ActionMenus.MoreOptions.action_rollback'),
            'no rollback action present for a saved draft page'
        );
        $this->assertNull(
            $actions->fieldByName('MajorActions.action_restore'),
            'no restore action present for a saved draft page'
        );
        // END DRAFT

        // BEGIN PUBLISHED
        $page->doPublish();
        $actions = $page->getCMSActions();
        $this->assertNull(
            $actions->fieldByName('MajorActions.action_save'),
            'no save action present for a published page'
        );
        $this->assertNull(
            $actions->fieldByName('ActionMenus.MoreOptions.action_archive'),
            'no archive action present for a saved draft page'
        );
        $this->assertNotNull(
            $actions->fieldByName('ActionMenus.MoreOptions.action_rollback'),
            'rollback action present for a published page'
        );
        $this->assertNull(
            $actions->fieldByName('ActionMenus.MoreOptions.action_unpublish'),
            'no unpublish action present for a published page'
        );
        $this->assertNotNull(
            $actions->fieldByName('ActionMenus.MoreOptions.action_addtocampaign'),
            'addtocampaign action present for a published page'
        );
        $this->assertNull(
            $actions->fieldByName('MajorActions.action_restore'),
            'no restore action present for a published page'
        );
        // END PUBLISHED

        // BEGIN DRAFT AFTER PUBLISHED
        $page->Content = md5(rand(0, PHP_INT_MAX));
        $page->write();
        $actions = $page->getCMSActions();

        $this->assertNotNull(
            $actions->fieldByName('MajorActions.action_save'),
            'save action present for a changed published page'
        );
        $this->assertNotNull(
            $actions->fieldByName('ActionMenus.MoreOptions.action_unpublish'),
            'unpublish action present for a changed published page'
        );
        $this->assertNotNull(
            $actions->fieldByName('ActionMenus.MoreOptions.action_archive'),
            'archive action present for a changed published page'
        );
        $this->assertNotNull(
            $actions->fieldByName('ActionMenus.MoreOptions.action_rollback'),
            'rollback action present for a changed published page'
        );
        $this->assertNotNull(
            $actions->fieldByName('ActionMenus.MoreOptions.action_addtocampaign'),
            'addtocampaign action present for a changed published page'
        );
        $this->assertNull(
            $actions->fieldByName('MajorActions.action_restore'),
            'no restore action present for a changed published page'
        );
        // END DRAFT AFTER PUBLISHED

        // BEGIN ARCHIVED
        $page->doArchive();
        $actions = $page->getCMSActions();

        $this->assertNull(
            $actions->fieldByName('MajorActions.action_save'),
            'no save action present for a archived page'
        );
        $this->assertNull(
            $actions->fieldByName('ActionMenus.MoreOptions.action_unpublish'),
            'no unpublish action present for a archived page'
        );
        $this->assertNull(
            $actions->fieldByName('ActionMenus.MoreOptions.action_archive'),
            'no archive action present for a archived page'
        );
        $this->assertNull(
            $actions->fieldByName('ActionMenus.MoreOptions.action_rollback'),
            'no rollback action present for a archived page'
        );
        $this->assertNull(
            $actions->fieldByName('ActionMenus.MoreOptions.action_addtocampaign'),
            'no addtocampaign action present for a archived page'
        );
        $this->assertNotNull(
            $actions->fieldByName('MajorActions.action_restore'),
            'restore action present for a archived page'
        );
        // END ARCHIVED
    }

    public function testGetCMSActionsWithoutForms()
    {
        // Create new page on DRAFT
        $page = SiteTree::create();
        $page->Content = md5(rand(0, PHP_INT_MAX));
        $page->write();

        // BEGIN DRAFT
        $actions = $page->getCMSActions();

        $this->assertEmpty(
            $actions->fieldByName('MajorActions.action_save')->getForm(),
            'save action has no form when page is draft'
        );
        $this->assertEmpty(
            $actions->fieldByName('MajorActions.action_publish')->getForm(),
            'publish action has no form when page is draft'
        );
        $this->assertEmpty(
            $actions->fieldByName('ActionMenus.MoreOptions.action_archive')->getForm(),
            'archive action has no form when page is draft'
        );
        $this->assertEmpty(
            $actions->fieldByName('ActionMenus.MoreOptions.action_addtocampaign')->getForm(),
            'addtocampaign action has no form when page is draft'
        );
        // END DRAFT

        // BEGIN PUBLISHED
        $page->doPublish();
        $actions = $page->getCMSActions();

        $this->assertEmpty(
            $actions->fieldByName('ActionMenus.MoreOptions.action_rollback')->getForm(),
            'rollback action has no form when page is published'
        );
        $this->assertEmpty(
            $actions->fieldByName('ActionMenus.MoreOptions.action_addtocampaign')->getForm(),
            'addtocampaign action has no form when page is published'
        );
        // END PUBLISHED

        // BEGIN DRAFT AFTER PUBLISHED
        $page->Content = md5(rand(0, PHP_INT_MAX));
        $page->write();
        $actions = $page->getCMSActions();

        $this->assertEmpty(
            $actions->fieldByName('MajorActions.action_save')->getForm(),
            'save action has no form when page is draft after published'
        );
        $this->assertEmpty(
            $actions->fieldByName('ActionMenus.MoreOptions.action_unpublish')->getForm(),
            'unpublish action has no form when page is draft after published'
        );
        $this->assertEmpty(
            $actions->fieldByName('ActionMenus.MoreOptions.action_archive')->getForm(),
            'archive action has no form when page is draft after published'
        );
        $this->assertEmpty(
            $actions->fieldByName('ActionMenus.MoreOptions.action_rollback')->getForm(),
            'rollback action has no form when page is draft after published'
        );
        $this->assertEmpty(
            $actions->fieldByName('ActionMenus.MoreOptions.action_addtocampaign')->getForm(),
            'addtocampaign action has no form when page is draft after published'
        );
        // END DRAFT AFTER PUBLISHED

        // BEGIN ARCHIVED
        $page->doArchive();
        $actions = $page->getCMSActions();

        $this->assertEmpty(
            $actions->fieldByName('MajorActions.action_restore')->getForm(),
            'retore action has no form when page archived'
        );
        // END ARCHIVED
    }
}
