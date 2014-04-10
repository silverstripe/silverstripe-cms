<?php
class CMSSiteTreeFilterTest extends SapphireTest {

	protected static $fixture_file = 'CMSSiteTreeFilterTest.yml';
	
	public function testSearchFilterEmpty() {
		$page1 = $this->objFromFixture('Page', 'page1');
		$page2 = $this->objFromFixture('Page', 'page2');
	
		$f = new CMSSiteTreeFilter_Search();
		$results = $f->pagesIncluded();
	
		$this->assertTrue($f->isPageIncluded($page1));
		$this->assertTrue($f->isPageIncluded($page2));
	}
	
	public function testSearchFilterByTitle() {
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
	
	public function testIncludesParentsForNestedMatches() {
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
	
	public function testChangedPagesFilter() {
		$unchangedPage = $this->objFromFixture('Page', 'page1');
		$unchangedPage->doPublish();
	
		$changedPage = $this->objFromFixture('Page', 'page2');
		$changedPage->Title = 'Original';
		$changedPage->publish('Stage', 'Live');
		$changedPage->Title = 'Changed';
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
	
	public function testDeletedPagesFilter() {
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
	
	public function testStatusDraftPagesFilter() {
		$draftPage = $this->objFromFixture('Page', 'page4');
		$draftPage->publish('Stage', 'Stage');
		$draftPage = Versioned::get_one_by_stage(
			'SiteTree', 
			'Stage', 
			sprintf('"SiteTree"."ID" = %d', $draftPage->ID)
		);

		$f = new CMSSiteTreeFilter_StatusDraftPages();
		$f->pagesIncluded();

		$this->assertTrue($f->isPageIncluded($draftPage));
		
		// Ensures empty array returned if no data to show
		$draftPage->delete();
		$this->assertEmpty($f->isPageIncluded($draftPage));		
	}			
	
	public function testStatusRemovedFromDraftFilter() {
		$removedDraftPage = $this->objFromFixture('Page', 'page6');
		$removedDraftPage->doPublish();
		$removedDraftPage->deleteFromStage('Stage');
		$removedDraftPage = Versioned::get_one_by_stage(
			'SiteTree', 
			'Live', 
			sprintf('"SiteTree"."ID" = %d', $removedDraftPage->ID)
		);

		$f = new CMSSiteTreeFilter_StatusRemovedFromDraftPages();
		$f->pagesIncluded();

		$this->assertTrue($f->isPageIncluded($removedDraftPage));
		
		// Ensures empty array returned if no data to show
		$removedDraftPage->delete();
		$this->assertEmpty($f->isPageIncluded($removedDraftPage));
	}
	
	public function testStatusDeletedFilter() {
		$deletedPage = $this->objFromFixture('Page', 'page7');
		$deletedPage->publish('Stage', 'Live');
		$deletedPageID = $deletedPage->ID;
		
		// Can't use straight $blah->delete() as that blows it away completely and test fails
		$deletedPage->deleteFromStage('Live');
		$deletedPage->deleteFromStage('Draft');	
		
		/*
		 * Pretty funky way to get the data out. But none of the Versioned::get_xx() methods
		 * worked out of the box, not even get_including_deleted() which the logic under test actually uses!
		 */
		$checkParentExists = null;
		Versioned::get_including_deleted('SiteTree')->each(function($item) use($deletedPageID, &$checkParentExists) {
			if($item->ID == $deletedPageID) {
				$checkParentExists = $item;
			}
		});

		$f = new CMSSiteTreeFilter_StatusDeletedPages();
		$f->pagesIncluded();

		$this->assertTrue($f->isPageIncluded($checkParentExists));
	}
}
