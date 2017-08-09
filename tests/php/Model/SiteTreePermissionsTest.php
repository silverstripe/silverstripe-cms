<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Versioned\Versioned;

/**
 * @todo Test canAddChildren()
 * @todo Test canCreate()
 */
class SiteTreePermissionsTest extends FunctionalTest
{
    protected static $fixture_file = "SiteTreePermissionsTest.yml";

    protected static $illegal_extensions = array(
        SiteTree::class => array('SiteTreeSubsites')
    );

    public function setUp()
    {
        parent::setUp();

        $this->useDraftSite();

        // we're testing HTTP status codes before being redirected to login forms
        $this->autoFollowRedirection = false;
    }


    public function testAccessingStageWithBlankStage()
    {
        $this->useDraftSite(false);
        $this->autoFollowRedirection = false;

        /** @var Page $draftOnlyPage */
        $draftOnlyPage = $this->objFromFixture('Page', 'draftOnlyPage');
        $this->logOut();

        $response = $this->get($draftOnlyPage->URLSegment . '?stage=Live');
        $this->assertEquals($response->getStatusCode(), '404');

        $response = $this->get($draftOnlyPage->URLSegment);
        $this->assertEquals($response->getStatusCode(), '404');

        // should be prompted for a login
        try {
            $response = $this->get($draftOnlyPage->URLSegment . '?stage=Stage');
        } catch (HTTPResponse_Exception $responseException) {
            $response = $responseException->getResponse();
        }
        $this->assertEquals($response->getStatusCode(), '302');
        $this->assertContains(
            Security::config()->get('login_url'),
            $response->getHeader('Location')
        );

        $this->logInWithPermission('ADMIN');

        $response = $this->get($draftOnlyPage->URLSegment . '?stage=Live');
        $this->assertEquals('404', $response->getStatusCode());

        $response = $this->get($draftOnlyPage->URLSegment . '?stage=Stage');
        $this->assertEquals('200', $response->getStatusCode());

        // Stage is remembered from last request
        $response = $this->get($draftOnlyPage->URLSegment);
        $this->assertEquals('200', $response->getStatusCode());
    }

    public function testPermissionCheckingWorksOnDeletedPages()
    {
        // Set up fixture - a published page deleted from draft
        $this->logInWithPermission("ADMIN");
        $page = $this->objFromFixture('Page', 'restrictedEditOnlySubadminGroup');
        $pageID = $page->ID;
        $this->assertTrue($page->publishRecursive());
        $page->delete();

        // Re-fetch the page from the live site
        $page = Versioned::get_one_by_stage(SiteTree::class, 'Live', "\"SiteTree\".\"ID\" = $pageID");

        // subadmin has edit rights on that page
        $member = $this->objFromFixture(Member::class, 'subadmin');
        Security::setCurrentUser($member);

        // Test can_edit_multiple
        $this->assertEquals(
            [ $pageID => true ],
            SiteTree::getPermissionChecker()->canEditMultiple([$pageID], $member)
        );

        // Test canEdit
        Security::setCurrentUser($member);
        $this->assertTrue($page->canEdit());
    }

    public function testPermissionCheckingWorksOnUnpublishedPages()
    {
        // Set up fixture - an unpublished page
        $this->logInWithPermission("ADMIN");
        $page = $this->objFromFixture('Page', 'restrictedEditOnlySubadminGroup');
        $pageID = $page->ID;
        $page->doUnpublish();

        // subadmin has edit rights on that page
        $member = $this->objFromFixture(Member::class, 'subadmin');
        Security::setCurrentUser($member);

        // Test can_edit_multiple
        $this->assertEquals(
            [ $pageID => true ],
            SiteTree::getPermissionChecker()->canEditMultiple([$pageID], $member)
        );

        // Test canEdit
        Security::setCurrentUser($member);
        $this->assertTrue($page->canEdit());
    }

    public function testCanEditOnPageDeletedFromStageAndLiveReturnsFalse()
    {
        // Find a page that exists and delete it from both stage and published
        $this->logInWithPermission("ADMIN");
        $page = $this->objFromFixture('Page', 'restrictedEditOnlySubadminGroup');
        $pageID = $page->ID;
        $page->doUnpublish();
        $page->delete();

        // We'll need to resurrect the page from the version cache to test this case
        $page = Versioned::get_latest_version(SiteTree::class, $pageID);

        // subadmin had edit rights on that page, but now it's gone
        $member = $this->objFromFixture(Member::class, 'subadmin');
        Security::setCurrentUser($member);

        $this->assertFalse($page->canEdit());
    }

    public function testCanViewStage()
    {
        $this->useDraftSite(false); // useDraftSite deliberately disables checking the stage as part of canView

        // Get page & make sure it exists on Live
        $page = $this->objFromFixture('Page', 'standardpage');
        $page->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);

        // Then make sure there's a new version on Stage
        $page->Title = 1;
        $page->write();

        $editor = $this->objFromFixture(Member::class, 'editor');
        $websiteuser = $this->objFromFixture(Member::class, 'websiteuser');

        $this->assertTrue($page->canViewStage('Live', $websiteuser));
        $this->assertFalse($page->canViewStage('Stage', $websiteuser));

        $this->assertTrue($page->canViewStage('Live', $editor));
        $this->assertTrue($page->canViewStage('Stage', $editor));

        $this->useDraftSite();
    }

    public function testAccessTabOnlyDisplaysWithGrantAccessPermissions()
    {
        $page = $this->objFromFixture('Page', 'standardpage');

        $subadminuser = $this->objFromFixture(Member::class, 'subadmin');
        Security::setCurrentUser($subadminuser);
        $fields = $page->getSettingsFields();
        $this->assertFalse(
            $fields->dataFieldByName('CanViewType')->isReadonly(),
            'Users with SITETREE_GRANT_ACCESS permission can change "view" permissions in cms fields'
        );
        $this->assertFalse(
            $fields->dataFieldByName('CanEditType')->isReadonly(),
            'Users with SITETREE_GRANT_ACCESS permission can change "edit" permissions in cms fields'
        );

        $editoruser = $this->objFromFixture(Member::class, 'editor');
        Security::setCurrentUser($editoruser);
        $fields = $page->getSettingsFields();
        $this->assertTrue(
            $fields->dataFieldByName('CanViewType')->isReadonly(),
            'Users without SITETREE_GRANT_ACCESS permission cannot change "view" permissions in cms fields'
        );
        $this->assertTrue(
            $fields->dataFieldByName('CanEditType')->isReadonly(),
            'Users without SITETREE_GRANT_ACCESS permission cannot change "edit" permissions in cms fields'
        );

        $this->session()->set('loggedInAs', null);
    }

    public function testRestrictedViewLoggedInUsers()
    {
        $page = $this->objFromFixture('Page', 'restrictedViewLoggedInUsers');

        // unauthenticated users
        $this->assertFalse(
            $page->canView(false),
            'Unauthenticated members cant view a page marked as "Viewable for any logged in users"'
        );
        Security::setCurrentUser(null);
        $response = $this->get($page->RelativeLink());
        $this->assertEquals(
            $response->getStatusCode(),
            302,
            'Unauthenticated members cant view a page marked as "Viewable for any logged in users"'
        );

        // website users
        $websiteuser = $this->objFromFixture(Member::class, 'websiteuser');
        $this->assertTrue(
            $page->canView($websiteuser),
            'Authenticated members can view a page marked as "Viewable for any logged in users" even if they dont have access to the CMS'
        );
        Security::setCurrentUser($websiteuser);
        $response = $this->get($page->RelativeLink());
        $this->assertEquals(
            $response->getStatusCode(),
            200,
            'Authenticated members can view a page marked as "Viewable for any logged in users" even if they dont have access to the CMS'
        );
        Security::setCurrentUser(null);
    }

    public function testRestrictedViewOnlyTheseUsers()
    {
        $page = $this->objFromFixture('Page', 'restrictedViewOnlyWebsiteUsers');

        // unauthenticcated users
        $this->assertFalse(
            $page->canView(false),
            'Unauthenticated members cant view a page marked as "Viewable by these groups"'
        );
        Security::setCurrentUser(null);
        $response = $this->get($page->RelativeLink());
        $this->assertEquals(
            $response->getStatusCode(),
            302,
            'Unauthenticated members cant view a page marked as "Viewable by these groups"'
        );

        // subadmin users
        $subadminuser = $this->objFromFixture(Member::class, 'subadmin');
        $this->assertFalse(
            $page->canView($subadminuser),
            'Authenticated members cant view a page marked as "Viewable by these groups" if theyre not in the listed groups'
        );
        Security::setCurrentUser($subadminuser);
        $response = $this->get($page->RelativeLink());
        $this->assertEquals(
            $response->getStatusCode(),
            403,
            'Authenticated members cant view a page marked as "Viewable by these groups" if theyre not in the listed groups'
        );
        Security::setCurrentUser(null);

        // website users
        $websiteuser = $this->objFromFixture(Member::class, 'websiteuser');
        $this->assertTrue(
            $page->canView($websiteuser),
            'Authenticated members can view a page marked as "Viewable by these groups" if theyre in the listed groups'
        );
        Security::setCurrentUser($websiteuser);
        $response = $this->get($page->RelativeLink());
        $this->assertEquals(
            $response->getStatusCode(),
            200,
            'Authenticated members can view a page marked as "Viewable by these groups" if theyre in the listed groups'
        );
        Security::setCurrentUser(null);
    }

    public function testRestrictedEditLoggedInUsers()
    {
        $page = $this->objFromFixture('Page', 'restrictedEditLoggedInUsers');

        // unauthenticcated users
        $this->assertFalse(
            $page->canEdit(false),
            'Unauthenticated members cant edit a page marked as "Editable by logged in users"'
        );

        // website users
        $websiteuser = $this->objFromFixture(Member::class, 'websiteuser');
        Security::setCurrentUser($websiteuser);
        $this->assertFalse(
            $page->canEdit($websiteuser),
            'Authenticated members cant edit a page marked as "Editable by logged in users" if they dont have cms permissions'
        );

        // subadmin users
        $subadminuser = $this->objFromFixture(Member::class, 'subadmin');
        $this->assertTrue(
            $page->canEdit($subadminuser),
            'Authenticated members can edit a page marked as "Editable by logged in users" if they have cms permissions and belong to any of these groups'
        );
    }

    public function testRestrictedEditOnlySubadminGroup()
    {
        $page = $this->objFromFixture('Page', 'restrictedEditOnlySubadminGroup');

        // unauthenticated users
        $this->assertFalse(
            $page->canEdit(false),
            'Unauthenticated members cant edit a page marked as "Editable by these groups"'
        );

        // subadmin users
        $subadminuser = $this->objFromFixture(Member::class, 'subadmin');
        $this->assertTrue(
            $page->canEdit($subadminuser),
            'Authenticated members can view a page marked as "Editable by these groups" if theyre in the listed groups'
        );

        // website users
        $websiteuser = $this->objFromFixture(Member::class, 'websiteuser');
        $this->assertFalse(
            $page->canEdit($websiteuser),
            'Authenticated members cant edit a page marked as "Editable by these groups" if theyre not in the listed groups'
        );
    }

    public function testRestrictedViewInheritance()
    {
        $parentPage = $this->objFromFixture('Page', 'parent_restrictedViewOnlySubadminGroup');
        $childPage = $this->objFromFixture('Page', 'child_restrictedViewOnlySubadminGroup');

        // unauthenticated users
        $this->assertFalse(
            $childPage->canView(false),
            'Unauthenticated members cant view a page marked as "Viewable by these groups" by inherited permission'
        );
        Security::setCurrentUser(null);
        $response = $this->get($childPage->RelativeLink());
        $this->assertEquals(
            $response->getStatusCode(),
            302,
            'Unauthenticated members cant view a page marked as "Viewable by these groups" by inherited permission'
        );

        // subadmin users
        $subadminuser = $this->objFromFixture(Member::class, 'subadmin');
        $this->assertTrue(
            $childPage->canView($subadminuser),
            'Authenticated members can view a page marked as "Viewable by these groups" if theyre in the listed groups by inherited permission'
        );
        Security::setCurrentUser($subadminuser);
        $response = $this->get($childPage->RelativeLink());
        $this->assertEquals(
            $response->getStatusCode(),
            200,
            'Authenticated members can view a page marked as "Viewable by these groups" if theyre in the listed groups by inherited permission'
        );
        Security::setCurrentUser(null);
    }

    public function testRestrictedEditInheritance()
    {
        $parentPage = $this->objFromFixture('Page', 'parent_restrictedEditOnlySubadminGroup');
        $childPage = $this->objFromFixture('Page', 'child_restrictedEditOnlySubadminGroup');

        // unauthenticated users
        $this->assertFalse(
            $childPage->canEdit(false),
            'Unauthenticated members cant edit a page marked as "Editable by these groups" by inherited permission'
        );

        // subadmin users
        $subadminuser = $this->objFromFixture(Member::class, 'subadmin');
        $this->assertTrue(
            $childPage->canEdit($subadminuser),
            'Authenticated members can edit a page marked as "Editable by these groups" if theyre in the listed groups by inherited permission'
        );
    }

    public function testDeleteRestrictedChild()
    {
        $parentPage = $this->objFromFixture('Page', 'deleteTestParentPage');
        $childPage = $this->objFromFixture('Page', 'deleteTestChildPage');

        // unauthenticated users
        $this->assertFalse(
            $parentPage->canDelete(false),
            'Unauthenticated members cant delete a page if it doesnt have delete permissions on any of its descendants'
        );
        $this->assertFalse(
            $childPage->canDelete(false),
            'Unauthenticated members cant delete a child page marked as "Editable by these groups"'
        );
    }

    public function testRestrictedEditLoggedInUsersDeletedFromStage()
    {
        $page = $this->objFromFixture('Page', 'restrictedEditLoggedInUsers');
        $pageID = $page->ID;

        $this->logInWithPermission("ADMIN");

        $page->publishRecursive();
        $page->deleteFromStage('Stage');

        // Get the live version of the page
        $page = Versioned::get_one_by_stage(SiteTree::class, Versioned::LIVE, "\"SiteTree\".\"ID\" = $pageID");
        $this->assertTrue(is_object($page), 'Versioned::get_one_by_stage() is returning an object');

        // subadmin users
        $subadminuser = $this->objFromFixture(Member::class, 'subadmin');
        $this->assertTrue(
            $page->canEdit($subadminuser),
            'Authenticated members can edit a page that was deleted from stage and marked as "Editable by logged in users" if they have cms permissions and belong to any of these groups'
        );
    }

    public function testInheritCanViewFromSiteConfig()
    {
        $page = $this->objFromFixture('Page', 'inheritWithNoParent');
        $siteconfig = $this->objFromFixture(SiteConfig::class, 'default');
        $editor = $this->objFromFixture(Member::class, 'editor');
        $editorGroup = $this->objFromFixture(Group::class, 'editorgroup');

        $siteconfig->CanViewType = 'Anyone';
        $siteconfig->write();
        $this->assertTrue($page->canView(false), 'Anyone can view a page when set to inherit from the SiteConfig, and SiteConfig has canView set to LoggedInUsers');

        $siteconfig->CanViewType = 'LoggedInUsers';
        $siteconfig->write();
        $this->assertFalse($page->canView(false), 'Anonymous can\'t view a page when set to inherit from the SiteConfig, and SiteConfig has canView set to LoggedInUsers');

        $siteconfig->CanViewType = 'LoggedInUsers';
        $siteconfig->write();
        $this->assertTrue($page->canView($editor), 'Users can view a page when set to inherit from the SiteConfig, and SiteConfig has canView set to LoggedInUsers');

        $siteconfig->CanViewType = 'OnlyTheseUsers';
        $siteconfig->ViewerGroups()->add($editorGroup);
        $siteconfig->write();
        $this->assertTrue($page->canView($editor), 'Editors can view a page when set to inherit from the SiteConfig, and SiteConfig has canView set to OnlyTheseUsers');
        $this->assertFalse($page->canView(false), 'Anonymous can\'t view a page when set to inherit from the SiteConfig, and SiteConfig has canView set to OnlyTheseUsers');
    }

    public function testInheritCanEditFromSiteConfig()
    {
        $page = $this->objFromFixture('Page', 'inheritWithNoParent');
        $siteconfig = $this->objFromFixture(SiteConfig::class, 'default');
        $editor = $this->objFromFixture(Member::class, 'editor');
        $user = $this->objFromFixture(Member::class, 'websiteuser');
        $editorGroup = $this->objFromFixture(Group::class, 'editorgroup');

        $siteconfig->CanEditType = 'LoggedInUsers';
        $siteconfig->write();

        $this->assertFalse($page->canEdit(false), 'Anonymous can\'t edit a page when set to inherit from the SiteConfig, and SiteConfig has canEdit set to LoggedInUsers');
        Security::setCurrentUser($editor);
        $this->assertTrue($page->canEdit(), 'Users can edit a page when set to inherit from the SiteConfig, and SiteConfig has canEdit set to LoggedInUsers');

        $siteconfig->CanEditType = 'OnlyTheseUsers';
        $siteconfig->EditorGroups()->add($editorGroup);
        $siteconfig->write();
        $this->assertTrue($page->canEdit($editor), 'Editors can edit a page when set to inherit from the SiteConfig, and SiteConfig has canEdit set to OnlyTheseUsers');
        Security::setCurrentUser(null);
        $this->assertFalse($page->canEdit(false), 'Anonymous can\'t edit a page when set to inherit from the SiteConfig, and SiteConfig has canEdit set to OnlyTheseUsers');
        Security::setCurrentUser($user);
        $this->assertFalse($page->canEdit($user), 'Website user can\'t edit a page when set to inherit from the SiteConfig, and SiteConfig has canEdit set to OnlyTheseUsers');
    }
}
