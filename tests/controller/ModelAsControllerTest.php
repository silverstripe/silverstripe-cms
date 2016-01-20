<?php
/**
 * @package cms
 * @subpackage tests
 */
class ModelAsControllerTest extends FunctionalTest {
	
	protected $usesDatabase = true;
	
	protected static $fixture_file = 'ModelAsControllerTest.yml';
	
	protected $autoFollowRedirection = false;

	protected $orig = array();

	/**
	 * New tests require nested urls to be enabled, but the site might not
	 * support nested URLs.
	 * This setup will enable nested-urls for this test and resets the state
	 * after the tests have been performed.
	 */
	public function setUp() {
		parent::setUp();

		$this->orig['nested_urls'] = SiteTree::config()->nested_urls;
		Config::inst()->update('SiteTree', 'nested_urls', true);
	}

	/**
	 * New tests require nested urls to be enabled, but the site might not
	 * support nested URLs.
	 * This setup will enable nested-urls for this test and resets the state
	 * after the tests have been performed.
	 */
	public function tearDown() {
		
		if (isset($this->orig['nested_urls']) && !$this->orig['nested_urls']) {
			SiteTree::config()->nested_urls = false;
		}
		parent::tearDown();		
	}


	protected function generateNestedPagesFixture() {
		$level1 = new Page();
		$level1->Title      = 'First Level';
		$level1->URLSegment = 'level1';
		$level1->write();
		$level1->publish('Stage', 'Live');
		
		$level1->URLSegment = 'newlevel1';
		$level1->write();
		$level1->publish('Stage', 'Live');
		
		$level2 = new Page();
		$level2->Title      = 'Second Level';
		$level2->URLSegment = 'level2';
		$level2->ParentID = $level1->ID;
		$level2->write();
		$level2->publish('Stage', 'Live');
		
		$level2->URLSegment = 'newlevel2';
		$level2->write();
		$level2->publish('Stage', 'Live');
		
		$level3 = New Page();
		$level3->Title = "Level 3";
		$level3->URLSegment = 'level3';
		$level3->ParentID = $level2->ID;
		$level3->write();
		$level3->publish('Stage','Live');
						
		$level3->URLSegment = 'newlevel3';
		$level3->write();
		$level3->publish('Stage','Live');
	}
	
	/**
	 * We're building up a page hierarchy ("nested URLs") and rename
	 * all the individual pages afterwards. The assumption is that
	 * all pages will be found by their old segments.
	 *
	 * NOTE: This test requires nested_urls
	 *
	 * Original: level1/level2/level3
	 * Republished as: newlevel1/newlevel2/newlevel3
	 */
	public function testRedirectsNestedRenamedPages(){
		$this->generateNestedPagesFixture();
		
		// check a first level URLSegment
		$response = $this->get('level1/action');
		$this->assertEquals($response->getStatusCode(),301);
		$this->assertEquals(
			Controller::join_links(Director::baseURL() . 'newlevel1/action'),
			$response->getHeader('Location')
		);
		
		// check second level URLSegment
		$response = $this->get('newlevel1/level2');
		$this->assertEquals($response->getStatusCode(),301 );
		$this->assertEquals(
			Controller::join_links(Director::baseURL() . 'newlevel1/newlevel2/'),
			$response->getHeader('Location')
		);
		
		// check third level URLSegment
		$response = $this->get('newlevel1/newlevel2/level3');
		$this->assertEquals($response->getStatusCode(), 301);
		$this->assertEquals(
			Controller::join_links(Director::baseURL() . 'newlevel1/newlevel2/newlevel3/'),
			$response->getHeader('Location')
		);
		
		$response = $this->get('newlevel1/newlevel2/level3');
	}

	/**
	 * Test that the redirect works even with a lot of nested pages
	 * Original: /oldurl/level2/level3/level4/level5
	 * New: /newurl/level2/level3/level4/level5
	 */
	public function testHeavilyNestedRenamedRedirectedPages() {
		$page = new Page();
		$page->Title      = 'First Level';
		$page->URLSegment = 'oldurl';
		$page->write();
		$page->publish('Stage', 'Live');
		
		$page->URLSegment = 'newurl';
		$page->write();
		$page->publish('Stage', 'Live');

		$page2 = new Page();
		$page2->Title      = 'Second Level Page';
		$page2->URLSegment = 'level2';
		$page2->ParentID = $page->ID;
		$page2->write();
		$page2->publish('Stage', 'Live');
		
		$page3 = new Page();
		$page3->Title      = 'Third Level Page';
		$page3->URLSegment = 'level3';
		$page3->ParentID = $page2->ID;
		$page3->write();
		$page3->publish('Stage', 'Live');
	
		$page4 = new Page();
		$page4->Title      = 'Fourth Level Page';
		$page4->URLSegment = 'level4';
		$page4->ParentID = $page3->ID;
		$page4->write();
		$page4->publish('Stage', 'Live');
		
		$page5 = new Page();
		$page5->Title      = 'Fifth Level Page';
		$page5->URLSegment = 'level5';
		$page5->ParentID = $page4->ID;
		$page5->write();
		$page5->publish('Stage', 'Live');

		// Test that the redirect still works fine when trying to access the most nested page
		$response = $this->get('oldurl/level2/level3/level4/level5/');
		$this->assertEquals($response->getStatusCode(), 301);
		$this->assertEquals(
			Controller::join_links(Director::baseURL() . 'newurl/level2/level3/level4/level5/'),
			$response->getHeader('Location')
		);
	}


	public function testRedirectionForPreNestedurlsBookmarks(){
		$this->generateNestedPagesFixture();

		// Up-to-date URLs will be redirected to the appropriate subdirectory
		$response = $this->get('newlevel3');
		$this->assertEquals(301, $response->getStatusCode());
		$this->assertEquals(Director::baseURL() . 'newlevel1/newlevel2/newlevel3/',
			$response->getHeader("Location"));

		// So will the legacy ones
		$response = $this->get('level3');
		$this->assertEquals(301, $response->getStatusCode());
		$this->assertEquals(Director::baseURL() . 'newlevel1/newlevel2/newlevel3/',
			$response->getHeader("Location"));
	}

	public function testDoesntRedirectToNestedChildrenOutsideOfOwnHierarchy() {
		$this->generateNestedPagesFixture();
		
		$otherParent = new Page(array(
			'URLSegment' => 'otherparent'
		));
		$otherParent->write();
		$otherParent->publish('Stage', 'Live');
		
		$response = $this->get('level1/otherparent');
		$this->assertEquals($response->getStatusCode(), 301);

		$response = $this->get('newlevel1/otherparent');
		$this->assertEquals(
			$response->getStatusCode(),
			404,
			'Requesting an unrelated page on a renamed parent should be interpreted as a missing action, not a redirect'
		);
	}
	
	/**
	 *
	 * NOTE: This test requires nested_urls
	 *
	 */
	public function testRedirectsNestedRenamedPagesWithGetParameters() {
		$this->generateNestedPagesFixture();
		
		// check third level URLSegment
		$response = $this->get('newlevel1/newlevel2/level3/?foo=bar&test=test');
		$this->assertEquals($response->getStatusCode(), 301);
		$this->assertEquals(
			Controller::join_links(Director::baseURL() . 'newlevel1/newlevel2/newlevel3/', '?foo=bar&test=test'),
			$response->getHeader('Location')
		);
	}
	
	/**
	 *
	 * NOTE: This test requires nested_urls
	 *
	 */
	public function testDoesntRedirectToNestedRenamedPageWhenNewExists() {
		$this->generateNestedPagesFixture();
		
		$otherLevel1 = new Page(array(
			'Title' => "Other Level 1",
			'URLSegment' => 'level1'
		));
		$otherLevel1->write();
		$otherLevel1->publish('Stage', 'Live');
		
		$response = $this->get('level1');
		$this->assertEquals(
			$response->getStatusCode(),
			200
		);
		
		$response = $this->get('level1/newlevel2');
		$this->assertEquals(
			$response->getStatusCode(),
			404,
			'The old newlevel2/ URLSegment is checked as an action on the new page, which shouldnt exist.'
		);
	}
	
	/**
	 *
	 * NOTE: This test requires nested_urls
	 *
	 */
	public function testFindOldPage(){
		$page = new Page();
		$page->Title      = 'First Level';
		$page->URLSegment = 'oldurl';
		$page->write();
		$page->publish('Stage', 'Live');
		
		$page->URLSegment = 'newurl';
		$page->write();
		$page->publish('Stage', 'Live');
		
		$url = OldPageRedirector::find_old_page('oldurl');
		$matchedPage = SiteTree::get_by_link($url);
		$this->assertEquals('First Level',$matchedPage->Title);
		
		$page2 = new Page();
		$page2->Title      = 'Second Level Page';
		$page2->URLSegment = 'oldpage2';
		$page2->ParentID = $page->ID;
		$page2->write();
		$page2->publish('Stage', 'Live');
		
		$page2->URLSegment = 'newpage2';
		$page2->write();
		$page2->publish('Stage', 'Live');
		
		$url = OldPageRedirector::find_old_page('oldpage2',$page2->ParentID);
		$matchedPage = SiteTree::get_by_link($url);
		$this->assertEquals('Second Level Page',$matchedPage->Title);
		
		$url = OldPageRedirector::find_old_page('oldpage2',$page2->ID);
		$matchedPage = SiteTree::get_by_link($url);
		$this->assertEquals(false, $matchedPage);
	}

	/**
	 * go to a page that's been published but is child of an unpublished page
	 *
	 * NOTE: This test requires nested_urls
	 */
	public function testChildOfDraft() {
		RootURLController::reset();
		Config::inst()->update('SiteTree', 'nested_urls', true);

		$draft = new Page();
		$draft->Title = 'Root Leve Draft Page';
		$draft->URLSegment = 'root';
		$draft->write();

		$published = new Page();
		$published->Title = 'Published Page Under Draft Page';
		$published->URLSegment = 'sub-root';
		$published->write();
		$published->publish('Stage', 'Live');
		$response = $this->get('root/sub-root');

		$this->assertEquals(
		$response->getStatusCode(),
			404,
			'The page should not be found since its parent has not been published, in this case http://<yousitename>/root/sub-root or http://<yousitename>/sub-root'
		);
	}

}
