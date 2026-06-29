<?php

namespace SilverStripe\CMS\Tests\Controllers;

use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\SimpleCache\CacheInterface;
use ReflectionMethod;
use ReflectionProperty;
use SilverStripe\Admin\CMSBatchActionHandler;
use SilverStripe\CMS\Controllers\CMSMain;
use SilverStripe\CMS\Controllers\CMSSiteTreeFilter_PublishedPages;
use SilverStripe\CMS\Controllers\CMSSiteTreeFilter_Search;
use SilverStripe\CMS\Controllers\CMSSiteTreeFilter_StatusDeletedPages;
use SilverStripe\CMS\Controllers\CMSSiteTreeFilter_StatusRemovedFromDraftPages;
use SilverStripe\CMS\Model\RedirectorPage;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\CMS\Tests\Controllers\CMSMainTest\TestHierarchicalDataObject;
use SilverStripe\CMS\Tests\Controllers\CMSMainTest\TestHierarchicalDataObjectWithSort;
use SilverStripe\CMS\Tests\Controllers\CMSMainTest\TestStatusFlagsPage;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\CSSContentParser;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Versioned\RecursivePublishable;
use SilverStripe\Versioned\Versioned;

class CMSMainTest extends FunctionalTest
{
    protected static $fixture_file = 'CMSMainTest.yml';

    protected static $orig = [];

    protected static $extraDataObjects = [
        TestStatusFlagsPage::class,
        TestHierarchicalDataObject::class,
        TestHierarchicalDataObjectWithSort::class,
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // Clear automatically created siteconfigs (in case one was created outside of the specified fixtures).
        $ids = $this->allFixtureIDs(SiteConfig::class);
        if ($ids) {
            foreach (SiteConfig::get()->exclude('ID', $ids) as $config) {
                $config->delete();
            }
        }
    }

    public function testTreeHints()
    {
        $cache = Injector::inst()->get(CacheInterface::class . '.CMSMain_TreeHints');
        // Login as user with root creation privileges
        $user = $this->objFromFixture(Member::class, 'rootedituser');
        Security::setCurrentUser($user);
        $cache->clear();

        $rawHints = singleton(CMSMain::class)->TreeHints();
        $this->assertNotNull($rawHints);

        $rawHints = preg_replace('/^"(.*)"$/', '$1', Convert::xml2raw($rawHints) ?? '');
        $hints = json_decode($rawHints ?? '', true);

        $this->assertArrayHasKey('Root', $hints);
        $this->assertArrayHasKey('Page', $hints);
        $this->assertArrayHasKey('All', $hints);

        $this->assertArrayHasKey(
            CMSMainTest_ClassA::class,
            $hints['All'],
            'Global list shows allowed classes'
        );

        $this->assertArrayNotHasKey(
            CMSMainTest_HiddenClass::class,
            $hints['All'],
            'Global list does not list hidden classes'
        );

        $this->assertNotContains(
            CMSMainTest_ClassA::class,
            $hints['Root']['disallowedChildren'],
            'Limits root classes'
        );

        $this->assertContains(
            CMSMainTest_NotRoot::class,
            $hints['Root']['disallowedChildren'],
            'Limits root classes'
        );
    }

    public function testChildFilter()
    {
        $this->logInWithPermission('ADMIN');

        // Check page A
        $pageA = new CMSMainTest_ClassA();
        $pageA->write();
        $pageB = new CMSMainTest_ClassB();
        $pageB->write();

        // Check query
        $response = $this->get('admin/pages/childfilter?ParentID=' . $pageA->ID);
        $children = json_decode($response->getBody() ?? '');
        $this->assertFalse($response->isError());

        // Page A can't have unrelated children
        $this->assertContains(
            'Page',
            $children,
            'Limited parent lists disallowed classes'
        );

        // But it can create a ClassB
        $this->assertNotContains(
            CMSMainTest_ClassB::class,
            $children,
            'Limited parent omits explicitly allowed classes in disallowedChildren'
        );
    }

    /**
     * Test that getCMSFields works on each page type.
     * Mostly, this is just checking that the method doesn't return an error
     */
    public function testThatGetCMSFieldsWorksOnEveryRecordType()
    {
        $classes = ClassInfo::subclassesFor(SiteTree::class);
        array_shift($classes);

        foreach ($classes as $class) {
            $page = new $class();
            if ($page instanceof TestOnly) {
                continue;
            }
            if (!$page->config()->get('can_be_root')) {
                continue;
            }

            $page->Title = "Test $class page";
            $page->write();
            $page->flushCache();
            $page = SiteTree::get()->byID($page->ID);

            $this->assertTrue($page->getCMSFields() instanceof FieldList);
        }
    }

    public function testCanPublishPageWithUnpublishedParentWithStrictHierarchyOff()
    {
        $this->logInWithPermission('ADMIN');

        Config::modify()->set(SiteTree::class, 'enforce_strict_hierarchy', true);
        $parentPage = $this->objFromFixture(SiteTree::class, 'page3');
        $childPage = $this->objFromFixture(SiteTree::class, 'page1');

        $parentPage->doUnpublish();
        $childPage->doUnpublish();

        $actions = $childPage->getCMSActions()->getDataFields();
        $this->assertArrayHasKey(
            'action_publish',
            $actions,
            'Can publish a page with an unpublished parent with strict hierarchy off'
        );
        Config::modify()->set(SiteTree::class, 'enforce_strict_hierarchy', false);
    }

    /**
     * Test that a draft-deleted page can still be opened in the CMS
     */
    public function testDraftDeletedPageCanBeOpenedInCMS()
    {
        $this->logInWithPermission('ADMIN');

        // Set up a page that is delete from live
        $page = $this->objFromFixture(SiteTree::class, 'page1');
        $pageID = $page->ID;
        $page->publishRecursive();
        $page->delete();

        $response = $this->get('admin/pages/edit/show/' . $pageID);

        $livePage = Versioned::get_one_by_stage(SiteTree::class, Versioned::LIVE, [
                '"SiteTree"."ID"' => $pageID,
        ]);
        $this->assertInstanceOf(SiteTree::class, $livePage);
        $this->assertTrue($livePage->canDelete());

        // Check that the 'restore' button exists as a simple way of checking that the correct page is returned.
        $this->assertMatchesRegularExpression('/<button type="submit"[^>]+name="action_(restore|revert)"/i', $response->getBody());
    }

    /**
     * Test CMSMain::getRecord()
     */
    public function testGetRecord()
    {
        $this->logInWithPermission('ADMIN');

        // Set up a page that is delete from live
        $page1 = $this->objFromFixture(SiteTree::class, 'page1');
        $page1ID = $page1->ID;
        $page1->publishRecursive();
        $page1->delete();

        $cmsMain = CMSMain::create();
        $cmsMain->setRequest(Controller::curr()->getRequest());

        // Bad calls
        $this->assertNull($cmsMain->getRecord('0'));
        $this->assertNull($cmsMain->getRecord('asdf'));

        // Pages that are on draft and aren't on draft should both work
        $this->assertInstanceOf(SiteTree::class, $cmsMain->getRecord($page1ID));
        $this->assertInstanceOf(SiteTree::class, $cmsMain->getRecord($this->idFromFixture(SiteTree::class, 'page2')));

        // This functionality isn't actually used any more.
        $newPage = $cmsMain->getRecord('new-Page-5');
        $this->assertInstanceOf(SiteTree::class, $newPage);
        $this->assertEquals('5', $newPage->ParentID);
    }

    public function testDeletedPagesSiteTreeFilter()
    {
        $id = $this->idFromFixture(SiteTree::class, 'page3');
        $this->logInWithPermission('ADMIN');
        $result = $this->get('admin/pages/getsubtree?filter=CMSSiteTreeFilter_DeletedPages&ajax=1&ID=' . $id);
        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testCreationOfTopLevelPage()
    {
        $origFollow = $this->autoFollowRedirection;
        $this->autoFollowRedirection = false;

        $cmsUser = $this->objFromFixture(Member::class, 'allcmssectionsuser');
        $rootEditUser = $this->objFromFixture(Member::class, 'rootedituser');

        // with insufficient permissions
        Security::setCurrentUser($cmsUser);
        $this->get('admin/pages/add');
        $response = $this->post(
            'admin/pages/AddForm',
            [
                'ParentID' => '0',
                'RecordType' => RedirectorPage::class,
                'Locale' => 'en_US',
                'action_doAdd' => 1,
                'ajax' => 1,
            ],
            [
                'X-Pjax' => 'CurrentForm,Breadcrumbs',
            ]
        );
        // should redirect, which is a permission error
        $this->assertEquals(403, $response->getStatusCode(), 'Add TopLevel page must fail for normal user');

        // with correct permissions
        $this->logInAs($rootEditUser);
        $response = $this->get('admin/pages/add');

        $response = $this->post(
            'admin/pages/AddForm',
            [
                'ParentID' => '0',
                'RecordType' => RedirectorPage::class,
                'Locale' => 'en_US',
                'action_doAdd' => 1,
                'ajax' => 1,
            ],
            [
                'X-Pjax' => 'CurrentForm,Breadcrumbs',
            ]
        );

        $location = $response->getHeader('X-ControllerURL');
        $this->assertNotEmpty($location, 'Must be a redirect on success');
        $this->assertStringContainsString('/show/', $location, 'Must redirect to /show/ the new page');
        $this->logOut();

        $this->autoFollowRedirection = $origFollow;
    }

    public function testCreationOfRestrictedPage()
    {
        $origFollow = $this->autoFollowRedirection;
        $this->autoFollowRedirection = false;

        $this->logInAs('admin');

        // Create toplevel page
        $this->get('admin/pages/add');
        $response = $this->post(
            'admin/pages/AddForm',
            [
                'ParentID' => '0',
                'RecordType' => CMSMainTest_ClassA::class,
                'Locale' => 'en_US',
                'action_doAdd' => 1,
                'ajax' => 1,
            ],
            [
                'X-Pjax' => 'CurrentForm,Breadcrumbs',
            ]
        );
        $this->assertFalse($response->isError());
        $ok = preg_match('/edit\/show\/(\d*)/', $response->getHeader('X-ControllerURL') ?? '', $matches);
        $this->assertNotEmpty($ok);
        $newPageId = $matches[1];

        // Create allowed child
        $this->get('admin/pages/add');
        $response = $this->post(
            'admin/pages/AddForm',
            [
                'ParentID' => $newPageId,
                'RecordType' => CMSMainTest_ClassB::class,
                'Locale' => 'en_US',
                'action_doAdd' => 1,
                'ajax' => 1,
            ],
            [
                'X-Pjax' => 'CurrentForm,Breadcrumbs',
            ]
        );
        $this->assertFalse($response->isError());
        $this->assertEmpty($response->getBody());

        // Verify that the page was created and redirected to accurately
        $newerPage = SiteTree::get()->byID($newPageId)->AllChildren()->first();
        $this->assertNotEmpty($newerPage);
        $ok = preg_match('/edit\/show\/(\d*)/', $response->getHeader('X-ControllerURL') ?? '', $matches);
        $this->assertNotEmpty($ok);
        $newerPageID = $matches[1];
        $this->assertEquals($newerPage->ID, $newerPageID);

        // Create disallowed child
        $this->get('admin/pages/add');
        $response = $this->post(
            'admin/pages/AddForm',
            [
                'ParentID' => $newPageId,
                'RecordType' => RedirectorPage::class,
                'Locale' => 'en_US',
                'action_doAdd' => 1,
                'ajax' => 1,
            ],
            [
                'X-Pjax' => 'CurrentForm,Breadcrumbs',
            ]
        );
        $this->assertEquals(403, $response->getStatusCode(), 'Add disallowed child should fail');

        Security::setCurrentUser(null);

        $this->autoFollowRedirection = $origFollow;
    }

    public function testBreadcrumbs()
    {
        $page31 = $this->objFromFixture(SiteTree::class, 'page31');
        // Ensure there are no versioned badges populating the breadcrumbs
        if ($page31->hasExtension(Versioned::class)) {
            $page31->publishSingle();
        }
        $this->logInAs('admin');

        $response = $this->get('admin/pages/edit/show/' . $page31->ID);
        $this->assertCrumbs(
            ['Page 3', 'Page 3.1'],
            $response,
            'Edit breadcrumb includes all pages up to the one being edited without a tob level Page'
        );
    }

    public function testBreadcrumbsHaveStatusFlags()
    {
        $page = new TestStatusFlagsPage();
        $page->write();
        $this->logInAs('admin');

        $response = $this->get('admin/pages/edit/show/' . $page->ID);
        $parser = new CSSContentParser($response->getBody());
        $badges = $parser->getBySelector('.breadcrumbs-wrapper .crumb .badge');
        $badgesMarkup = '';
        foreach ($badges as $badge) {
            $badgesMarkup .= $badge->asXML();
        }
        $flagsMarkup = $page->getStatusFlagMarkup('badge--breadcrumbs');

        $this->assertSame($flagsMarkup, $badgesMarkup);
    }

    public function testBreadcrumbsListView()
    {
        $page311 = $this->objFromFixture(SiteTree::class, 'page311');
        // Ensure there are no versioned badges populating the breadcrumbs
        if ($page311->hasExtension(Versioned::class)) {
            $page311->publishSingle();
        }
        $this->logInAs('admin');

        $response = $this->get('admin/pages?ParentID=' . $page311->ID);
        $this->assertCrumbs(
            ['Pages', 'Page 3', 'Page 3.1', 'Page 3.1.1'],
            $response,
            'List view breadcrumb includes all pages and a Page link back to the root level'
        );
    }

    public function testBreadcrumbsListViewHasStatusFlags()
    {
        $page = new TestStatusFlagsPage();
        $page->write();
        $this->logInAs('admin');

        $response = $this->get('admin/pages?ParentID=' . $page->ID);
        $parser = new CSSContentParser($response->getBody());
        $badges = $parser->getBySelector('.breadcrumbs-wrapper .crumb .badge');
        $badgesMarkup = '';
        foreach ($badges as $badge) {
            $badgesMarkup .= $badge->asXML();
        }
        $flagsMarkup = $page->getStatusFlagMarkup('badge--breadcrumbs');

        $this->assertSame($flagsMarkup, $badgesMarkup);
    }

    public function testBreadcrumbsListViewTopLevel()
    {
        $this->logInAs('admin');

        $response = $this->get('admin/pages');
        $this->assertCrumbs(
            ['Pages'],
            $response,
            'Top level of list view includes only a Page crumb'
        );
    }

    public function testBreadcrumbsListViewWithPjax()
    {
        $page311 = $this->objFromFixture(SiteTree::class, 'page311');
        // Ensure there are no versioned badges populating the breadcrumbs
        if ($page311->hasExtension(Versioned::class)) {
            $page311->publishSingle();
        }
        $this->logInAs('admin');

        $response = $this->get('admin/pages?ParentID=' . $page311->ID);
        $this->assertCrumbs(
            ['Pages', 'Page 3', 'Page 3.1', 'Page 3.1.1'],
            $response,
            'List view breadcrumb includes all pages and a Page link back to the root level'
        );
    }

    public function testBreadcrumbsSearchView()
    {
        $page311 = $this->objFromFixture(SiteTree::class, 'page311');
        // Ensure there are no versioned badges populating the breadcrumbs
        if ($page311->hasExtension(Versioned::class)) {
            $page311->publishSingle();
        }
        $this->logInAs('admin');

        $response = $this->get(
            'admin/pages?ParentID=' . $page311->ID,
            null,
            [
                'X-Pjax' => 'ListViewForm,Breadcrumbs',
                'X-Requested-With' => 'XMLHttpRequest'
            ]
        );
        $jsonStr = $response->getBody();
        $data = json_decode($jsonStr, true);

        $parser = new CSSContentParser($data['Breadcrumbs']);
        $crumbs = $parser->getBySelector('.breadcrumbs-wrapper .crumb');

        $crumbs = array_map(function ($crumb) {
            // Whitespace doesn't matter, just the actual text
            return trim((string)$crumb);
        }, $crumbs);

        $this->assertNotNull($crumbs, 'Should have found some crumbs');
        $this->assertEquals(
            ['Pages', 'Page 3', 'Page 3.1', 'Page 3.1.1'],
            $crumbs,
            'List view breadcrumb includes all pages and a Page link back to the root level when access wia PJAX'
        );
    }

    private function assertCrumbs(array $expectedCrumbs, $response, string $message): void
    {
        $parser = new CSSContentParser($response->getBody());
        $crumbs = $parser->getBySelector('.breadcrumbs-wrapper .crumb');

        $crumbs = array_map(function ($crumb) {
            // Whitespace doesn't matter, just the actual text
            return trim((string)$crumb);
        }, $crumbs);

        $this->assertNotNull($crumbs, $message);
        $this->assertEquals($expectedCrumbs, $crumbs, $message);
    }

    public function testGetNewItem()
    {
        $controller = CMSMain::create();
        $controller->setRequest(Controller::curr()->getRequest());
        $id = 'new-Page-0';

        // Test success
        $page = $controller->getNewItem($id, false);

        $this->assertEquals($page->Title, 'New Page');
        $this->assertNotEquals($page->Sort, 0);
        $this->assertInstanceOf(SiteTree::class, $page);

        // Test failure
        try {
            $id = 'new-Member-0';
            $member = $controller->getNewItem($id, false);
            $this->fail('Should not be able to create a Member object');
        } catch (HTTPResponse_Exception $e) {
            $this->assertEquals($controller->getResponse()->getStatusCode(), 302);
        }
    }

    /**
     * Note this is not intended to be exhaustive - we're just validating
     * that the search filter stuff is generally applied to ListViewForm.
     * More robust testing of the filtering functionality is done separately.
     */
    public static function provideListViewForm(): array
    {
        return [
            'include all pages' => [
                'params' => [],
                'limit' => 5,
                'expectedPreLimitCount' => 26, // Note this explicitly excludes records with parents!
                'expectedTitles' => ['Home', 'Page 10', 'Page 11', 'Page 13', 'Page 14'],
            ],
            'filter by terms' => [
                'params' => ['q' => 'Page 4'],
                'limit' => null,
                'expectedPreLimitCount' => 3,
                'expectedTitles' => ['Page 14', 'Page 24', 'Page 4'],
            ],
            'deleted pages only' => [
                'params' => [
                    'FilterClass' => CMSSiteTreeFilter_StatusDeletedPages::class,
                ],
                'limit' => null,
                'expectedPreLimitCount' => 1,
                'expectedTitles' => ['Page 1'],
            ],
            'pages removed from draft only' => [
                'params' => [
                    'FilterClass' => CMSSiteTreeFilter_StatusRemovedFromDraftPages::class,
                ],
                'limit' => null,
                'expectedPreLimitCount' => 1,
                'expectedTitles' => ['Page 12'],
            ],
            'published pages only' => [
                'params' => [
                    'FilterClass' => CMSSiteTreeFilter_PublishedPages::class,
                ],
                'limit' => null,
                'expectedPreLimitCount' => 2,
                'expectedTitles' => ['Page 11', 'Page 12'],
            ],
        ];
    }

    #[DataProvider('provideListViewForm')]
    public function testListViewForm(array $params, ?int $limit, int $expectedPreLimitCount, array $expectedTitles): void
    {
        $request = Controller::curr()->getRequest();
        $requestVarsReflection = new ReflectionProperty($request, 'getVars');
        $requestVarsReflection->setValue($request, ['q' => $params]);
        $controller = CMSMain::create()->setRequest($request);

        /** @var DataList<SiteTree> $pages */
        $pages = $controller->ListViewForm()->Fields()->dataFieldByName('Record')->getList();

        // Change state of tree
        $page1 = $this->objFromFixture(SiteTree::class, 'page1');
        $page11 = $this->objFromFixture(SiteTree::class, 'page11');
        $page12 = $this->objFromFixture(SiteTree::class, 'page12');
        // Deleted
        $page1->doUnpublish();
        $page1->delete();
        // Live and draft
        $page11->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        // Live only
        $page12->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        $page12->delete();

        $this->assertSame($expectedTitles, $pages->sort('Title')->limit($limit)->column('Title'));
        $this->assertCount($expectedPreLimitCount, $pages);
    }

    public static function provideListViewFormParentID(): array
    {
        return [
            [
                'includeFilter' => true,
            ],
            [
                'includeFilter' => false,
            ],
        ];
    }

    #[DataProvider('provideListViewFormParentID')]
    public function testListViewFormParentID(bool $includeFilter): void
    {
        $page3 = $this->objFromFixture(SiteTree::class, 'page3');
        $page11 = $this->objFromFixture(SiteTree::class, 'page11');
        $page12 = $this->objFromFixture(SiteTree::class, 'page12');
        $page11->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        $page12->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);

        $request = Controller::curr()->getRequest();
        $params = ['ParentID' => $page3->ID];
        if ($includeFilter) {
            $params['q'] = ['FilterClass' => CMSSiteTreeFilter_PublishedPages::class];
        }
        $requestVarsReflection = new ReflectionProperty($request, 'getVars');
        $requestVarsReflection->setValue($request, $params);
        $controller = CMSMain::create()->setRequest($request);

        /** @var DataList<SiteTree> $pages */
        $pages = $controller->ListViewForm()->Fields()->dataFieldByName('Record')->getList();

        if ($includeFilter) {
            $this->assertSame(['Page 11', 'Page 12'], $pages->column('Title'));
        } else {
            $this->assertSame(['Page 3.1', 'Page 3.2'], $pages->column('Title'));
        }
    }

    /**
     * Testing retrieval and type of CMS edit form.
     */
    public function testGetEditForm()
    {
        // Login is required prior to accessing a CMS form.
        $this->loginWithPermission('ADMIN');

        // Get a associated with a fixture page.
        $page = $this->objFromFixture(SiteTree::class, 'page1');
        $controller = CMSMain::create();
        $controller->setRequest(Controller::curr()->getRequest());
        $form = $controller->getEditForm($page->ID);
        $this->assertInstanceOf("SilverStripe\\Forms\\Form", $form);

        // Ensure that the form will not "validate" on delete or "unpublish" actions.
        $exemptActions = $form->getValidationExemptActions();
        $this->assertContains("delete", $exemptActions);
        $this->assertContains("unpublish", $exemptActions);
    }

    /**
     * Test that changed classes save with the correct class name
     */
    public function testChangeClass()
    {
        $this->logInWithPermission('ADMIN');
        $cms = CMSMain::create();
        $cms->setRequest(Controller::curr()->getRequest());
        $page = new CMSMainTest_ClassA();
        $page->Title = 'Class A';
        $page->write();

        $form = $cms->getEditForm($page->ID);
        $form->loadDataFrom(['ClassName' => CMSMainTest_ClassB::class]);
        $result = $cms->save([
            'ID' => $page->ID,
            'ClassName' => CMSMainTest_ClassB::class,
        ], $form);
        $this->assertEquals(200, $result->getStatusCode());

        $newPage = SiteTree::get()->byID($page->ID);

        $this->assertInstanceOf(CMSMainTest_ClassB::class, $newPage);
        $this->assertEquals(CMSMainTest_ClassB::class, $newPage->ClassName);
        $this->assertEquals('Class A', $newPage->Title);
    }

    public function testTreeHintsCache()
    {
        $cms = CMSMain::create();
        $reflectionAllowedSubclasses = new ReflectionMethod($cms, 'getAllowedSubClasses');
        /** @var Member $user */
        $user = $this->objFromFixture(Member::class, 'rootedituser');
        Security::setCurrentUser($user);
        $pageClass = array_values($reflectionAllowedSubclasses->invoke($cms))[0];
        $mockPageMissesCache = $this->getMockBuilder($pageClass)
            ->onlyMethods(['canCreate'])
            ->getMock();
        $mockPageMissesCache
            ->expects($this->exactly(3))
            ->method('canCreate');

        $mockPageHitsCache = $this->getMockBuilder($pageClass)
            ->onlyMethods(['canCreate'])
            ->getMock();
        $mockPageHitsCache
            ->expects($this->never())
            ->method('canCreate');


        // Initially, cache misses (1)
        Injector::inst()->registerService($mockPageMissesCache, $pageClass);
        $hints = $cms->TreeHints();
        $this->assertNotNull($hints);

        // Now it hits
        Injector::inst()->registerService($mockPageHitsCache, $pageClass);
        $hints = $cms->TreeHints();
        $this->assertNotNull($hints);

        // Mutating member record invalidates cache. Misses (2)
        $user->FirstName = 'changed';
        $user->write();
        Injector::inst()->registerService($mockPageMissesCache, $pageClass);
        $hints = $cms->TreeHints();
        $this->assertNotNull($hints);

        // Now it hits again
        Injector::inst()->registerService($mockPageHitsCache, $pageClass);
        $hints = $cms->TreeHints();
        $this->assertNotNull($hints);

        // Different user. Misses. (3)
        $user = $this->objFromFixture(Member::class, 'allcmssectionsuser');
        Security::setCurrentUser($user);
        Injector::inst()->registerService($mockPageMissesCache, $pageClass);
        $hints = $cms->TreeHints();
        $this->assertNotNull($hints);
    }

    public function testCanOrganiseTree()
    {
        $cms = CMSMain::create();

        $this->assertFalse($cms->CanOrganiseTree());

        $this->logInWithPermission('CMS_ACCESS_CMSMain');
        $this->assertFalse($cms->CanOrganiseTree());

        $this->logOut();
        $this->logInWithPermission('SITETREE_REORGANISE');
        $this->assertTrue($cms->CanOrganiseTree());

        $this->logOut();
        $this->logInWithPermission('ADMIN');
        $this->assertTrue($cms->CanOrganiseTree());
    }

    public function testGetCreatableSubClassesCache()
    {
        // Use injector because CMSMain defines some injectable dependencies
        $cms = CMSMain::create();
        $reflectionMethod = new ReflectionMethod($cms, 'getCreatableSubClasses');

        $siteTree = new SiteTree();
        $user = $this->objFromFixture(Member::class, 'allcmssectionsuser');
        Security::setCurrentUser($user);
        $classes = ClassInfo::getValidSubClasses(SiteTree::class);
        SiteTree::singleton()->updateAllowedSubClasses($classes);
        $pageClass = array_values($classes)[0];

        $mockPageMissesCache = $this->getMockBuilder($pageClass)
            ->onlyMethods(['canCreate'])
            ->getMock();
        $mockPageMissesCache
            ->expects($this->exactly(3))
            ->method('canCreate');

        $mockPageHitsCache = $this->getMockBuilder($pageClass)
            ->onlyMethods(['canCreate'])
            ->getMock();
        $mockPageHitsCache
            ->expects($this->never())
            ->method('canCreate');

        // Initially, cache misses (1)
        Injector::inst()->registerService($mockPageMissesCache, $pageClass);
        $result = $reflectionMethod->invoke($cms, $siteTree);
        $this->assertNotNull($result);

        // Now it hits
        Injector::inst()->registerService($mockPageHitsCache, $pageClass);
        $result = $reflectionMethod->invoke($cms, $siteTree);
        $this->assertNotNull($result);


        // Mutating member record invalidates cache. Misses (2)
        $user->FirstName = 'changed';
        $user->write();
        Injector::inst()->registerService($mockPageMissesCache, $pageClass);
        $result = $reflectionMethod->invoke($cms, $siteTree);
        $this->assertNotNull($result);

        // Now it hits again
        Injector::inst()->registerService($mockPageHitsCache, $pageClass);
        $result = $reflectionMethod->invoke($cms, $siteTree);
        $this->assertNotNull($result);

        // Different user. Misses. (3)
        $user = $this->objFromFixture(Member::class, 'rootedituser');
        Security::setCurrentUser($user);
        Injector::inst()->registerService($mockPageMissesCache, $pageClass);
        $result = $reflectionMethod->invoke($cms, $siteTree);
        $this->assertNotNull($result);
    }

    public static function provideInit(): array
    {
        return [
            [
                'class' => DataObject::class,
                'throwsException' => true,
            ],
            [
                'class' => TestHierarchicalDataObject::class,
                'throwsException' => true,
            ],
            [
                'class' => TestHierarchicalDataObjectWithSort::class,
                'throwsException' => false,
            ],
            [
                'class' => SiteTree::class,
                'throwsException' => false,
            ],
        ];
    }

    #[DataProvider('provideInit')]
    public function testInit(string $class, bool $throwsException): void
    {
        CMSMain::config()->set('model_class', $class);
        // Use injector because CMSMain defines some injectable dependencies
        $cms = CMSMain::create();
        $initReflection = new ReflectionMethod($cms, 'init');
        if ($throwsException) {
            $this->expectException(LogicException::class);
        } else {
            $this->expectNotToPerformAssertions();
        }
        $initReflection->invoke($cms);
    }

    public static function provideTreeAsULPrepopulateOptions(): array
    {
        return [
            'base-class-config' => [
                'siteTreeConfig' => true,
                'expected' => 'mySiteTreeMethod',
            ],
            'extension-config' => [
                'siteTreeConfig' => false,
                'expected' => 'getChildrenForTree',
            ],
        ];
    }

    #[DataProvider('provideTreeAsULPrepopulateOptions')]
    public function testTreeAsULPrepopulateOptions(bool $siteTreeConfig, string $expected): void
    {
        $cmsMain = new CMSMain();
        if ($siteTreeConfig) {
            SiteTree::config()->set('tree_children_method', 'mySiteTreeMethod');
        }
        $refl = new ReflectionMethod($cmsMain, 'getTreeAsULPrepopulateOptions');
        $actual = $refl->invoke($cmsMain, SiteTree::class)['childrenMethod'];
        $this->assertSame($expected, $actual);
    }

    public static function provideGetRecordTreeMarkup(): array
    {
        return [
            'no-specials' => [
                'title' => 'About Us',
                'expected' => 'About Us',
            ],
            'amp' => [
                'title' => 'About & Us',
                'expected' => 'About &amp; Us',
            ],
            'single-quote' => [
                'title' => 'About \' Us',
                'expected' => 'About &#039; Us',
            ],
            'double-quote' => [
                'title' => 'About " Us',
                'expected' => 'About &quot; Us',
            ],
            'less-than' => [
                'title' => 'About < Us',
                'expected' => 'About &lt; Us',
            ],
            'greater-than' => [
                'title' => 'About > Us',
                'expected' => 'About &gt; Us',
            ],
        ];
    }

    #[DataProvider('provideGetRecordTreeMarkup')]
    public function testGetRecordTreeMarkup(string $title, string $expected): void
    {
        $page = new SiteTree(['Title' => $title]);
        $cmsMain = new CMSMain();
        $html = $cmsMain->getRecordTreeMarkup($page);
        $actual = strip_tags($html);
        $this->assertSame($expected, $actual);
    }

    public static function provideGetListViewBreadcrumbs(): array
    {
        return [
            'plain' => [
                'parentTitle' => 'About Us',
                'expected' => '<p class="small cms-list__item-breadcrumbs">About Us/</p>',
            ],
            'amp' => [
                'parentTitle' => 'About & Us',
                'expected' => '<p class="small cms-list__item-breadcrumbs">About &amp; Us/</p>',
            ],
            'script-tag' => [
                'parentTitle' => '<script>alert(1)</script>',
                'expected' => '<p class="small cms-list__item-breadcrumbs">'
                    . '&lt;script&gt;alert(1)&lt;/script&gt;/</p>',
            ],
            'double-quote' => [
                'parentTitle' => 'About " Us',
                'expected' => '<p class="small cms-list__item-breadcrumbs">About &quot; Us/</p>',
            ],
        ];
    }

    #[DataProvider('provideGetListViewBreadcrumbs')]
    public function testGetListViewBreadcrumbs(string $parentTitle, string $expected): void
    {
        $parent = new SiteTree(['Title' => $parentTitle]);
        $parent->write();
        $child = new SiteTree(['Title' => 'Child', 'ParentID' => $parent->ID]);
        $child->write();

        $controller = new CMSMain();
        $reflectionMethod = new ReflectionMethod($controller, 'getListViewBreadcrumbs');
        $this->assertSame($expected, $reflectionMethod->invoke($controller, $child));
    }

    public function testGetListViewBreadcrumbsNoAncestors(): void
    {
        $page = new SiteTree(['Title' => 'Top Level']);
        $page->write();

        $controller = new CMSMain();
        $reflectionMethod = new ReflectionMethod($controller, 'getListViewBreadcrumbs');
        $this->assertSame(
            '<p class="small cms-list__item-breadcrumbs"></p>',
            $reflectionMethod->invoke($controller, $page)
        );
    }

    public function testGetArchiveWarningMessage(): void
    {
        $controller = new CMSMain();
        $reflectionMethod = new ReflectionMethod($controller, 'getArchiveWarningMessage');
        $page = new SiteTree(['Title' => 'my page']);
        $page->write();

        $this->assertSame(
            'Warning: This record will be unpublished before being sent to the archive.\n\nAre you sure you want to proceed?',
            $reflectionMethod->invoke($controller, $page)
        );

        $childPage = new SiteTree(['ParentID' => $page->ID]);
        $childPage->write();

        $this->assertSame(
            'Warning: This record and all of its child records will be unpublished before being sent to the archive.\n\nAre you sure you want to proceed?',
            $reflectionMethod->invoke($controller, $page)
        );
    }
}
