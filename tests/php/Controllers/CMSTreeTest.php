<?php

namespace SilverStripe\CMS\Tests\Controllers;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\ORM\DataObject;

/**
 * Tests for tree-operations refactored out of LeftAndMain
 */
class CMSTreeTest extends FunctionalTest
{
    protected static $fixture_file = 'CMSTreeTest.yml';

    public function testSaveTreeNodeSorting()
    {
        $this->logInWithPermission('ADMIN');

        // forcing sorting for non-MySQL
        $rootPages = SiteTree::get()
            ->filter("ParentID", 0)
            ->sort('"ID"');
        $siblingIDs = $rootPages->column('ID');
        $page1 = $rootPages->offsetGet(0);
        $page2 = $rootPages->offsetGet(1);
        $page3 = $rootPages->offsetGet(2);

        // Move page2 before page1
        $siblingIDs[0] = $page2->ID;
        $siblingIDs[1] = $page1->ID;
        $data = array(
            'SiblingIDs' => $siblingIDs,
            'ID' => $page2->ID,
            'ParentID' => 0
        );

        $response = $this->post('admin/pages/edit/savetreenode', $data);
        $this->assertEquals(200, $response->getStatusCode());
        /** @var SiteTree $page1 */
        $page1 = SiteTree::get()->byID($page1->ID);
        /** @var SiteTree $page2 */
        $page2 = SiteTree::get()->byID($page2->ID);
        /** @var SiteTree $page3 */
        $page3 = SiteTree::get()->byID($page3->ID);

        $this->assertEquals(2, $page1->Sort, 'Page1 is sorted after Page2');
        $this->assertEquals(1, $page2->Sort, 'Page2 is sorted before Page1');
        $this->assertEquals(3, $page3->Sort, 'Sort order for other pages is unaffected');
    }

    public function testSaveTreeNodeParentID()
    {
        $this->logInWithPermission('ADMIN');

        $page2 = $this->objFromFixture(SiteTree::class, 'page2');
        $page3 = $this->objFromFixture(SiteTree::class, 'page3');
        $page31 = $this->objFromFixture(SiteTree::class, 'page31');
        $page32 = $this->objFromFixture(SiteTree::class, 'page32');

        // Move page2 into page3, between page3.1 and page 3.2
        $siblingIDs = array(
            $page31->ID,
            $page2->ID,
            $page32->ID
        );
        $data = array(
            'SiblingIDs' => $siblingIDs,
            'ID' => $page2->ID,
            'ParentID' => $page3->ID
        );
        $response = $this->post('admin/pages/edit/savetreenode', $data);
        $this->assertEquals(200, $response->getStatusCode());
        $page2 = DataObject::get_by_id(SiteTree::class, $page2->ID, false);
        $page31 = DataObject::get_by_id(SiteTree::class, $page31->ID, false);
        $page32 = DataObject::get_by_id(SiteTree::class, $page32->ID, false);

        $this->assertEquals($page3->ID, $page2->ParentID, 'Moved page gets new parent');
        $this->assertEquals(1, $page31->Sort, 'Children pages before insertaion are unaffected');
        $this->assertEquals(2, $page2->Sort, 'Moved page is correctly sorted');
        $this->assertEquals(3, $page32->Sort, 'Children pages after insertion are resorted');
    }


    /**
     * Test {@see CMSMain::updatetreenodes}
     */
    public function testUpdateTreeNodes()
    {
        $page1 = $this->objFromFixture(SiteTree::class, 'page1');
        $page2 = $this->objFromFixture(SiteTree::class, 'page2');
        $page3 = $this->objFromFixture(SiteTree::class, 'page3');
        $page31 = $this->objFromFixture(SiteTree::class, 'page31');
        $page32 = $this->objFromFixture(SiteTree::class, 'page32');
        $this->logInWithPermission('ADMIN');

        // Check page
        $result = $this->get('admin/pages/edit/updatetreenodes?ids='.$page1->ID);
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('application/json', $result->getHeader('Content-Type'));
        $data = json_decode($result->getBody(), true);
        $pageData = $data[$page1->ID];
        $this->assertEquals(0, $pageData['ParentID']);
        $this->assertEquals($page2->ID, $pageData['NextID']);
        $this->assertEmpty($pageData['PrevID']);

        // check subpage
        $result = $this->get('admin/pages/edit/updatetreenodes?ids='.$page31->ID);
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('application/json', $result->getHeader('Content-Type'));
        $data = json_decode($result->getBody(), true);
        $pageData = $data[$page31->ID];
        $this->assertEquals($page3->ID, $pageData['ParentID']);
        $this->assertEquals($page32->ID, $pageData['NextID']);
        $this->assertEmpty($pageData['PrevID']);

        // Multiple pages
        $result = $this->get('admin/pages/edit/updatetreenodes?ids='.$page1->ID.','.$page2->ID);
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('application/json', $result->getHeader('Content-Type'));
        $data = json_decode($result->getBody(), true);
        $this->assertEquals(2, count($data));

        // Invalid IDs
        $result = $this->get('admin/pages/edit/updatetreenodes?ids=-3');
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('application/json', $result->getHeader('Content-Type'));
        $data = json_decode($result->getBody(), true);
        $this->assertEquals(0, count($data));
    }
}
