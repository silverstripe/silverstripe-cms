<?php

namespace SilverStripe\CMS\Tests\Model;

use Page;
use ReflectionMethod;
use SilverStripe\CMS\Model\RedirectorPage;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\CMS\Model\VirtualPage;
use SilverStripe\Control\ContentNegotiator;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\i18n\i18n;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Group;
use SilverStripe\Security\InheritedPermissions;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Versioned\Versioned;
use SilverStripe\View\Parsers\Diff;
use SilverStripe\View\Parsers\ShortcodeParser;
use SilverStripe\View\Parsers\URLSegmentFilter;
use LogicException;

class SiteTreeTest extends SapphireTest
{
    protected static $fixture_file = 'SiteTreeTest.yml';

    protected static $illegal_extensions = array(
        SiteTree::class => array('SiteTreeSubsites', 'Translatable'),
    );

    protected static $extra_dataobjects = array(
        SiteTreeTest_ClassA::class,
        SiteTreeTest_ClassB::class,
        SiteTreeTest_ClassC::class,
        SiteTreeTest_ClassD::class,
        SiteTreeTest_ClassCext::class,
        SiteTreeTest_NotRoot::class,
        SiteTreeTest_StageStatusInherit::class,
        SiteTreeTest_DataObject::class,
    );

    public function testCreateDefaultpages()
    {
            $remove = SiteTree::get();
        if ($remove) {
            foreach ($remove as $page) {
                $page->delete();
            }
        }
            // Make sure the table is empty
            $this->assertEquals(DB::query('SELECT COUNT("ID") FROM "SiteTree"')->value(), 0);

            // Disable the creation
            SiteTree::config()->create_default_pages = false;
            singleton(SiteTree::class)->requireDefaultRecords();

            // The table should still be empty
            $this->assertEquals(DB::query('SELECT COUNT("ID") FROM "SiteTree"')->value(), 0);

            // Enable the creation
            SiteTree::config()->create_default_pages = true;
            singleton(SiteTree::class)->requireDefaultRecords();

            // The table should now have three rows (home, about-us, contact-us)
            $this->assertEquals(DB::query('SELECT COUNT("ID") FROM "SiteTree"')->value(), 3);
    }

    /**
     * Test generation of the URLSegment values.
     *  - Turns things into lowercase-hyphen-format
     *  - Generates from Title by default, unless URLSegment is explicitly set
     *  - Resolves duplicates by appending a number
     *  - renames classes with a class name conflict
     */
    public function testURLGeneration()
    {
        $expectedURLs = array(
            'home' => 'home',
            'staff' => 'my-staff',
            'about' => 'about-us',
            'staffduplicate' => 'my-staff-2',
            'product1' => '1-1-test-product',
            'product2' => 'another-product',
            'product3' => 'another-product-2',
            'product4' => 'another-product-3',
            'object'   => 'object',
            'controller' => 'controller',
            'numericonly' => '1930',
        );

        foreach ($expectedURLs as $fixture => $urlSegment) {
            $obj = $this->objFromFixture('Page', $fixture);
            $this->assertEquals($urlSegment, $obj->URLSegment);
        }
    }

    /**
     * Test that publication copies data to SiteTree_Live
     */
    public function testPublishCopiesToLiveTable()
    {
        $obj = $this->objFromFixture('Page', 'about');
        $obj->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);

        $createdID = DB::query("SELECT \"ID\" FROM \"SiteTree_Live\" WHERE \"URLSegment\" = '$obj->URLSegment'")->value();
        $this->assertEquals($obj->ID, $createdID);
    }

    /**
     * Test that field which are set and then cleared are also transferred to the published site.
     */
    public function testPublishDeletedFields()
    {
        $this->logInWithPermission('ADMIN');

        $obj = $this->objFromFixture('Page', 'about');
        $obj->Title = "asdfasdf";
        $obj->write();
        $this->assertTrue($obj->publishRecursive());

        $this->assertEquals('asdfasdf', DB::query("SELECT \"Title\" FROM \"SiteTree_Live\" WHERE \"ID\" = '$obj->ID'")->value());

        $obj->Title = null;
        $obj->write();
        $this->assertTrue($obj->publishRecursive());

        $this->assertNull(DB::query("SELECT \"Title\" FROM \"SiteTree_Live\" WHERE \"ID\" = '$obj->ID'")->value());
    }

    public function testParentNodeCachedInMemory()
    {
        $parent = new SiteTree();
        $parent->Title = 'Section Title';
        $child = new SiteTree();
        $child->Title = 'Page Title';
        $child->setParent($parent);

        $this->assertInstanceOf(SiteTree::class, $child->Parent);
        $this->assertEquals("Section Title", $child->Parent->Title);
    }

    public function testParentModelReturnType()
    {
        $parent = new SiteTreeTest_PageNode();
        $child = new SiteTreeTest_PageNode();

        $child->setParent($parent);
        $this->assertInstanceOf(SiteTreeTest_PageNode::class, $child->Parent);
    }

    /**
     * Confirm that DataObject::get_one() gets records from SiteTree_Live
     */
    public function testGetOneFromLive()
    {
        $s = new SiteTree();
        $s->Title = "V1";
        $s->URLSegment = "get-one-test-page";
        $s->write();
        $s->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        $s->Title = "V2";
        $s->write();

        $oldMode = Versioned::get_reading_mode();
        Versioned::set_stage(Versioned::LIVE);

        $checkSiteTree = DataObject::get_one(SiteTree::class, array(
            '"SiteTree"."URLSegment"' => 'get-one-test-page'
        ));
        $this->assertEquals("V1", $checkSiteTree->Title);

        Versioned::set_reading_mode($oldMode);
    }

    public function testChidrenOfRootAreTopLevelPages()
    {
        $pages = SiteTree::get();
        foreach ($pages as $page) {
            $page->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        }
        unset($pages);

        /* If we create a new SiteTree object with ID = 0 */
        $obj = new SiteTree();
        /* Then its children should be the top-level pages */
        $stageChildren = $obj->stageChildren()->map('ID', 'Title');
        $liveChildren = $obj->liveChildren()->map('ID', 'Title');
        $allChildren = $obj->AllChildrenIncludingDeleted()->map('ID', 'Title');

        $this->assertContains('Home', $stageChildren);
        $this->assertContains('Products', $stageChildren);
        $this->assertNotContains('Staff', $stageChildren);

        $this->assertContains('Home', $liveChildren);
        $this->assertContains('Products', $liveChildren);
        $this->assertNotContains('Staff', $liveChildren);

        $this->assertContains('Home', $allChildren);
        $this->assertContains('Products', $allChildren);
        $this->assertNotContains('Staff', $allChildren);
    }

    public function testCanSaveBlankToHasOneRelations()
    {
        /* DataObject::write() should save to a has_one relationship if you set a field called (relname)ID */
        $page = new SiteTree();
        $parentID = $this->idFromFixture('Page', 'home');
        $page->ParentID = $parentID;
        $page->write();
        $this->assertEquals($parentID, DB::query("SELECT \"ParentID\" FROM \"SiteTree\" WHERE \"ID\" = $page->ID")->value());

        /* You should then be able to save a null/0/'' value to the relation */
        $page->ParentID = null;
        $page->write();
        $this->assertEquals(0, DB::query("SELECT \"ParentID\" FROM \"SiteTree\" WHERE \"ID\" = $page->ID")->value());
    }

    public function testStageStates()
    {
        // newly created page
        $createdPage = new SiteTree();
        $createdPage->write();
        $this->assertTrue($createdPage->isOnDraft());
        $this->assertFalse($createdPage->isPublished());
        $this->assertTrue($createdPage->isOnDraftOnly());
        $this->assertTrue($createdPage->isModifiedOnDraft());

        // published page
        $publishedPage = new SiteTree();
        $publishedPage->write();
        $publishedPage->copyVersionToStage('Stage', 'Live');
        $this->assertTrue($publishedPage->isOnDraft());
        $this->assertTrue($publishedPage->isPublished());
        $this->assertFalse($publishedPage->isOnDraftOnly());
        $this->assertFalse($publishedPage->isOnLiveOnly());
        $this->assertFalse($publishedPage->isModifiedOnDraft());

        // published page, deleted from stage
        $deletedFromDraftPage = new SiteTree();
        $deletedFromDraftPage->write();
        $deletedFromDraftPage->copyVersionToStage('Stage', 'Live');
        $deletedFromDraftPage->deleteFromStage('Stage');
        $this->assertFalse($deletedFromDraftPage->isArchived());
        $this->assertFalse($deletedFromDraftPage->isOnDraft());
        $this->assertTrue($deletedFromDraftPage->isPublished());
        $this->assertFalse($deletedFromDraftPage->isOnDraftOnly());
        $this->assertTrue($deletedFromDraftPage->isOnLiveOnly());
        $this->assertFalse($deletedFromDraftPage->isModifiedOnDraft());

        // published page, deleted from live
        $deletedFromLivePage = new SiteTree();
        $deletedFromLivePage->write();
        $deletedFromLivePage->copyVersionToStage('Stage', 'Live');
        $deletedFromLivePage->deleteFromStage('Live');
        $this->assertFalse($deletedFromLivePage->isArchived());
        $this->assertTrue($deletedFromLivePage->isOnDraft());
        $this->assertFalse($deletedFromLivePage->isPublished());
        $this->assertTrue($deletedFromLivePage->isOnDraftOnly());
        $this->assertFalse($deletedFromLivePage->isOnLiveOnly());
        $this->assertTrue($deletedFromLivePage->isModifiedOnDraft());

        // published page, deleted from both stages
        $deletedFromAllStagesPage = new SiteTree();
        $deletedFromAllStagesPage->write();
        $deletedFromAllStagesPage->copyVersionToStage('Stage', 'Live');
        $deletedFromAllStagesPage->deleteFromStage('Stage');
        $deletedFromAllStagesPage->deleteFromStage('Live');
        $this->assertTrue($deletedFromAllStagesPage->isArchived());
        $this->assertFalse($deletedFromAllStagesPage->isOnDraft());
        $this->assertFalse($deletedFromAllStagesPage->isPublished());
        $this->assertFalse($deletedFromAllStagesPage->isOnDraftOnly());
        $this->assertFalse($deletedFromAllStagesPage->isOnLiveOnly());
        $this->assertFalse($deletedFromAllStagesPage->isModifiedOnDraft());

        // published page, modified
        $modifiedOnDraftPage = new SiteTree();
        $modifiedOnDraftPage->write();
        $modifiedOnDraftPage->copyVersionToStage('Stage', 'Live');
        $modifiedOnDraftPage->Content = 'modified';
        $modifiedOnDraftPage->write();
        $this->assertFalse($modifiedOnDraftPage->isArchived());
        $this->assertTrue($modifiedOnDraftPage->isOnDraft());
        $this->assertTrue($modifiedOnDraftPage->isPublished());
        $this->assertFalse($modifiedOnDraftPage->isOnDraftOnly());
        $this->assertFalse($modifiedOnDraftPage->isOnLiveOnly());
        $this->assertTrue($modifiedOnDraftPage->isModifiedOnDraft());
    }

    /**
     * Test that a page can be completely deleted and restored to the stage site
     */
    public function testRestoreToStage()
    {
        $page = $this->objFromFixture('Page', 'about');
        $pageID = $page->ID;
        $page->delete();
        $this->assertTrue(!DataObject::get_by_id("Page", $pageID));

        $deletedPage = Versioned::get_latest_version(SiteTree::class, $pageID);
        $resultPage = $deletedPage->doRestoreToStage();

        $requeriedPage = DataObject::get_by_id("Page", $pageID);

        $this->assertEquals($pageID, $resultPage->ID);
        $this->assertEquals($pageID, $requeriedPage->ID);
        $this->assertEquals('About Us', $requeriedPage->Title);
        $this->assertInstanceOf('Page', $requeriedPage);


        $page2 = $this->objFromFixture('Page', 'products');
        $page2ID = $page2->ID;
        $page2->doUnpublish();
        $page2->delete();

        // Check that if we restore while on the live site that the content still gets pushed to
        // stage
        Versioned::set_stage(Versioned::LIVE);
        $deletedPage = Versioned::get_latest_version(SiteTree::class, $page2ID);
        $deletedPage->doRestoreToStage();
        $this->assertFalse((bool)Versioned::get_one_by_stage(SiteTree::class, Versioned::LIVE, "\"SiteTree\".\"ID\" = " . $page2ID));

        Versioned::set_stage(Versioned::DRAFT);
        $requeriedPage = DataObject::get_by_id("Page", $page2ID);
        $this->assertEquals('Products', $requeriedPage->Title);
        $this->assertInstanceOf('Page', $requeriedPage);
    }

    public function testNoCascadingDeleteWithoutID()
    {
        Config::inst()->update('SiteTree', 'enforce_strict_hierarchy', true);
        $count = SiteTree::get()->count();
        $this->assertNotEmpty($count);
        $obj = new SiteTree();
        $this->assertFalse($obj->exists());
        $fail = true;
        try {
            $obj->delete();
        } catch (LogicException $e) {
            $fail = false;
        }
        if ($fail) {
            $this->fail('Failed to throw delete exception');
        }
        $this->assertCount($count, SiteTree::get());
    }

    public function testGetByLink()
    {
        $home     = $this->objFromFixture('Page', 'home');
        $about    = $this->objFromFixture('Page', 'about');
        $staff    = $this->objFromFixture('Page', 'staff');
        $product  = $this->objFromFixture('Page', 'product1');

        SiteTree::config()->nested_urls = false;

        $this->assertEquals($home->ID, SiteTree::get_by_link('/', false)->ID);
        $this->assertEquals($home->ID, SiteTree::get_by_link('/home/', false)->ID);
        $this->assertEquals($about->ID, SiteTree::get_by_link($about->Link(), false)->ID);
        $this->assertEquals($staff->ID, SiteTree::get_by_link($staff->Link(), false)->ID);
        $this->assertEquals($product->ID, SiteTree::get_by_link($product->Link(), false)->ID);

        Config::modify()->set(SiteTree::class, 'nested_urls', true);

        $this->assertEquals($home->ID, SiteTree::get_by_link('/', false)->ID);
        $this->assertEquals($home->ID, SiteTree::get_by_link('/home/', false)->ID);
        $this->assertEquals($about->ID, SiteTree::get_by_link($about->Link(), false)->ID);
        $this->assertEquals($staff->ID, SiteTree::get_by_link($staff->Link(), false)->ID);
        $this->assertEquals($product->ID, SiteTree::get_by_link($product->Link(), false)->ID);

        $this->assertEquals(
            $staff->ID,
            SiteTree::get_by_link('/my-staff/', false)->ID,
            'Assert a unique URLSegment can be used for b/c.'
        );
    }

    public function testRelativeLink()
    {
        $about    = $this->objFromFixture('Page', 'about');
        $staff    = $this->objFromFixture('Page', 'staff');

        Config::modify()->set(SiteTree::class, 'nested_urls', true);

        $this->assertEquals('about-us/', $about->RelativeLink(), 'Matches URLSegment on top level without parameters');
        $this->assertEquals('about-us/my-staff/', $staff->RelativeLink(), 'Matches URLSegment plus parent on second level without parameters');
        $this->assertEquals('about-us/edit', $about->RelativeLink('edit'), 'Matches URLSegment plus parameter on top level');
        $this->assertEquals('about-us/tom&jerry', $about->RelativeLink('tom&jerry'), 'Doesnt url encode parameter');
    }

    public function testPageLevel()
    {
        $about = $this->objFromFixture('Page', 'about');
        $staff = $this->objFromFixture('Page', 'staff');
        $this->assertEquals(1, $about->getPageLevel());
        $this->assertEquals(2, $staff->getPageLevel());
    }

    public function testAbsoluteLiveLink()
    {
        $parent = $this->objFromFixture('Page', 'about');
        $child = $this->objFromFixture('Page', 'staff');

        Config::modify()->set(SiteTree::class, 'nested_urls', true);

        $child->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        $parent->URLSegment = 'changed-on-live';
        $parent->write();
        $parent->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        $parent->URLSegment = 'changed-on-draft';
        $parent->write();

        $this->assertStringEndsWith('changed-on-live/my-staff/', $child->getAbsoluteLiveLink(false));
        $this->assertStringEndsWith('changed-on-live/my-staff/?stage=Live', $child->getAbsoluteLiveLink());
    }

    public function testDuplicateChildrenRetainSort()
    {
        $parent = new Page();
        $parent->Title = 'Parent';
        $parent->write();

        $child1 = new Page();
        $child1->ParentID = $parent->ID;
        $child1->Title = 'Child 1';
        $child1->Sort = 2;
        $child1->write();

        $child2 = new Page();
        $child2->ParentID = $parent->ID;
        $child2->Title = 'Child 2';
        $child2->Sort = 1;
        $child2->write();

        $duplicateParent = $parent->duplicateWithChildren();
        $duplicateChildren = $duplicateParent->AllChildren()->toArray();
        $this->assertCount(2, $duplicateChildren);

        $duplicateChild2 = array_shift($duplicateChildren);
        $duplicateChild1 = array_shift($duplicateChildren);


        $this->assertEquals('Child 1', $duplicateChild1->Title);
        $this->assertEquals('Child 2', $duplicateChild2->Title);

        // assertGreaterThan works by having the LOWER value first
        $this->assertGreaterThan($duplicateChild2->Sort, $duplicateChild1->Sort);
    }

    public function testDeleteFromStageOperatesRecursively()
    {
        Config::modify()->set(SiteTree::class, 'enforce_strict_hierarchy', false);
        $pageAbout = $this->objFromFixture('Page', 'about');
        $pageStaff = $this->objFromFixture('Page', 'staff');
        $pageStaffDuplicate = $this->objFromFixture('Page', 'staffduplicate');

        $pageAbout->delete();

        $this->assertNull(DataObject::get_by_id('Page', $pageAbout->ID));
        $this->assertTrue(DataObject::get_by_id('Page', $pageStaff->ID) instanceof Page);
        $this->assertTrue(DataObject::get_by_id('Page', $pageStaffDuplicate->ID) instanceof Page);
        Config::modify()->set(SiteTree::class, 'enforce_strict_hierarchy', true);
    }

    public function testDeleteFromStageOperatesRecursivelyStrict()
    {
        $pageAbout = $this->objFromFixture('Page', 'about');
        $pageStaff = $this->objFromFixture('Page', 'staff');
        $pageStaffDuplicate = $this->objFromFixture('Page', 'staffduplicate');

        $pageAbout->delete();

        $this->assertNull(DataObject::get_by_id('Page', $pageAbout->ID));
        $this->assertNull(DataObject::get_by_id('Page', $pageStaff->ID));
        $this->assertNull(DataObject::get_by_id('Page', $pageStaffDuplicate->ID));
    }

    public function testDuplicate()
    {
        $pageAbout = $this->objFromFixture('Page', 'about');
        $dupe = $pageAbout->duplicate();
        $this->assertEquals($pageAbout->Title, $dupe->Title);
        $this->assertNotEquals($pageAbout->URLSegment, $dupe->URLSegment);
        $this->assertNotEquals($pageAbout->Sort, $dupe->Sort);
    }

    public function testDeleteFromLiveOperatesRecursively()
    {
        Config::modify()->set(SiteTree::class, 'enforce_strict_hierarchy', false);
        $this->logInWithPermission('ADMIN');

        $pageAbout = $this->objFromFixture('Page', 'about');
        $pageAbout->publishRecursive();
        $pageStaff = $this->objFromFixture('Page', 'staff');
        $pageStaff->publishRecursive();
        $pageStaffDuplicate = $this->objFromFixture('Page', 'staffduplicate');
        $pageStaffDuplicate->publishRecursive();

        $parentPage = $this->objFromFixture('Page', 'about');

        $parentPage->doUnpublish();

        Versioned::set_stage(Versioned::LIVE);

        $this->assertNull(DataObject::get_by_id('Page', $pageAbout->ID));
        $this->assertTrue(DataObject::get_by_id('Page', $pageStaff->ID) instanceof Page);
        $this->assertTrue(DataObject::get_by_id('Page', $pageStaffDuplicate->ID) instanceof Page);
        Versioned::set_stage(Versioned::DRAFT);
        Config::modify()->set(SiteTree::class, 'enforce_strict_hierarchy', true);
    }

    public function testUnpublishDoesNotDeleteChildrenWithLooseHierachyOn()
    {
        Config::modify()->set(SiteTree::class, 'enforce_strict_hierarchy', false);
        $this->logInWithPermission('ADMIN');

        $pageAbout = $this->objFromFixture('Page', 'about');
        $pageAbout->publishRecursive();
        $pageStaff = $this->objFromFixture('Page', 'staff');
        $pageStaff->publishRecursive();
        $pageStaffDuplicate = $this->objFromFixture('Page', 'staffduplicate');
        $pageStaffDuplicate->publishRecursive();

        $parentPage = $this->objFromFixture('Page', 'about');
        $parentPage->doUnpublish();

        Versioned::set_stage(Versioned::LIVE);
        $this->assertNull(DataObject::get_by_id('Page', $pageAbout->ID));
        $this->assertTrue(DataObject::get_by_id('Page', $pageStaff->ID) instanceof Page);
        $this->assertTrue(DataObject::get_by_id('Page', $pageStaffDuplicate->ID) instanceof Page);
        Versioned::set_stage(Versioned::DRAFT);
        Config::modify()->set(SiteTree::class, 'enforce_strict_hierarchy', true);
    }

    public function testDeleteFromLiveOperatesRecursivelyStrict()
    {
        $this->logInWithPermission('ADMIN');

        $pageAbout = $this->objFromFixture('Page', 'about');
        $pageAbout->publishRecursive();
        $pageStaff = $this->objFromFixture('Page', 'staff');
        $pageStaff->publishRecursive();
        $pageStaffDuplicate = $this->objFromFixture('Page', 'staffduplicate');
        $pageStaffDuplicate->publishRecursive();

        $parentPage = $this->objFromFixture('Page', 'about');
        $parentPage->doUnpublish();

        Versioned::set_stage(Versioned::LIVE);
        $this->assertNull(DataObject::get_by_id('Page', $pageAbout->ID));
        $this->assertNull(DataObject::get_by_id('Page', $pageStaff->ID));
        $this->assertNull(DataObject::get_by_id('Page', $pageStaffDuplicate->ID));
        Versioned::set_stage(Versioned::DRAFT);
    }

    /**
     * Simple test to confirm that querying from a particular archive date doesn't throw
     * an error
     */
    public function testReadArchiveDate()
    {
        $date = '2009-07-02 14:05:07';
        Versioned::reading_archived_date($date);
        SiteTree::get()->where(array(
            '"SiteTree"."ParentID"' => 0
        ));
        Versioned::reading_archived_date(null);
        $this->assertEquals(
            Versioned::get_reading_mode(),
            'Archive.'
        );
    }

    public function testEditPermissions()
    {
        $editor = $this->objFromFixture(Member::class, "editor");

        $home = $this->objFromFixture("Page", "home");
        $staff = $this->objFromFixture("Page", "staff");
        $products = $this->objFromFixture("Page", "products");
        $product1 = $this->objFromFixture("Page", "product1");
        $product4 = $this->objFromFixture("Page", "product4");

        // Test logged out users cannot edit
        $this->logOut();
        $this->assertFalse($staff->canEdit());

        // Can't edit a page that is locked to admins
        $this->assertFalse($home->canEdit($editor));

        // Can edit a page that is locked to editors
        $this->assertTrue($products->canEdit($editor));

        // Can edit a child of that page that inherits
        $this->assertTrue($product1->canEdit($editor));

        // Can't edit a child of that page that has its permissions overridden
        $this->assertFalse($product4->canEdit($editor));
    }

    public function testCanEditWithAccessToAllSections()
    {
        $page = new Page();
        $page->write();
        $allSectionMember = $this->objFromFixture(Member::class, 'allsections');
        $securityAdminMember = $this->objFromFixture(Member::class, 'securityadmin');

        $this->assertTrue($page->canEdit($allSectionMember));
        $this->assertFalse($page->canEdit($securityAdminMember));
    }

    public function testCreatePermissions()
    {
        // Test logged out users cannot create
        $this->logOut();
        $this->assertFalse(singleton(SiteTree::class)->canCreate());

        // Login with another permission
        $this->logInWithPermission('DUMMY');
        $this->assertFalse(singleton(SiteTree::class)->canCreate());

        // Login with basic CMS permission
        $perms = SiteConfig::config()->required_permission;
        $this->logInWithPermission(reset($perms));
        $this->assertTrue(singleton(SiteTree::class)->canCreate());

        // Test creation underneath a parent which this user doesn't have access to
        $parent = $this->objFromFixture('Page', 'about');
        $this->assertFalse(singleton(SiteTree::class)->canCreate(null, array('Parent' => $parent)));

        // Test creation underneath a parent which doesn't allow a certain child
        $parentB = new SiteTreeTest_ClassB();
        $parentB->Title = 'Only Allows SiteTreeTest_ClassC';
        $parentB->write();
        $this->assertTrue(singleton(SiteTreeTest_ClassA::class)->canCreate(null));
        $this->assertFalse(singleton(SiteTreeTest_ClassA::class)->canCreate(null, array('Parent' => $parentB)));
        $this->assertTrue(singleton(SiteTreeTest_ClassC::class)->canCreate(null, array('Parent' => $parentB)));

        // Test creation underneath a parent which doesn't exist in the database. This should
        // fall back to checking whether the user can create pages at the root of the site
        $this->assertTrue(singleton(SiteTree::class)->canCreate(null, array('Parent' => singleton(SiteTree::class))));

        //Test we don't check for allowedChildren on parent context if it's not SiteTree instance
        $this->assertTrue(singleton(SiteTree::class)->canCreate(null, ['Parent' => $this->objFromFixture(SiteTreeTest_DataObject::class, 'relations')]));
    }

    public function testEditPermissionsOnDraftVsLive()
    {
        // Create an inherit-permission page
        $page = new Page();
        $page->write();
        $page->CanEditType = "Inherit";
        $page->publishRecursive();
        $pageID = $page->ID;

        // Lock down the site config
        $sc = $page->SiteConfig;
        $sc->CanEditType = 'OnlyTheseUsers';
        $sc->EditorGroups()->add($this->idFromFixture(Group::class, 'admins'));
        $sc->write();

        // Confirm that Member.editor can't edit the page
        $member = $this->objFromFixture(Member::class, 'editor');
        Security::setCurrentUser($member);
        $this->assertFalse($page->canEdit());

        // Change the page to be editable by Group.editors, but do not publish
        $admin = $this->objFromFixture(Member::class, 'admin');
        Security::setCurrentUser($admin);
        $page->CanEditType = 'OnlyTheseUsers';
        $page->EditorGroups()->add($this->idFromFixture(Group::class, 'editors'));
        $page->write();

        // Clear permission cache
        /** @var InheritedPermissions $checker */
        $checker = SiteTree::getPermissionChecker();
        $checker->clearCache();

        // Confirm that Member.editor can now edit the page
        $member = $this->objFromFixture(Member::class, 'editor');
        Security::setCurrentUser($member);
        $this->assertTrue($page->canEdit());

        // Publish the changes to the page
        $admin = $this->objFromFixture(Member::class, 'admin');
        Security::setCurrentUser($admin);
        $page->publishRecursive();

        // Confirm that Member.editor can still edit the page
        $member = $this->objFromFixture(Member::class, 'editor');
        Security::setCurrentUser($member);
        $this->assertTrue($page->canEdit());
    }

    public function testCompareVersions()
    {
        // Necessary to avoid
        $oldCleanerClass = Diff::$html_cleaner_class;
        Diff::$html_cleaner_class = SiteTreeTest_NullHtmlCleaner::class;

        $page = new Page();
        $page->write();
        $this->assertEquals(1, $page->Version);

        // Use inline element to avoid double wrapping applied to
        // blocklevel elements depending on HTMLCleaner implementation:
        // <ins><p> gets converted to <ins><p><inst>
        $page->Content = "<span>This is a test</span>";
        $page->write();
        $this->assertEquals(2, $page->Version);

        $diff = $page->compareVersions(1, 2);

        $processedContent = trim($diff->Content);
        $processedContent = preg_replace('/\s*</', '<', $processedContent);
        $processedContent = preg_replace('/>\s*/', '>', $processedContent);
        $this->assertEquals("<ins><span>This is a test</span></ins>", $processedContent);

        Diff::$html_cleaner_class = $oldCleanerClass;
    }

    public function testAuthorIDAndPublisherIDFilledOutOnPublish()
    {
        // Ensure that we have a member ID who is doing all this work
        $member = security::getCurrentUser();
        if ($member) {
            $memberID = $member->ID;
        } else {
            $member = $this->objFromFixture(Member::class, "admin");
            Security::setCurrentUser($member);
        }

        // Write the page
        $about = $this->objFromFixture('Page', 'about');
        $about->Title = "Another title";
        $about->write();

        // Check the version created
        $savedVersion = DB::query("SELECT \"AuthorID\", \"PublisherID\" FROM \"SiteTree_Versions\"
			WHERE \"RecordID\" = $about->ID ORDER BY \"Version\" DESC")->first();
        $this->assertEquals($memberID, $savedVersion['AuthorID']);
        $this->assertEquals(0, $savedVersion['PublisherID']);

        // Publish the page
        $about->publishRecursive();
        $publishedVersion = DB::query("SELECT \"AuthorID\", \"PublisherID\" FROM \"SiteTree_Versions\"
			WHERE \"RecordID\" = $about->ID ORDER BY \"Version\" DESC")->first();

        // Check the version created
        $this->assertEquals($memberID, $publishedVersion['AuthorID']);
        $this->assertEquals($memberID, $publishedVersion['PublisherID']);
    }

    public function testLinkShortcodeHandler()
    {
        $aboutPage = $this->objFromFixture('Page', 'about');
        $redirectPage = $this->objFromFixture(RedirectorPage::class, 'external');

        $parser = new ShortcodeParser();
        $parser->register('sitetree_link', array(SiteTree::class, 'link_shortcode_handler'));

        $aboutShortcode = sprintf('[sitetree_link,id=%d]', $aboutPage->ID);
        $aboutEnclosed  = sprintf('[sitetree_link,id=%d]Example Content[/sitetree_link]', $aboutPage->ID);

        $aboutShortcodeExpected = $aboutPage->Link();
        $aboutEnclosedExpected  = sprintf('<a href="%s">Example Content</a>', $aboutPage->Link());

        $this->assertEquals($aboutShortcodeExpected, $parser->parse($aboutShortcode), 'Test that simple linking works.');
        $this->assertEquals($aboutEnclosedExpected, $parser->parse($aboutEnclosed), 'Test enclosed content is linked.');

        $aboutPage->delete();

        $this->assertEquals($aboutShortcodeExpected, $parser->parse($aboutShortcode), 'Test that deleted pages still link.');
        $this->assertEquals($aboutEnclosedExpected, $parser->parse($aboutEnclosed));

        $aboutShortcode = '[sitetree_link,id="-1"]';
        $aboutEnclosed  = '[sitetree_link,id="-1"]Example Content[/sitetree_link]';

        $this->assertEquals('', $parser->parse($aboutShortcode), 'Test empty result if no suitable matches.');
        $this->assertEquals('', $parser->parse($aboutEnclosed));

        $redirectShortcode = sprintf('[sitetree_link,id=%d]', $redirectPage->ID);
        $redirectEnclosed  = sprintf('[sitetree_link,id=%d]Example Content[/sitetree_link]', $redirectPage->ID);
        $redirectExpected = 'http://www.google.com?a&amp;b';

        $this->assertEquals($redirectExpected, $parser->parse($redirectShortcode));
        $this->assertEquals(sprintf('<a href="%s">Example Content</a>', $redirectExpected), $parser->parse($redirectEnclosed));

        $this->assertEquals('', $parser->parse('[sitetree_link]'), 'Test that invalid ID attributes are not parsed.');
        $this->assertEquals('', $parser->parse('[sitetree_link,id="text"]'));
        $this->assertEquals('', $parser->parse('[sitetree_link]Example Content[/sitetree_link]'));
    }

    public function testIsCurrent()
    {
        $aboutPage = $this->objFromFixture('Page', 'about');
        $productPage = $this->objFromFixture('Page', 'products');

        Director::set_current_page($aboutPage);
        $this->assertTrue($aboutPage->isCurrent(), 'Assert that basic isCurrent checks works.');
        $this->assertFalse($productPage->isCurrent());

        $this->assertTrue(
            DataObject::get_one(SiteTree::class, array(
                '"SiteTree"."Title"' => 'About Us'
            ))->isCurrent(),
            'Assert that isCurrent works on another instance with the same ID.'
        );

        Director::set_current_page($newPage = new SiteTree());
        $this->assertTrue($newPage->isCurrent(), 'Assert that isCurrent works on unsaved pages.');
    }

    public function testIsSection()
    {
        $about = $this->objFromFixture('Page', 'about');
        $staff = $this->objFromFixture('Page', 'staff');
        $ceo   = $this->objFromFixture('Page', 'ceo');

        Director::set_current_page($about);
        $this->assertTrue($about->isSection());
        $this->assertFalse($staff->isSection());
        $this->assertFalse($ceo->isSection());

        Director::set_current_page($staff);
        $this->assertTrue($about->isSection());
        $this->assertTrue($staff->isSection());
        $this->assertFalse($ceo->isSection());

        Director::set_current_page($ceo);
        $this->assertTrue($about->isSection());
        $this->assertTrue($staff->isSection());
        $this->assertTrue($ceo->isSection());
    }

    public function testURLSegmentAutoUpdate()
    {
        $sitetree = new SiteTree();
        $sitetree->Title = _t(
            'SilverStripe\\CMS\\Controllers\\CMSMain.NEWPAGE',
            'New {pagetype}',
            array('pagetype' => $sitetree->i18n_singular_name())
        );
        $sitetree->write();
        $this->assertEquals(
            'new-page',
            $sitetree->URLSegment,
            'Sets based on default title on first save'
        );

        $sitetree->Title = 'Changed';
        $sitetree->write();
        $this->assertEquals(
            'changed',
            $sitetree->URLSegment,
            'Auto-updates when set to default title'
        );

        $sitetree->Title = 'Changed again';
        $sitetree->write();
        $this->assertEquals(
            'changed',
            $sitetree->URLSegment,
            'Does not auto-update once title has been changed'
        );
    }

    public function testURLSegmentAutoUpdateLocalized()
    {
        $oldLocale = i18n::get_locale();
        i18n::set_locale('de_DE');

        $sitetree = new SiteTree();
        $sitetree->Title = _t(
            'SilverStripe\\CMS\\Controllers\\CMSMain.NEWPAGE',
            'New {pagetype}',
            array('pagetype' => $sitetree->i18n_singular_name())
        );
        $sitetree->write();
        $this->assertEquals(
            'neue-seite',
            $sitetree->URLSegment,
            'Sets based on default title on first save'
        );

        $sitetree->Title = 'Changed';
        $sitetree->write();
        $this->assertEquals(
            'changed',
            $sitetree->URLSegment,
            'Auto-updates when set to default title'
        );

        $sitetree->Title = 'Changed again';
        $sitetree->write();
        $this->assertEquals(
            'changed',
            $sitetree->URLSegment,
            'Does not auto-update once title has been changed'
        );

        i18n::set_locale($oldLocale);
    }

    /**
     * @covers \SilverStripe\CMS\Model\SiteTree::validURLSegment
     */
    public function testValidURLSegmentURLSegmentConflicts()
    {
        $sitetree = new SiteTree();
        SiteTree::config()->nested_urls = false;

        $sitetree->URLSegment = 'home';
        $this->assertFalse($sitetree->validURLSegment(), 'URLSegment conflicts are recognised');
        $sitetree->URLSegment = 'home-noconflict';
        $this->assertTrue($sitetree->validURLSegment());

        $sitetree->ParentID   = $this->idFromFixture('Page', 'about');
        $sitetree->URLSegment = 'home';
        $this->assertFalse($sitetree->validURLSegment(), 'Conflicts are still recognised with a ParentID value');

        Config::modify()->set(SiteTree::class, 'nested_urls', true);

        $sitetree->ParentID   = 0;
        $sitetree->URLSegment = 'home';
        $this->assertFalse($sitetree->validURLSegment(), 'URLSegment conflicts are recognised');

        $sitetree->ParentID = $this->idFromFixture('Page', 'about');
        $this->assertTrue($sitetree->validURLSegment(), 'URLSegments can be the same across levels');

        $sitetree->URLSegment = 'my-staff';
        $this->assertFalse($sitetree->validURLSegment(), 'Nested URLSegment conflicts are recognised');
        $sitetree->URLSegment = 'my-staff-noconflict';
        $this->assertTrue($sitetree->validURLSegment());
    }

    /**
     * @covers \SilverStripe\CMS\Model\SiteTree::validURLSegment
     */
    public function testValidURLSegmentClassNameConflicts()
    {
        $sitetree = new SiteTree();
        $sitetree->URLSegment = Controller::class;

        $this->assertFalse($sitetree->validURLSegment(), 'Class name conflicts are recognised');
    }

    /**
     * @covers \SilverStripe\CMS\Model\SiteTree::validURLSegment
     */
    public function testValidURLSegmentControllerConflicts()
    {
        Config::modify()->set(SiteTree::class, 'nested_urls', true);

        $sitetree = new SiteTree();
        $sitetree->ParentID = $this->idFromFixture(SiteTreeTest_Conflicted::class, 'parent');

        $sitetree->URLSegment = 'index';
        $this->assertFalse($sitetree->validURLSegment(), 'index is not a valid URLSegment');

        $sitetree->URLSegment = 'conflicted-action';
        $this->assertFalse($sitetree->validURLSegment(), 'allowed_actions conflicts are recognised');

        $sitetree->URLSegment = 'conflicted-template';
        $this->assertFalse($sitetree->validURLSegment(), 'Action-specific template conflicts are recognised');

        $sitetree->URLSegment = 'valid';
        $this->assertTrue($sitetree->validURLSegment(), 'Valid URLSegment values are allowed');
    }

    public function testURLSegmentPrioritizesExtensionVotes()
    {
        $sitetree = new SiteTree();
        $sitetree->URLSegment = 'unique-segment';
        $this->assertTrue($sitetree->validURLSegment());

        SiteTree::add_extension(SiteTreeTest_Extension::class);
        $sitetree = new SiteTree();
        $sitetree->URLSegment = 'unique-segment';
        $this->assertFalse($sitetree->validURLSegment());
        SiteTree::remove_extension(SiteTreeTest_Extension::class);
    }

    public function testURLSegmentMultiByte()
    {
        URLSegmentFilter::config()->set('default_allow_multibyte', true);
        $sitetree = new SiteTree();
        $sitetree->write();

        $sitetree->URLSegment = 'brötchen';
        $sitetree->write();
        $sitetree = DataObject::get_by_id(SiteTree::class, $sitetree->ID, false);
        $this->assertEquals($sitetree->URLSegment, rawurlencode('brötchen'));

        $sitetree->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        $sitetree = DataObject::get_by_id(SiteTree::class, $sitetree->ID, false);
        $this->assertEquals($sitetree->URLSegment, rawurlencode('brötchen'));
        $sitetreeLive = Versioned::get_one_by_stage(SiteTree::class, Versioned::LIVE, '"SiteTree"."ID" = ' .$sitetree->ID, false);
        $this->assertEquals($sitetreeLive->URLSegment, rawurlencode('brötchen'));
    }

    public function testVersionsAreCreated()
    {
        $p = new Page();
        $p->Content = "one";
        $p->write();
        $this->assertEquals(1, $p->Version);

        // No changes don't bump version
        $p->write();
        $this->assertEquals(1, $p->Version);

        $p->Content = "two";
        $p->write();
        $this->assertEquals(2, $p->Version);

        // Only change meta-data don't bump version
        $p->HasBrokenLink = true;
        $p->write();
        $p->HasBrokenLink = false;
        $p->write();
        $this->assertEquals(2, $p->Version);

        $p->Content = "three";
        $p->write();
        $this->assertEquals(3, $p->Version);
    }

    public function testPageTypeClasses()
    {
        $classes = SiteTree::page_type_classes();
        $this->assertNotContains(SiteTree::class, $classes, 'Page types do not include base class');
        $this->assertContains('Page', $classes, 'Page types do contain subclasses');

        // Testing what happens in an incorrect config value is set - hide_ancestor should be a string
        Config::modify()->set(SiteTreeTest_ClassA::class, 'hide_ancestor', true);
        $newClasses = SiteTree::page_type_classes();
        $this->assertEquals(
            $classes,
            $newClasses,
            'Setting hide_ancestor to a boolean (incorrect) value caused a page class to be hidden'
        );
    }

    /**
     * Tests that core subclasses of SiteTree are included in allowedChildren() by default, but not instances of
     * HiddenClass
     */
    public function testAllowedChildrenContainsCoreSubclassesButNotHiddenClass()
    {
        $page = new SiteTree();
        $allowedChildren = $page->allowedChildren();

        $this->assertContains(
            VirtualPage::class,
            $allowedChildren,
            'Includes core subclasses by default'
        );

        $this->assertNotContains(
            SiteTreeTest_ClassE::class,
            $allowedChildren,
            'HiddenClass instances should not be returned'
        );
    }

    /**
     * Tests that various types of SiteTree classes will or will not be returned from the allowedChildren method
     * @dataProvider allowedChildrenProvider
     * @param string $className
     * @param array  $expected
     * @param string $assertionMessage
     */
    public function testAllowedChildren($className, $expected, $assertionMessage)
    {
        $class = new $className;
        $this->assertEquals($expected, $class->allowedChildren(), $assertionMessage);
    }

    /**
     * @return array
     */
    public function allowedChildrenProvider()
    {
        return [
            [
                // Class name
                SiteTreeTest_ClassA::class,
                // Expected
                [ SiteTreeTest_ClassB::class ],
                // Assertion message
                'Direct setting of allowed children'
            ],
            [
                SiteTreeTest_ClassB::class,
                [ SiteTreeTest_ClassC::class, SiteTreeTest_ClassCext::class ],
                'Includes subclasses'
            ],
            [
                SiteTreeTest_ClassC::class,
                [],
                'Null setting'
            ],
            [
                SiteTreeTest_ClassD::class,
                [SiteTreeTest_ClassC::class],
                'Excludes subclasses if class is prefixed by an asterisk'
            ]
        ];
    }

    public function testAllowedChildrenValidation()
    {
        $page = new SiteTree();
        $page->write();
        $classA = new SiteTreeTest_ClassA();
        $classA->write();
        $classB = new SiteTreeTest_ClassB();
        $classB->write();
        $classC = new SiteTreeTest_ClassC();
        $classC->write();
        $classD = new SiteTreeTest_ClassD();
        $classD->write();
        $classCext = new SiteTreeTest_ClassCext();
        $classCext->write();

        $classB->ParentID = $page->ID;
        $valid = $classB->doValidate();
        $this->assertTrue($valid->isValid(), "Does allow children on unrestricted parent");

        $classB->ParentID = $classA->ID;
        $valid = $classB->doValidate();
        $this->assertTrue($valid->isValid(), "Does allow child specifically allowed by parent");

        $classC->ParentID = $classA->ID;
        $valid = $classC->doValidate();
        $this->assertFalse($valid->isValid(), "Doesnt allow child on parents specifically restricting children");

        $classB->ParentID = $classC->ID;
        $valid = $classB->doValidate();
        $this->assertFalse($valid->isValid(), "Doesnt allow child on parents disallowing all children");

        $classB->ParentID = $classCext->ID;
        $valid = $classB->doValidate();
        $this->assertTrue($valid->isValid(), "Extensions of allowed classes are incorrectly reported as invalid");

        $classCext->ParentID = $classD->ID;
        $valid = $classCext->doValidate();
        $this->assertFalse($valid->isValid(), "Doesnt allow child where only parent class is allowed on parent node, and asterisk prefixing is used");
    }

    public function testClassDropdown()
    {
        $sitetree = new SiteTree();
        $method = new ReflectionMethod($sitetree, 'getClassDropdown');
        $method->setAccessible(true);

        Security::setCurrentUser(null);
        $this->assertArrayNotHasKey(SiteTreeTest_ClassA::class, $method->invoke($sitetree));

        $this->loginWithPermission('ADMIN');
        $this->assertArrayHasKey(SiteTreeTest_ClassA::class, $method->invoke($sitetree));

        $this->loginWithPermission('CMS_ACCESS_CMSMain');
        $this->assertArrayHasKey(SiteTreeTest_ClassA::class, $method->invoke($sitetree));

        Security::setCurrentUser(null);
    }

    public function testCanBeRoot()
    {
        $page = new SiteTree();
        $page->ParentID = 0;
        $page->write();

        $notRootPage = new SiteTreeTest_NotRoot();
        $notRootPage->ParentID = 0;
        $isDetected = false;
        try {
            $notRootPage->write();
        } catch (ValidationException $e) {
            $this->assertContains('is not allowed on the root level', $e->getMessage());
            $isDetected = true;
        }

        if (!$isDetected) {
            $this->fail('Fails validation with $can_be_root=false');
        }
    }

    public function testModifyStatusFlagByInheritance()
    {
        $node = new SiteTreeTest_StageStatusInherit();
        $treeTitle = $node->getTreeTitle();
        $this->assertContains('InheritedTitle', $treeTitle);
        $this->assertContains('inherited-class', $treeTitle);
    }

    public function testMenuTitleIsUnsetWhenEqualsTitle()
    {
        $page = new SiteTree();
        $page->Title = 'orig';
        $page->MenuTitle = 'orig';
        $page->write();

        // change menu title
        $page->MenuTitle = 'changed';
        $page->write();
        $page = SiteTree::get()->byID($page->ID);
        $this->assertEquals('changed', $page->getField('MenuTitle'));

        // change menu title back
        $page->MenuTitle = 'orig';
        $page->write();
        $page = SiteTree::get()->byID($page->ID);
        $this->assertEquals(null, $page->getField('MenuTitle'));
    }

    public function testMetaTagGeneratorDisabling()
    {
        $generator = Config::inst()->get(SiteTree::class, 'meta_generator');

        $page = new SiteTreeTest_PageNode();

        $meta = $page->MetaTags();
        $this->assertEquals(
            1,
            preg_match('/.*meta name="generator" content="SilverStripe - http:\/\/silverstripe.org".*/', $meta),
            'test default functionality - uses value from Config'
        );

        // test proper escaping of quotes in attribute value
        Config::modify()->set(SiteTree::class, 'meta_generator', 'Generator with "quotes" in it');
        $meta = $page->MetaTags();
        $this->assertEquals(
            1,
            preg_match('/.*meta name="generator" content="Generator with &quot;quotes&quot; in it".*/', $meta),
            'test proper escaping of values from Config'
        );

        // test empty generator - no tag should appear at all
        Config::modify()->set(SiteTree::class, 'meta_generator', '');
        $meta = $page->MetaTags();
        $this->assertEquals(
            0,
            preg_match('/.*meta name=.generator..*/', $meta),
            'test blank value means no tag generated'
        );

        // reset original value
        Config::modify()->set(SiteTree::class, 'meta_generator', $generator);
    }


    public function testGetBreadcrumbItems()
    {
        $page = $this->objFromFixture("Page", "breadcrumbs");
        $this->assertEquals(1, $page->getBreadcrumbItems()->count(), "Only display current page.");

        // Test breadcrumb order
        $page = $this->objFromFixture("Page", "breadcrumbs5");
        $breadcrumbs = $page->getBreadcrumbItems();
        $this->assertEquals($breadcrumbs->count(), 5, "Display all breadcrumbs");
        $this->assertEquals($breadcrumbs->first()->Title, "Breadcrumbs", "Breadcrumbs should be the first item.");
        $this->assertEquals($breadcrumbs->last()->Title, "Breadcrumbs 5", "Breadcrumbs 5 should be last item.");

        // Test breadcrumb max depth
        $breadcrumbs = $page->getBreadcrumbItems(2);
        $this->assertEquals($breadcrumbs->count(), 2, "Max depth should limit the breadcrumbs to 2 items.");
        $this->assertEquals($breadcrumbs->first()->Title, "Breadcrumbs 4", "First item should be Breadrcumbs 4.");
        $this->assertEquals($breadcrumbs->last()->Title, "Breadcrumbs 5", "Breadcrumbs 5 should be last.");
    }

    /**
     * Tests SiteTree::MetaTags
     * Note that this test makes no assumption on the closing of tags (other than <title></title>)
     */
    public function testMetaTags()
    {
        $this->logInWithPermission('ADMIN');
        $page = $this->objFromFixture('Page', 'metapage');

        // Test with title
        $meta = $page->MetaTags();
        $charset = Config::inst()->get(ContentNegotiator::class, 'encoding');
        $this->assertContains('<meta http-equiv="Content-Type" content="text/html; charset='.$charset.'"', $meta);
        $this->assertContains('<meta name="description" content="The &lt;br /&gt; and &lt;br&gt; tags"', $meta);
        $this->assertContains('<link rel="canonical" href="http://www.mysite.com/html-and-xml"', $meta);
        $this->assertContains('<meta name="x-page-id" content="'.$page->ID.'"', $meta);
        $this->assertContains('<meta name="x-cms-edit-link" content="'.$page->CMSEditLink().'"', $meta);
        $this->assertContains('<title>HTML &amp; XML</title>', $meta);

        // Test without title
        $meta = $page->MetaTags(false);
        $this->assertNotContains('<title>', $meta);
    }

    /**
     * Test that orphaned pages are handled correctly
     */
    public function testOrphanedPages()
    {
        $origStage = Versioned::get_reading_mode();

        // Setup user who can view draft content, but lacks cms permission.
        // To users such as this, orphaned pages should be inaccessible. canView for these pages is only
        // necessary for admin / cms users, who require this permission to edit / rearrange these pages.
        $permission = new Permission();
        $permission->Code = 'VIEW_DRAFT_CONTENT';
        $group = new Group(array('Title' => 'Staging Users'));
        $group->write();
        $group->Permissions()->add($permission);
        $member = new Member();
        $member->Email = 'someguy@example.com';
        $member->write();
        $member->Groups()->add($group);

        // both pages are viewable in stage
        Versioned::set_stage(Versioned::DRAFT);
        $about = $this->objFromFixture('Page', 'about');
        $staff = $this->objFromFixture('Page', 'staff');
        $this->assertFalse($about->isOrphaned());
        $this->assertFalse($staff->isOrphaned());
        $this->assertTrue($about->canView($member));
        $this->assertTrue($staff->canView($member));

        // Publishing only the child page to live should orphan the live record, but not the staging one
        $staff->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        $this->assertFalse($staff->isOrphaned());
        $this->assertTrue($staff->canView($member));
        Versioned::set_stage(Versioned::LIVE);
        $staff = $this->objFromFixture('Page', 'staff'); // Live copy of page
        $this->assertTrue($staff->isOrphaned()); // because parent isn't published
        $this->assertFalse($staff->canView($member));

        // Publishing the parent page should restore visibility
        Versioned::set_stage(Versioned::DRAFT);
        $about = $this->objFromFixture('Page', 'about');
        $about->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        Versioned::set_stage(Versioned::LIVE);
        $staff = $this->objFromFixture('Page', 'staff');
        $this->assertFalse($staff->isOrphaned());
        $this->assertTrue($staff->canView($member));

        // Removing staging page should not prevent live page being visible
        $about->deleteFromStage('Stage');
        $staff->deleteFromStage('Stage');
        $staff = $this->objFromFixture('Page', 'staff');
        $this->assertFalse($staff->isOrphaned());
        $this->assertTrue($staff->canView($member));

        // Cleanup
        Versioned::set_reading_mode($origStage);
    }

    /**
     * Test archived page behaviour
     */
    public function testArchivedPages()
    {
        $this->logInWithPermission('ADMIN');

        /** @var Page $page */
        $page = $this->objFromFixture('Page', 'home');
        $this->assertTrue($page->canAddChildren());
        $this->assertTrue($page->isOnDraft());
        $this->assertFalse($page->isPublished());

        // Publish
        $page->publishRecursive();
        $this->assertTrue($page->canAddChildren());
        $this->assertTrue($page->isOnDraft());
        $this->assertTrue($page->isPublished());

        // Archive
        $page->doArchive();
        $this->assertFalse($page->canAddChildren());
        $this->assertFalse($page->isOnDraft());
        $this->assertTrue($page->isArchived());
        $this->assertFalse($page->isPublished());
    }

    public function testCanNot()
    {
        // Test that
        $this->logInWithPermission('ADMIN');
        $page = new SiteTreeTest_AdminDenied();
        $this->assertFalse($page->canCreate());
        $this->assertFalse($page->canEdit());
        $this->assertFalse($page->canDelete());
        $this->assertFalse($page->canAddChildren());
        $this->assertFalse($page->canView());
    }

    public function testCanPublish()
    {
        $page = new SiteTreeTest_ClassD();
        $this->logOut();

        // Test that false overrides any can_publish = true
        SiteTreeTest_ExtensionA::$can_publish = true;
        SiteTreeTest_ExtensionB::$can_publish = false;
        $this->assertFalse($page->canPublish());
        SiteTreeTest_ExtensionA::$can_publish = false;
        SiteTreeTest_ExtensionB::$can_publish = true;
        $this->assertFalse($page->canPublish());

        // Test null extensions fall back to canEdit()
        SiteTreeTest_ExtensionA::$can_publish = null;
        SiteTreeTest_ExtensionB::$can_publish = null;
        $page->canEditValue = true;
        $this->assertTrue($page->canPublish());
        $page->canEditValue = false;
        $this->assertFalse($page->canPublish());
    }

    /**
     * Test url rewriting extensions
     */
    public function testLinkExtension()
    {
        Director::config()->set('alternate_base_url', 'http://www.baseurl.com');
        $page = new SiteTreeTest_ClassD();
        $page->URLSegment = 'classd';
        $page->write();
        $this->assertEquals(
            'http://www.updatedhost.com/classd/myaction?extra=1',
            $page->Link('myaction')
        );
        $this->assertEquals(
            'http://www.updatedhost.com/classd/myaction?extra=1',
            $page->AbsoluteLink('myaction')
        );
        $this->assertEquals(
            'classd/myaction',
            $page->RelativeLink('myaction')
        );
    }

    /**
     * Test that the controller name for a SiteTree instance can be gathered by appending "Controller" to the SiteTree
     * class name in a PSR-2 compliant manner.
     */
    public function testGetControllerName()
    {
        $class = new Page;
        $this->assertSame('PageController', $class->getControllerName());
    }

    /**
     * Test that underscored class names (legacy) are still supported (deprecation notice is issued though).
     */
    public function testGetControllerNameWithUnderscoresIsSupported()
    {
        $class = new SiteTreeTest_LegacyControllerName;
        $this->assertEquals(SiteTreeTest_LegacyControllerName_Controller::class, $class->getControllerName());
    }
}
