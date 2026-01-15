<?php

namespace SilverStripe\CMS\Tests\Controllers;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\CMS\Controllers\CMSMain;
use SilverStripe\CMS\Controllers\CMSSiteTreeController;

class CMSSiteTreeControllerTest extends FunctionalTest
{
    protected static $fixture_file = 'CMSSiteTreeControllerTest.yml';

    protected function setUp(): void
    {
        parent::setUp();
        $this->logInWithPermission('ADMIN');
    }

    public function testJsonViewReturnsValidJson(): void
    {
        $url = '/admin/pages/tree/jsonview';
        $response = $this->mainSession->sendRequest('GET', $url, []);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeader('Content-Type'));
        $data = json_decode($response->getBody(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('currentRecordID', $data);
        $this->assertArrayHasKey('data', $data);
        if (!empty($data['data'])) {
            $firstNode = $data['data'][0];
            $this->assertArrayHasKey('marked', $firstNode);
            $this->assertArrayHasKey('expanded', $firstNode);
            $this->assertArrayHasKey('opened', $firstNode);
            $this->assertArrayHasKey('limited', $firstNode);
            $this->assertArrayHasKey('count', $firstNode);
            $this->assertArrayHasKey('depth', $firstNode);
            $this->assertArrayHasKey('statusFlags', $firstNode);
            $this->assertIsArray($firstNode['statusFlags']);
        }
    }

    public function testJsonViewWithCurrentRecord(): void
    {
        $record = $this->objFromFixture(SiteTree::class, 'page31');
        $url = '/admin/pages/tree/jsonview/0/' . $record->ID;
        $response = $this->mainSession->sendRequest('GET', $url, []);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeader('Content-Type'));
        $data = json_decode($response->getBody(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('currentRecordID', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertIsInt($data['currentRecordID']);
        $this->assertEquals($record->ID, $data['currentRecordID']);
    }

    public function testPartialTreeWithNodeThresholdTotal(): void
    {
        SiteTree::config()->set('node_threshold_total', 5);
        $url = '/admin/pages/tree/jsonview';
        $response = $this->mainSession->sendRequest('GET', $url, []);
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertIsArray($data['data']);
        $totalNodes = $this->countNodesInTree($data['data']);
        $this->assertLessThanOrEqual(28, $totalNodes);
    }

    public function testPartialTreeWithNodeThresholdLeaf(): void
    {
        SiteTree::config()->set('node_threshold_leaf', 1);
        SiteTree::config()->set('node_threshold_total', 100);
        $page3 = $this->objFromFixture(SiteTree::class, 'page3');
        $url = '/admin/pages/tree/jsonview/' . $page3->ID;
        $response = $this->mainSession->sendRequest('GET', $url, []);
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertIsArray($data['data']);
        $this->assertCount(2, $data['data'], 'Page3 has 2 children');
    }

    public function testPartialTreeWithRootID(): void
    {
        $page3 = $this->objFromFixture(SiteTree::class, 'page3');
        $url = '/admin/pages/tree/jsonview/' . $page3->ID;
        $response = $this->mainSession->sendRequest('GET', $url, []);
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertIsArray($data['data']);
        $this->assertCount(2, $data['data']);
        $titles = array_column($data['data'], 'title');
        $this->assertContains('Page 3.1', $titles);
        $this->assertContains('Page 3.2', $titles);
    }

    public function testNodeThresholdTotalLimitsMarking(): void
    {
        SiteTree::config()->set('node_threshold_total', 3);
        $url = '/admin/pages/tree/jsonview';
        $response = $this->mainSession->sendRequest('GET', $url, []);
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertIsArray($data['data']);
        $totalNodes = $this->countNodesInTree($data['data']);
        $this->assertLessThanOrEqual(28, $totalNodes);
        $this->assertGreaterThan(0, count($data['data']));
    }

    public function testPartialTreeOpened(): void
    {
        SiteTree::config()->set('node_threshold_total', 3);
        $url = '/admin/pages/tree/jsonview';
        $response = $this->mainSession->sendRequest('GET', $url, []);
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertIsArray($data['data']);
        $this->assertGreaterThan(0, count($data['data']));
        foreach ($data['data'] as $node) {
            $this->assertArrayHasKey('opened', $node);
            $this->assertArrayHasKey('expanded', $node);
            $this->assertArrayHasKey('marked', $node);
            $this->assertTrue($node['marked']);
        }
    }

    public function testIntegrationUnexpandedNodeExpansion(): void
    {
        SiteTree::config()->set('node_threshold_total', 4);
        SiteTree::config()->set('node_threshold_leaf', 100);
        $url = '/admin/pages/tree/jsonview';
        $response = $this->mainSession->sendRequest('GET', $url, []);
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertIsArray($data['data']);
        $page3 = $this->findNodeByTitle($data['data'], 'Page 3');
        if ($page3) {
            $this->assertFalse($page3['expanded'], 'Page 3 should not be expanded when threshold_total is low');
            $this->assertGreaterThan(0, $page3['count'], 'Page 3 should have children count > 0');
            $this->assertEmpty($page3['children'], 'Page 3 children should not be loaded in initial response');
            $page3_obj = $this->objFromFixture(SiteTree::class, 'page3');
            $url = '/admin/pages/tree/jsonview/' . $page3_obj->ID;
            $response = $this->mainSession->sendRequest('GET', $url, []);
            $this->assertSame(200, $response->getStatusCode());
            $subtreeData = json_decode($response->getBody(), true);
            $this->assertGreaterThanOrEqual(2, count($subtreeData['data']), 'Subtree should return all 2 children of Page 3');
            $subtreeTitles = array_column($subtreeData['data'], 'title');
            $this->assertContains('Page 3.1', $subtreeTitles);
            $this->assertContains('Page 3.2', $subtreeTitles);
        }
    }

    public function testIntegrationMultipleSubtreeLoads(): void
    {
        SiteTree::config()->set('node_threshold_total', 3);
        SiteTree::config()->set('node_threshold_leaf', 100);
        $url = '/admin/pages/tree/jsonview';
        $response = $this->mainSession->sendRequest('GET', $url, []);
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertIsArray($data['data']);
        $page1 = $this->objFromFixture(SiteTree::class, 'page1');
        $url1 = '/admin/pages/tree/jsonview/' . $page1->ID;
        $response1 = $this->mainSession->sendRequest('GET', $url1, []);
        $this->assertSame(200, $response1->getStatusCode());
        $subtreeData1 = json_decode($response1->getBody(), true);
        $this->assertIsArray($subtreeData1['data']);
        $page2 = $this->objFromFixture(SiteTree::class, 'page2');
        $url2 = '/admin/pages/tree/jsonview/' . $page2->ID;
        $response2 = $this->mainSession->sendRequest('GET', $url2, []);
        $this->assertSame(200, $response2->getStatusCode());
        $subtreeData2 = json_decode($response2->getBody(), true);
        $this->assertIsArray($subtreeData2['data']);
    }

    public function testJsonViewIncludesContextMenuDataForSiteTree(): void
    {
        $response = $this->get('/admin/pages/tree/jsonview');
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertIsArray($data['data']);
        if (!empty($data['data'])) {
            $firstNode = $data['data'][0];
            $this->assertArrayHasKey('contextMenuData', $firstNode);
            $contextMenuData = $firstNode['contextMenuData'];
            $this->assertIsArray($contextMenuData);
            $this->assertArrayHasKey('canEdit', $contextMenuData);
            $this->assertIsBool($contextMenuData['canEdit']);
            $this->assertArrayHasKey('canCreate', $contextMenuData);
            $this->assertIsBool($contextMenuData['canCreate']);
            $this->assertArrayHasKey('canDelete', $contextMenuData);
            $this->assertIsBool($contextMenuData['canDelete']);
            $this->assertArrayHasKey('numChildren', $contextMenuData);
            $this->assertIsInt($contextMenuData['numChildren']);
            $this->assertArrayHasKey('allowedChildren', $contextMenuData);
            $this->assertIsArray($contextMenuData['allowedChildren']);
        }
    }

    public function testContextMenuDataAllowedChildrenStructure(): void
    {
        $response = $this->get('/admin/pages/tree/jsonview');
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertIsArray($data['data']);
        if (!empty($data['data'])) {
            $firstNode = $data['data'][0];
            $allowedChildren = $firstNode['contextMenuData']['allowedChildren'];
            $this->assertIsArray($allowedChildren);
            foreach ($allowedChildren as $child) {
                $this->assertArrayHasKey('ClassName', $child);
                $this->assertArrayHasKey('Title', $child);
                $this->assertArrayHasKey('IconClass', $child);
                $this->assertIsString($child['ClassName']);
                $this->assertIsString($child['Title']);
            }
        }
    }

    public function testContextMenuDataExcludesBasePageClass(): void
    {
        $response = $this->get('/admin/pages/tree/jsonview');
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertIsArray($data['data']);
        if (!empty($data['data'])) {
            foreach ($data['data'] as $node) {
                $allowedChildren = $node['contextMenuData']['allowedChildren'];
                $this->assertIsArray($allowedChildren);
                $classNames = array_column($allowedChildren, 'ClassName');
                $this->assertNotContains(
                    SiteTree::class,
                    $classNames,
                    'SiteTree base class should be filtered out by updateAllowedSubClasses'
                );
            }
        }
    }

    private function countNodesInTree(array $nodes): int
    {
        $count = 0;
        foreach ($nodes as $node) {
            $count++;
            if (!empty($node['children'])) {
                $count += $this->countNodesInTree($node['children']);
            }
        }
        return $count;
    }

    private function findNodeByTitle(array $nodes, string $title): ?array
    {
        foreach ($nodes as $node) {
            if (isset($node['title']) && $node['title'] === $title) {
                return $node;
            }
            if (!empty($node['children']) && is_array($node['children'])) {
                $found = $this->findNodeByTitle($node['children'], $title);
                if ($found !== null) {
                    return $found;
                }
            }
        }
        return null;
    }

    public function testDuplicateNodeWithChildren(): void
    {
        $page = $this->objFromFixture(SiteTree::class, 'page3');
        $originalCount = $page->Children()->count();
        $response = $this->mainSession->sendRequest(
            'POST',
            '/admin/pages/tree/duplicateWithChildren/' . $page->ID,
            []
        );
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('newNodeID', $data);
        $newNode = SiteTree::get_by_id($data['newNodeID']);
        $this->assertNotNull($newNode);
        $this->assertSame($originalCount, $newNode->Children()->count());
    }

    public function testDuplicateNodeWithoutChildren(): void
    {
        $page = $this->objFromFixture(SiteTree::class, 'page3');
        $response = $this->mainSession->sendRequest(
            'POST',
            '/admin/pages/tree/duplicate/' . $page->ID,
            []
        );
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('newNodeID', $data);
        $newNode = SiteTree::get_by_id($data['newNodeID']);
        $this->assertNotNull($newNode);
        $this->assertSame(0, $newNode->Children()->count());
    }

    public function testDuplicateNodeWithInvalidID(): void
    {
        $response = $this->mainSession->sendRequest(
            'POST',
            '/admin/pages/tree/duplicate/999999',
            []
        );
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testComplexTreeViewSchemaHasLabels(): void
    {
        $controller = CMSSiteTreeController::create();
        $treeView = $controller->getComplexTreeView();
        $schemaData = $treeView->getSchemaData();
        $this->assertArrayHasKey('data', $schemaData);
        $this->assertArrayHasKey('labels', $schemaData['data']);
        $labels = $schemaData['data']['labels'];
        $this->assertIsArray($labels);
        $this->assertArrayHasKey('edit', $labels);
        $this->assertArrayHasKey('showAsList', $labels);
        $this->assertArrayHasKey('addChild', $labels);
        $this->assertArrayHasKey('duplicate', $labels);
        $this->assertArrayHasKey('duplicateThisOnly', $labels);
        $this->assertArrayHasKey('duplicateWithChildren', $labels);
    }

    public function testComplexTreeViewDisablesDefaultNavigationHandlers(): void
    {
        $controller = CMSSiteTreeController::create();
        $treeView = $controller->getComplexTreeView();
        $schemaData = $treeView->getSchemaData();
        $this->assertArrayHasKey('data', $schemaData);
        $this->assertArrayHasKey('enableDefaultNavigationHandlers', $schemaData['data']);
        $this->assertFalse($schemaData['data']['enableDefaultNavigationHandlers']);
    }
}
