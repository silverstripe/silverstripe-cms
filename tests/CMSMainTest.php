<?php

class CMSMainTest extends FunctionalTest {
	static $fixture_file = 'cms/tests/CMSMainTest.yml';
	
	/**
	 * @todo Test the results of a publication better
	 */
	function testPublish() {
		$session = new Session(array(
			'loggedInAs' => $this->idFromFixture('Member', 'admin')
		));
		
		$response = Director::test("admin/cms/publishall", array('confirm' => 1), $session);
		$this->assertContains(
			sprintf(_t('CMSMain.PUBPAGES',"Done: Published %d pages"), 5), 
			$response->getBody()
		);

		$response = Director::test("admin/cms/publishitems", array('csvIDs' => '1,2', 'ajax' => 1), $session);
		$this->assertContains('setNodeTitle(1, \'Page 1\');', $response->getBody());
		$this->assertContains('setNodeTitle(2, \'Page 2\');', $response->getBody());
		
		
		
		//$this->assertRegexp('/Done: Published 4 pages/', $response->getBody())
			
		/*
		$response = Director::test("admin/publishitems", array(
			'ID' => ''
			'Title' => ''
			'action_publish' => 'Save and publish',
		), $session);
		$this->assertRegexp('/Done: Published 4 pages/', $response->getBody())
		*/
	}
	
	/**
	 * Test publication of one of every page type
	 */
	function testPublishOneOfEachKindOfPage() {
		return;
		$classes = ClassInfo::subclassesFor("SiteTree");
		array_shift($classes);
		unset($classes['GhostPage']); //Ghost Pages aren't used anymore

		foreach($classes as $class) {
			$page = new $class();
			if($class instanceof TestOnly) continue;
			
			$page->Title = "Test $class page";
			
			$page->write();
			$this->assertEquals("Test $class page", DB::query("SELECT Title FROM SiteTree WHERE ID = $page->ID")->value());
			
			$page->doPublish();
			$this->assertEquals("Test $class page", DB::query("SELECT Title FROM SiteTree_Live WHERE ID = $page->ID")->value());
			
			// Check that you can visit the page
			Director::test($page->Link());
		}
	}

	/**
	 * Test that getCMSFields works on each page type.
	 * Mostly, this is just checking that the method doesn't return an error
	 */
	function testThatGetCMSFieldsWorksOnEveryPageType() {
		$classes = ClassInfo::subclassesFor("SiteTree");
		array_shift($classes);
		unset($classes['GhostPage']); //Ghost Pages aren't used anymore

		foreach($classes as $class) {
			$page = new $class();
			if($page instanceof TestOnly) continue;

			$page->Title = "Test $class page";
			$page->write();
			$page->flushCache();
			$page = DataObject::get_by_id("SiteTree", $page->ID);
			
			$this->assertTrue($page->getCMSFields(null) instanceof FieldSet);
		}
	}	

	/**
	 * Test that a draft-deleted page can still be opened in the CMS
	 */
	function testDraftDeletedPageCanBeOpenedInCMS() {
		// Set up a page that is delete from live
		$page = $this->objFromFixture('Page','page1');
		$pageID = $page->ID;
		$page->doPublish();
		$page->delete();
		
		$this->session()->inst_set('loggedInAs', $this->idFromFixture('Member', 'admin'));
		$response = $this->get('admin/getitem?ID=' . $pageID . '&ajax=1');

		// Check that the 'delete from live' button exists as a simple way of checking that the correct page is returned.
		$this->assertRegExp('/<input[^>]+type="submit"[^>]+name="action_deletefromlive"/i', $response->getBody());
	}
	
	/**
	 * Test CMSMain::getRecord()
	 */
	function testGetRecord() {
		// Set up a page that is delete from live
		$page1 = $this->objFromFixture('Page','page1');
		$page1ID = $page1->ID;
		$page1->doPublish();
		$page1->delete();
		
		$cmsMain = new CMSMain();

		// Bad calls
		$this->assertNull($cmsMain->getRecord('0'));
		$this->assertNull($cmsMain->getRecord('asdf'));
		
		// Pages that are on draft and aren't on draft should both work
		$this->assertType('Page', $cmsMain->getRecord($page1ID));
		$this->assertType('Page', $cmsMain->getRecord($this->idFromFixture('Page','page2')));

		// This functionality isn't actually used any more.
		$newPage = $cmsMain->getRecord('new-Page-5');
		$this->assertType('Page', $newPage);
		$this->assertEquals('5', $newPage->ParentID);

	}
}