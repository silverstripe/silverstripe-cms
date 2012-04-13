<?php
class CMSSiteTreeFilterTest extends SapphireTest {

	static $fixture_file = 'CMSSiteTreeFilterTest.yml';
	
	function testSearchFilterEmpty() {
		$page1 = $this->objFromFixture('Page', 'page1');
		$page2 = $this->objFromFixture('Page', 'page2');
	
		$f = new CMSSiteTreeFilter_Search();
		$results = $f->pagesIncluded();
	
		$this->assertTrue($f->isPageIncluded($page1));
		$this->assertTrue($f->isPageIncluded($page2));
	}
	
	function testSearchFilterByTitle() {
		$page1 = $this->objFromFixture('Page', 'page1');
		$page2 = $this->objFromFixture('Page', 'page2');
	
		$f = new CMSSiteTreeFilter_Search(array('Title' => 'Page 1'));
		$results = $f->pagesIncluded();
	
		$this->assertTrue($f->isPageIncluded($page1));
		$this->assertFalse($f->isPageIncluded($page2));
		$this->assertEquals(1, count($results));
		$this->assertEquals(
			array('ID' => $page1->ID, 'ParentID' => 0),
			$results[0]
		);
	}
	
	function testIncludesParentsForNestedMatches() {
		$parent = $this->objFromFixture('Page', 'page3');
		$child = $this->objFromFixture('Page', 'page3b');
	
		$f = new CMSSiteTreeFilter_Search(array('Title' => 'Page 3b'));
		$results = $f->pagesIncluded();
	
		$this->assertTrue($f->isPageIncluded($parent));
		$this->assertTrue($f->isPageIncluded($child));
		$this->assertEquals(1, count($results));
		$this->assertEquals(
			array('ID' => $child->ID, 'ParentID' => $parent->ID),
			$results[0]
		);
	}
	
	function testChangedPagesFilter() {
		$unchangedPage = $this->objFromFixture('Page', 'page1');
		$unchangedPage->doPublish();
	
		$changedPage = $this->objFromFixture('Page', 'page2');
		$changedPage->MetaTitle = 'Original';
		$changedPage->publish('Stage', 'Live');
		$changedPage->MetaTitle = 'Changed';
		$changedPage->write();
	
		$f = new CMSSiteTreeFilter_ChangedPages();
		$results = $f->pagesIncluded();
	
		$this->assertTrue($f->isPageIncluded($changedPage));
		$this->assertFalse($f->isPageIncluded($unchangedPage));
		$this->assertEquals(1, count($results));
		$this->assertEquals(
			array('ID' => $changedPage->ID, 'ParentID' => 0),
			$results[0]
		);
	}
	
	function testDeletedPagesFilter() {
		$deletedPage = $this->objFromFixture('Page', 'page2');
		$deletedPage->publish('Stage', 'Live');
		$deletedPageID = $deletedPage->ID;
		$deletedPage->delete();
		$deletedPage = Versioned::get_one_by_stage(
			'SiteTree', 
			'Live', 
			sprintf('"SiteTree_Live"."ID" = %d', $deletedPageID)
		);

		$f = new CMSSiteTreeFilter_DeletedPages();
		$results = $f->pagesIncluded();

		$this->assertTrue($f->isPageIncluded($deletedPage));
	}
}
