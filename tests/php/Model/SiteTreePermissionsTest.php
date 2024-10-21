<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Subsites\Extensions\SiteTreeSubsites;
use SilverStripe\Versioned\Versioned;
use PHPUnit\Framework\Attributes\DataProvider;

class SiteTreePermissionsTest extends FunctionalTest
{
    protected static $fixture_file = "SiteTreePermissionsTest.yml";

    protected static $illegal_extensions = [
        SiteTree::class => [SiteTreeSubsites::class],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // we're testing HTTP status codes before being redirected to login forms
        $this->autoFollowRedirection = false;

        // Ensure all pages are published
        /** @var SiteTree $page */
        foreach (SiteTree::get() as $page) {
            if ($page->URLSegment !== 'draft-only') {
                $page->publishSingle();
            }
        }
    }


    public function testAccessingStageWithBlankStage()
    {
        $this->autoFollowRedirection = false;

        /** @var SiteTree $draftOnlyPage */
        $draftOnlyPage = $this->objFromFixture(SiteTree::class, 'draftOnlyPage');
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
        $this->assertStringContainsString(
            Security::config()->get('login_url'),
            $response->getHeader('Location')
        );

        $this->logInWithPermission('ADMIN');

        $response = $this->get($draftOnlyPage->URLSegment . '?stage=Live');
        $this->assertEquals('404', $response->getStatusCode());

        $response = $this->get($draftOnlyPage->URLSegment . '?stage=Stage');
        $this->assertEquals('200', $response->getStatusCode());

        $draftOnlyPage->publishSingle();
        $response = $this->get($draftOnlyPage->URLSegment);
        $this->assertEquals('200', $response->getStatusCode());
    }

    public function testPermissionCheckingWorksOnDeletedPages()
    {
        // Set up fixture - a published page deleted from draft
        $this->logInWithPermission("ADMIN");
        $page = $this->objFromFixture(SiteTree::class, 'restrictedEditOnlySubadminGroup');
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
        $page = $this->objFromFixture(SiteTree::class, 'restrictedEditOnlySubadminGroup');
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
        $page = $this->objFromFixture(SiteTree::class, 'restrictedEditOnlySubadminGroup');
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
        // Get page & make sure it exists on Live
        /** @var SiteTree $page */
        $page = $this->objFromFixture(SiteTree::class, 'standardpage');
        $page->publishSingle();

        // Then make sure there's a new version on Stage
        $page->Title = '1';
        $page->write();

        $editor = $this->objFromFixture(Member::class, 'editor');
        $websiteuser = $this->objFromFixture(Member::class, 'websiteuser');

        $this->assertTrue($page->canViewStage('Live', $websiteuser));
        $this->assertFalse($page->canViewStage('Stage', $websiteuser));

        $this->assertTrue($page->canViewStage('Live', $editor));
        $this->assertTrue($page->canViewStage('Stage', $editor));
    }

    public function testAccessTabOnlyDisplaysWithGrantAccessPermissions()
    {
        $page = $this->objFromFixture(SiteTree::class, 'standardpage');

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

        $this->logOut();
    }

    public function testRestrictedViewLoggedInUsers()
    {
        $page = $this->objFromFixture(SiteTree::class, 'restrictedViewLoggedInUsers');

        // unauthenticated users
        $this->assertFalse(
            $page->canView(false),
            'Unauthenticated members cant view a page marked as "Viewable for any logged in users"'
        );
        $this->logOut();
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
            'Authenticated members can view a page marked as "Viewable for any logged in users" even if they dont ' .
            'have access to the CMS'
        );
        $this->logInAs($websiteuser);
        $response = $this->get($page->RelativeLink());
        $this->assertEquals(
            $response->getStatusCode(),
            200,
            'Authenticated members can view a page marked as "Viewable for any logged in users" even if they dont ' .
            'have access to the CMS'
        );
        $this->logOut();
    }

    public function testRestrictedViewOnlyTheseUsers()
    {
        $page = $this->objFromFixture(SiteTree::class, 'restrictedViewOnlyWebsiteUsers');

        // unauthenticcated users
        $this->assertFalse(
            $page->canView(false),
            'Unauthenticated members cant view a page marked as "Viewable by these groups"'
        );
        $this->logOut();
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
            'Authenticated members cant view a page marked as "Viewable by these groups" if theyre not in the listed ' .
            'groups'
        );
        $this->LogInAs($subadminuser);
        $response = $this->get($page->RelativeLink());
        $this->assertEquals(
            $response->getStatusCode(),
            403,
            'Authenticated members cant view a page marked as "Viewable by these groups" if theyre not in the listed ' .
            'groups'
        );
        $this->logOut();

        // website users
        $websiteuser = $this->objFromFixture(Member::class, 'websiteuser');
        $this->assertTrue(
            $page->canView($websiteuser),
            'Authenticated members can view a page marked as "Viewable by these groups" if theyre in the listed groups'
        );
        $this->logInAs($websiteuser);
        $response = $this->get($page->RelativeLink());
        $this->assertEquals(
            $response->getStatusCode(),
            200,
            'Authenticated members can view a page marked as "Viewable by these groups" if theyre in the listed groups'
        );
        $this->logOut();
    }

    public function testRestrictedEditLoggedInUsers()
    {
        $page = $this->objFromFixture(SiteTree::class, 'restrictedEditLoggedInUsers');

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
            'Authenticated members cant edit a page marked as "Editable by logged in users" if they dont have cms ' .
            'permissions'
        );

        // subadmin users
        $subadminuser = $this->objFromFixture(Member::class, 'subadmin');
        $this->assertTrue(
            $page->canEdit($subadminuser),
            'Authenticated members can edit a page marked as "Editable by logged in users" if they have cms ' .
            'permissions and belong to any of these groups'
        );
    }

    public function testRestrictedEditOnlySubadminGroup()
    {
        $page = $this->objFromFixture(SiteTree::class, 'restrictedEditOnlySubadminGroup');

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
            'Authenticated members cant edit a page marked as "Editable by these groups" if theyre not in the listed ' .
            'groups'
        );
    }

    public function testRestrictedViewInheritance()
    {
        $parentPage = $this->objFromFixture(SiteTree::class, 'parent_restrictedViewOnlySubadminGroup');
        $childPage = $this->objFromFixture(SiteTree::class, 'child_restrictedViewOnlySubadminGroup');

        // unauthenticated users
        $this->assertFalse(
            $childPage->canView(false),
            'Unauthenticated members cant view a page marked as "Viewable by these groups" by inherited permission'
        );
        $this->logOut();
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
            'Authenticated members can view a page marked as "Viewable by these groups" if theyre in the listed ' .
            'groups by inherited permission'
        );
        $this->logInAs($subadminuser);
        $response = $this->get($childPage->RelativeLink());
        $this->assertEquals(
            $response->getStatusCode(),
            200,
            'Authenticated members can view a page marked as "Viewable by these groups" if theyre in the listed ' .
            'groups by inherited permission'
        );
        $this->logOut();
    }

    public function testRestrictedEditInheritance()
    {
        $parentPage = $this->objFromFixture(SiteTree::class, 'parent_restrictedEditOnlySubadminGroup');
        $childPage = $this->objFromFixture(SiteTree::class, 'child_restrictedEditOnlySubadminGroup');

        // unauthenticated users
        $this->assertFalse(
            $childPage->canEdit(false),
            'Unauthenticated members cant edit a page marked as "Editable by these groups" by inherited permission'
        );

        // subadmin users
        $subadminuser = $this->objFromFixture(Member::class, 'subadmin');
        $this->assertTrue(
            $childPage->canEdit($subadminuser),
            'Authenticated members can edit a page marked as "Editable by these groups" if theyre in the listed ' .
            'groups by inherited permission'
        );
    }

    public function testDeleteRestrictedChild()
    {
        $parentPage = $this->objFromFixture(SiteTree::class, 'deleteTestParentPage');
        $childPage = $this->objFromFixture(SiteTree::class, 'deleteTestChildPage');

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
        $page = $this->objFromFixture(SiteTree::class, 'restrictedEditLoggedInUsers');
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
            'Authenticated members can edit a page that was deleted from stage and marked as "Editable by logged ' .
            'in users" if they have cms permissions and belong to any of these groups'
        );
    }

    public function testInheritCanViewFromSiteConfig()
    {
        $page = $this->objFromFixture(SiteTree::class, 'inheritWithNoParent');
        $siteconfig = $this->objFromFixture(SiteConfig::class, 'default');
        $editor = $this->objFromFixture(Member::class, 'editor');
        $editorGroup = $this->objFromFixture(Group::class, 'editorgroup');

        $siteconfig->CanViewType = 'Anyone';
        $siteconfig->write();
        $this->assertTrue(
            $page->canView(false),
            'Anyone can view a page when set to inherit from the SiteConfig, and SiteConfig has canView set to ' .
            'LoggedInUsers'
        );

        $siteconfig->CanViewType = 'LoggedInUsers';
        $siteconfig->write();
        $this->assertFalse(
            $page->canView(false),
            'Anonymous can\'t view a page when set to inherit from the SiteConfig, and SiteConfig has canView set to ' .
            'LoggedInUsers'
        );

        $siteconfig->CanViewType = 'LoggedInUsers';
        $siteconfig->write();
        $this->assertTrue(
            $page->canView($editor),
            'Users can view a page when set to inherit from the SiteConfig, and SiteConfig has canView set to ' .
            'LoggedInUsers'
        );

        $siteconfig->CanViewType = 'OnlyTheseUsers';
        $siteconfig->ViewerGroups()->add($editorGroup);
        $siteconfig->write();
        $this->assertTrue(
            $page->canView($editor),
            'Editors can view a page when set to inherit from the SiteConfig, and SiteConfig has canView set to ' .
            'OnlyTheseUsers'
        );
        $this->assertFalse(
            $page->canView(false),
            'Anonymous can\'t view a page when set to inherit from the SiteConfig, and SiteConfig has canView set ' .
            'to OnlyTheseUsers'
        );
    }

    public function testInheritCanEditFromSiteConfig()
    {
        $page = $this->objFromFixture(SiteTree::class, 'inheritWithNoParent');
        $siteconfig = $this->objFromFixture(SiteConfig::class, 'default');
        $editor = $this->objFromFixture(Member::class, 'editor');
        $user = $this->objFromFixture(Member::class, 'websiteuser');
        $editorGroup = $this->objFromFixture(Group::class, 'editorgroup');

        $siteconfig->CanEditType = 'LoggedInUsers';
        $siteconfig->write();

        $this->assertFalse(
            $page->canEdit(false),
            'Anonymous can\'t edit a page when set to inherit from the SiteConfig, and SiteConfig has canEdit set ' .
            'to LoggedInUsers'
        );
        Security::setCurrentUser($editor);
        $this->assertTrue(
            $page->canEdit(),
            'Users can edit a page when set to inherit from the SiteConfig, and SiteConfig has canEdit set to ' .
            'LoggedInUsers'
        );

        $siteconfig->CanEditType = 'OnlyTheseUsers';
        $siteconfig->EditorGroups()->add($editorGroup);
        $siteconfig->write();
        $this->assertTrue(
            $page->canEdit($editor),
            'Editors can edit a page when set to inherit from the SiteConfig, and SiteConfig has canEdit set to ' .
            'OnlyTheseUsers'
        );
        Security::setCurrentUser(null);
        $this->assertFalse(
            $page->canEdit(false),
            'Anonymous can\'t edit a page when set to inherit from the SiteConfig, and SiteConfig has canEdit set ' .
            'to OnlyTheseUsers'
        );
        Security::setCurrentUser($user);
        $this->assertFalse(
            $page->canEdit($user),
            'Website user can\'t edit a page when set to inherit from the SiteConfig, and SiteConfig has canEdit set ' .
            'to OnlyTheseUsers'
        );
    }

    /**
     * Test permissions on duplicate page
     */
    #[DataProvider('groupWithPermissions')]
    public function testDuplicatePageWithGroupPermissions(string $userName, string $method, bool $expected)
    {
        $originalPage = $this->objFromFixture(SiteTree::class, 'originalpage');
        $user = $this->objFromFixture(Member::class, $userName);
        $dupe = $originalPage->duplicate();

        $this->assertEquals($originalPage->Title, $dupe->Title);
        $this->assertEquals($dupe->CanViewType, 'OnlyTheseUsers');
        $this->assertEquals($dupe->CanEditType, 'OnlyTheseUsers');
        $this->assertSame($dupe->{$method}($user), $expected);
    }

    public static function groupWithPermissions(): array
    {
        return [
            'Subadmin can view page duplicate.' => [
                'subadmin',
                'canView',
                true,
            ],
            'Subadmin can edit page duplicate.' => [
                'subadmin',
                'canEdit',
                true,
            ],
            'Editor can view page duplicate.' => [
                'editor',
                'canView',
                true,
            ],
            'Editor can edit page duplicate.' => [
                'editor',
                'canEdit',
                true,
            ],
            'User with "allsections" permission can view page duplicate.' => [
                'allsections',
                'canView',
                true,
            ],
            'User with "allsections" permission cannot edit page duplicate.' => [
                'allsections',
                'canEdit',
                false,
            ],
            'Websiteuser permission cannot view page duplicate.' => [
                'websiteuser',
                'canView',
                false,
            ],
            'Websiteuser permission cannot edit page duplicate.' => [
                'websiteuser',
                'canEdit',
                false,
            ],
        ];
    }
}
