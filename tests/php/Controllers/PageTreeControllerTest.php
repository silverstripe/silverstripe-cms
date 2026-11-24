<?php

namespace SilverStripe\CMS\Tests\Controllers;

use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;
use SilverStripe\CMS\Controllers\CMSMain;
use SilverStripe\CMS\Controllers\PageTreeController;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\CMS\Tests\Controllers\PageTreeControllerTest\DenyAccessExtension;
use SilverStripe\CMS\Tests\Controllers\PageTreeControllerTest\EmptyTreeTitlePage;
use SilverStripe\CMS\Tests\Controllers\PageTreeControllerTest\HiddenFromTreePage;
use SilverStripe\CMS\Tests\Controllers\PageTreeControllerTest\JsonErrorExtension;
use SilverStripe\CMS\Tests\Controllers\PageTreeControllerTest\NoChildrenPage;
use SilverStripe\CMS\Tests\Controllers\PageTreeControllerTest\TreeNodeDataExtension;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Hierarchy\Hierarchy;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Versioned\Versioned;

class PageTreeControllerTest extends FunctionalTest
{
    protected static $fixture_file = 'PageTreeControllerTest.yml';

    protected static $extra_dataobjects = [
        NoChildrenPage::class,
        HiddenFromTreePage::class,
        EmptyTreeTitlePage::class,
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // Hierarchy never clears its static caches of tree children, so these caches keep values from the fixtures of
        // earlier tests.
        foreach (['children_for_tree_ids_cache', 'children_for_tree_obj_cache'] as $property) {
            (new ReflectionProperty(Hierarchy::class, $property))->setValue(null, []);
        }
    }

    public function testApiReadChildrenAtTheTopLevel(): void
    {
        $this->logInWithPermission('CMS_ACCESS_CMSMain');

        $response = $this->ajaxGet('admin/pages/tree/children/0');
        $this->assertSame('application/json', $response->getHeader('Content-Type'));
        $nodes = $this->decodeSuccess($response)['nodes'];

        $this->assertSame($this->idsOf(['home', 'parent', 'limitedParent', 'menuTitle']), array_column($nodes, 'id'));
        $parentPage = $this->findNode($nodes, 'parent');
        $this->assertSame('(Record type: Page) Parent', $parentPage['tooltip']);
        $this->assertSame('admin/pages?ParentID=' . $parentPage['id'], $parentPage['listViewUrl']);
        $this->assertSame('admin/pages/edit/show/' . $parentPage['id'], $parentPage['editUrl']);

        $home = $this->findNode($nodes, 'home');
        $this->assertSame(
            'record-icon font-icon-page class-SilverStripe_CMS_Model_SiteTree homepage',
            $home['iconClasses']
        );
        $this->assertNull($home['listViewUrl']);
        $this->assertSame('Menu title', $this->findNode($nodes, 'menuTitle')['treeTitle']);
    }

    public static function provideApiReadChildrenOfALimitedParent(): array
    {
        return [
            'more children than the threshold' => [2, true, 0],
            'as many children as the threshold' => [3, false, 3],
        ];
    }

    #[DataProvider('provideApiReadChildrenOfALimitedParent')]
    public function testApiReadChildrenOfALimitedParent(int $threshold, bool $expectedLimited, int $expectedCount): void
    {
        Config::modify()->set(SiteTree::class, 'node_threshold_leaf', $threshold);
        $this->logInWithPermission('CMS_ACCESS_CMSMain');

        $limitedParent = $this->findNode($this->getNodes('admin/pages/tree/children/0'), 'limitedParent');
        $this->assertSame($expectedLimited, $limitedParent['limited']);
        $this->assertTrue($limitedParent['hasChildren']);
        $this->assertSame('admin/pages?ParentID=' . $limitedParent['id'], $limitedParent['listViewUrl']);
        $children = $this->decodeSuccess($this->ajaxGet('admin/pages/tree/children/' . $limitedParent['id']));
        $this->assertSame($expectedLimited, $children['limited']);
        $this->assertCount($expectedCount, $children['nodes']);
    }

    public function testApiReadChildrenWithAnEmptyTreeTitle(): void
    {
        $menuTitlePage = $this->objFromFixture(SiteTree::class, 'menuTitle');
        (new EmptyTreeTitlePage(['Title' => 'Has a title', 'ParentID' => $menuTitlePage->ID]))->write();
        $this->logInWithPermission('CMS_ACCESS_CMSMain');

        $nodes = $this->getNodes('admin/pages/tree/children/' . $menuTitlePage->ID);

        $this->assertSame('(no title)', $nodes[0]['treeTitle']);
    }

    public static function provideIndex(): array
    {
        return [
            'no action' => ['admin/pages/tree', 404],
            'index action' => ['admin/pages/tree/index', 400],
        ];
    }

    #[DataProvider('provideIndex')]
    public function testIndex(string $url, int $expectedCode): void
    {
        $this->logInWithPermission('CMS_ACCESS_CMSMain');

        $this->assertJsonError($expectedCode, $this->ajaxGet($url));
    }

    public function testApiReadChildrenLeavesOutHiddenClasses(): void
    {
        Config::modify()->set(SiteTree::class, 'hide_from_cms_tree', [HiddenFromTreePage::class]);
        Config::modify()->set(SiteTree::class, 'node_threshold_leaf', 2);
        $parentPage = $this->objFromFixture(SiteTree::class, 'parent');
        $menuTitlePage = $this->objFromFixture(SiteTree::class, 'menuTitle');
        for ($i = 1; $i <= 3; $i++) {
            (new HiddenFromTreePage(['Title' => "Hidden child $i", 'ParentID' => $parentPage->ID]))->write();
        }
        (new HiddenFromTreePage(['Title' => 'Hidden only child', 'ParentID' => $menuTitlePage->ID]))->write();
        (new HiddenFromTreePage(['Title' => 'Hidden top level', 'Sort' => 5]))->write();
        $this->logInWithPermission('CMS_ACCESS_CMSMain');

        $nodes = $this->getNodes('admin/pages/tree/children/0');
        $this->assertSame($this->idsOf(['home', 'parent', 'limitedParent', 'menuTitle']), array_column($nodes, 'id'));
        $parentNode = $this->findNode($nodes, 'parent');
        $this->assertTrue($parentNode['hasChildren']);
        $this->assertFalse($parentNode['limited']);
        $menuTitleNode = $this->findNode($nodes, 'menuTitle');
        $this->assertFalse($menuTitleNode['hasChildren']);
        $this->assertNull($menuTitleNode['listViewUrl']);

        $children = $this->decodeSuccess($this->ajaxGet('admin/pages/tree/children/' . $parentPage->ID));
        $this->assertFalse($children['limited']);
        $this->assertSame($this->idsOf(['child', 'cannotEdit']), array_column($children['nodes'], 'id'));
    }

    public static function provideApiReadErrors(): array
    {
        return [
            'children of an unknown parent' => ['children/999999', 404],
            'children of a parent that is not a number' => ['children/abc', 400],
            'children of a negative number' => ['children/-1', 400],
            'children without a parent' => ['children', 400],
            'children action URL' => ['apiReadChildren/0', 400],
            'unknown record' => ['999999', 404],
            'top level' => ['0', 404],
            'record that is not a number' => ['abc', 400],
            'negative number' => ['-1', 400],
            'read action URL' => ['apiRead/1', 400],
        ];
    }

    #[DataProvider('provideApiReadErrors')]
    public function testApiReadErrors(string $route, int $expectedCode): void
    {
        $this->logInWithPermission('CMS_ACCESS_CMSMain');

        $this->assertJsonError($expectedCode, $this->ajaxGet('admin/pages/tree/' . $route));
    }

    public function testApiRead(): void
    {
        $this->logInWithPermission('CMS_ACCESS_CMSMain');

        $grandchildID = $this->idFromFixture(SiteTree::class, 'grandchild');
        $parentID = $this->idFromFixture(SiteTree::class, 'parent');

        $grandchild = $this->decodeSuccess($this->ajaxGet('admin/pages/tree/' . $grandchildID));
        $this->assertSame($this->idsOf(['parent', 'child']), $grandchild['path']);
        $children = $this->getNodes('admin/pages/tree/children/' . $this->idFromFixture(SiteTree::class, 'child'));
        $this->assertSame($this->findNode($children, 'grandchild'), $grandchild['node']);

        $parent = $this->decodeSuccess($this->ajaxGet('admin/pages/tree/' . $parentID));
        $this->assertSame([], $parent['path']);
        $topLevel = $this->getNodes('admin/pages/tree/children/0');
        $this->assertSame($this->findNode($topLevel, 'parent'), $parent['node']);
    }

    public function testApiReadOfAPageTheMemberCannotView(): void
    {
        $this->logInWithPermission('CMS_ACCESS_CMSMain');

        $cannotViewID = $this->idFromFixture(SiteTree::class, 'cannotView');
        $cannotView = $this->decodeSuccess($this->ajaxGet('admin/pages/tree/' . $cannotViewID));
        $this->assertSame($this->idsOf(['parent', 'child']), $cannotView['path']);
        $this->assertFalse($cannotView['node']['canView']);
    }

    public static function provideApiReadOnlyAllowsGet(): array
    {
        return [
            'children route' => ['admin/pages/tree/children/0'],
            'record route' => ['admin/pages/tree/1'],
            'children action URL' => ['admin/pages/tree/apiReadChildren/0'],
            'read action URL' => ['admin/pages/tree/apiRead/1'],
        ];
    }

    #[DataProvider('provideApiReadOnlyAllowsGet')]
    public function testApiReadOnlyAllowsGet(string $url): void
    {
        $this->logInWithPermission('CMS_ACCESS_CMSMain');

        $this->assertJsonError(405, $this->post($url, []));
    }

    public function testJsonErrorRunsTheHooks(): void
    {
        PageTreeController::add_extension(JsonErrorExtension::class);
        $this->logInWithPermission('CMS_ACCESS_CMSMain');

        $this->assertSame('404', $this->ajaxGet('admin/pages/tree/children/999999')->getHeader('X-Tree-Error'));
        $this->assertSame('400', $this->ajaxGet('admin/pages/tree/abc')->getHeader('X-Tree-Error'));
    }

    public function testApiReadChildrenStatusFlags(): void
    {
        $childPage = $this->objFromFixture(SiteTree::class, 'child');
        $childPage->publishSingle();
        $childPage->Content = '<p>Changed in draft</p>';
        $childPage->write();
        $this->logInWithPermission('CMS_ACCESS_CMSMain');
        $parentPage = $this->objFromFixture(SiteTree::class, 'parent');

        $nodes = $this->getNodes('admin/pages/tree/children/' . $parentPage->ID);
        $cannotEditNode = $this->findNode($nodes, 'cannotEdit');
        $this->assertSame(['addedtodraft'], array_column($cannotEditNode['statusFlags'], 'key'));
        $this->assertContains('status-addedtodraft', $this->words($cannotEditNode));
        $child = $this->findNode($nodes, 'child');
        $this->assertSame(['modified'], array_column($child['statusFlags'], 'key'));
        $this->assertContains('status-modified', $this->words($child));
    }

    public function testApiReadChildrenListsAPageTheMemberCannotView(): void
    {
        $this->logInWithPermission('CMS_ACCESS_CMSMain');
        $child = $this->objFromFixture(SiteTree::class, 'child');

        $nodes = $this->getNodes('admin/pages/tree/children/' . $child->ID);
        $this->assertSame($this->idsOf(['grandchild', 'cannotView']), array_column($nodes, 'id'));
        $cannotViewNode = $this->findNode($nodes, 'cannotView');
        $this->assertFalse($cannotViewNode['canView']);
        $this->assertContains('disabled', $this->words($cannotViewNode));

        $children = $this->getNodes('admin/pages/tree/children/' . $cannotViewNode['id']);
        $this->assertSame($this->idsOf(['viewableUnderCannotView']), array_column($children, 'id'));
        $this->assertTrue($children[0]['canView']);
    }

    public function testApiReadChildrenEditUrlKeepsTheSearch(): void
    {
        $this->logInWithPermission('CMS_ACCESS_CMSMain');

        $parentPage = $this->findNode($this->getNodes('admin/pages/tree/children/0?q%5BTerm%5D=Parent'), 'parent');
        $this->assertSame('admin/pages/edit/show/' . $parentPage['id'] . '?q%5BTerm%5D=Parent', $parentPage['editUrl']);
    }

    public function testApiReadChildrenUpdateTreeNodeDataHookSeesThePageFields(): void
    {
        PageTreeController::add_extension(TreeNodeDataExtension::class);
        $this->logInWithPermission('CMS_ACCESS_CMSMain');

        $parentPage = $this->findNode($this->getNodes('admin/pages/tree/children/0'), 'parent');

        $this->assertSame('(Record type: Page) Parent', $parentPage['tooltipSeen']);
    }

    public function testApiReadChildrenClassesUseTheCMSMainOverrides(): void
    {
        $section = new class extends CMSMain {
            public function getTreeNodeClasses(DataObject $node): string
            {
                return parent::getTreeNodeClasses($node) . ' node-from-section';
            }
        };
        Injector::inst()->load([CMSMain::class => ['class' => get_class($section)]]);
        $this->logInWithPermission('CMS_ACCESS_CMSMain');

        $home = $this->findNode($this->getNodes('admin/pages/tree/children/0'), 'home');

        $this->assertContains('node-from-section', explode(' ', $home['classes']));
    }

    public function testApiReadChildrenCanDuplicateAtTheTopLevel(): void
    {
        $this->logInWithPermission('CMS_ACCESS_CMSMain');
        $config = SiteConfig::current_site_config();

        $parentPage = $this->findNode($this->getNodes('admin/pages/tree/children/0'), 'parent');
        $this->assertTrue($parentPage['canDuplicate']);

        $config->CanCreateTopLevelType = 'OnlyTheseUsers';
        $config->write();
        $parentPage = $this->findNode($this->getNodes('admin/pages/tree/children/0'), 'parent');
        $this->assertTrue($parentPage['canEdit']);
        $this->assertFalse($parentPage['canDuplicate']);
    }

    public function testApiReadChildrenCanDuplicateMatchesTheDuplicateActions(): void
    {
        $cannotEditPage = $this->objFromFixture(SiteTree::class, 'cannotEdit');
        $timelineID = (new SiteTree([
            'Title' => 'Timeline',
            'ParentID' => $cannotEditPage->ID,
            'CanEditType' => 'LoggedInUsers',
        ]))->write();
        $this->logInWithPermission('CMS_ACCESS_CMSMain');

        $nodes = $this->getNodes('admin/pages/tree/children/' . $cannotEditPage->ID);
        $timeline = array_column($nodes, null, 'id')[$timelineID];
        $this->assertTrue($timeline['canEdit']);
        $this->assertFalse($timeline['canDuplicate'], 'A page under a parent the member cannot edit');
        $this->assertSame($timeline, $this->decodeSuccess($this->ajaxGet('admin/pages/tree/' . $timelineID))['node']);
        $home = $this->findNode($this->getNodes('admin/pages/tree/children/0'), 'home');
        $this->assertTrue($home['canDuplicate']);

        foreach ([$timeline, $home] as $node) {
            foreach (['duplicate', 'duplicatewithchildren'] as $action) {
                $count = SiteTree::get()->count();
                $this->get("admin/pages/$action/" . $node['id']);
                $this->assertSame(
                    $node['canDuplicate'],
                    SiteTree::get()->count() > $count,
                    "$action {$node['treeTitle']}"
                );
            }
        }
    }

    public function testApiReadChildrenOfAPageTypeWithoutChildren(): void
    {
        $this->logInWithPermission('ADMIN');
        $menuTitlePage = $this->objFromFixture(SiteTree::class, 'menuTitle');
        (new NoChildrenPage(['Title' => 'No children', 'ParentID' => $menuTitlePage->ID]))->write();

        $noChildrenNode = $this->getNodes('admin/pages/tree/children/' . $menuTitlePage->ID)[0];
        $this->assertTrue($noChildrenNode['canEdit']);
        $this->assertFalse($noChildrenNode['canAddChildren']);
    }

    public function testApiReadChildrenTreeTitleIsPlainText(): void
    {
        $this->logInWithPermission('CMS_ACCESS_CMSMain');
        $title = '<b>Bold</b> "q" & \'a\' </script> &amp; a < b ünï';
        $home = $this->objFromFixture(SiteTree::class, 'home');
        (new SiteTree(['Title' => $title, 'ParentID' => $home->ID]))->write();

        $nodes = $this->getNodes('admin/pages/tree/children/' . $home->ID);
        $this->assertSame($title, $nodes[0]['treeTitle']);
        $this->assertSame('(Record type: Page) ' . $title, $nodes[0]['tooltip']);
    }

    public function testApiReadChildrenOfALiveOnlyPage(): void
    {
        $this->logInWithPermission('CMS_ACCESS_CMSMain');
        $liveOnly = $this->objFromFixture(SiteTree::class, 'limitedChild1');
        $liveOnly->publishSingle();
        $liveOnly->deleteFromStage(Versioned::DRAFT);

        $this->assertSame(404, $this->ajaxGet('admin/pages/tree/children/' . $liveOnly->ID)->getStatusCode());
    }

    public function testCanViewWithoutAccessToTheSection(): void
    {
        $this->logInWithPermission('CMS_ACCESS_AssetAdmin');
        $this->assertSame(403, $this->ajaxGet('admin/pages/tree/children/0')->getStatusCode());
    }

    public function testCanViewWhenLoggedOut(): void
    {
        $this->logOut();

        $response = $this->ajaxGet('admin/pages/tree/children/0');

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('1', $response->getHeader('X-Reauthenticate'));
    }

    public static function provideUrlSegmentLeavesTheOtherPagesRoutesAlone(): array
    {
        return [
            'pages section' => ['admin/pages', null],
            'legacy tree' => ['admin/pages/treeview', null],
            'edit form' => ['admin/pages/edit/show/', 'parent'],
        ];
    }

    #[DataProvider('provideUrlSegmentLeavesTheOtherPagesRoutesAlone')]
    public function testUrlSegmentLeavesTheOtherPagesRoutesAlone(string $url, ?string $fixtureName): void
    {
        $this->logInWithPermission('ADMIN');
        if ($fixtureName) {
            $url .= $this->idFromFixture(SiteTree::class, $fixtureName);
        }

        $response = $this->get($url);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringStartsWith('text/html', $response->getHeader('Content-Type'));
    }

    public function testCanViewUsesTheCMSMainAccessCheck(): void
    {
        $this->logInWithPermission('CMS_ACCESS_CMSMain');
        $this->assertSame(200, $this->ajaxGet('admin/pages/tree/children/0')->getStatusCode());

        CMSMain::add_extension(DenyAccessExtension::class);
        $this->assertSame(403, $this->ajaxGet('admin/pages/tree/children/0')->getStatusCode());
    }

    private function ajaxGet(string $url): HTTPResponse
    {
        return $this->get($url, null, ['X-Requested-With' => 'XMLHttpRequest']);
    }

    private function getNodes(string $url): array
    {
        return $this->decodeSuccess($this->ajaxGet($url))['nodes'];
    }

    private function decodeSuccess(HTTPResponse $response): array
    {
        if ($response->getStatusCode() !== 200) {
            $this->fail('Status ' . $response->getStatusCode() . ': ' . $response->getBody());
        }
        return json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function assertJsonError(int $code, HTTPResponse $response): void
    {
        $this->assertSame($code, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeader('Content-Type'));
        $body = json_decode($response->getBody(), true);
        $this->assertSame('error', $body['status']);
        $this->assertSame($code, $body['errors'][0]['code']);
    }

    private function idsOf(array $fixtureNames): array
    {
        return array_map(fn (string $name) => $this->idFromFixture(SiteTree::class, $name), $fixtureNames);
    }

    private function findNode(array $nodes, string $fixtureName): array
    {
        $id = $this->idFromFixture(SiteTree::class, $fixtureName);
        foreach ($nodes as $node) {
            if ($node['id'] === $id) {
                return $node;
            }
        }
        $this->fail("Node $fixtureName is not in the response");
    }

    private function words(array $node): array
    {
        return explode(' ', $node['classes']);
    }
}
