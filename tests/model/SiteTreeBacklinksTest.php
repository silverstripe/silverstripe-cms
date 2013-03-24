<?php

class SiteTreeBacklinksTest extends SapphireTest {
	protected static $fixture_file = "SiteTreeBacklinksTest.yml";

	protected $requiredExtensions = array(
		'SiteTree' => array('SiteTreeBacklinksTest_DOD'),
	);
	
	public function setUp() {
		parent::setUp();
		
		// Log in as admin so that we don't run into permission issues.  That's not what we're
		// testing here.
		$this->logInWithPermission('ADMIN');
	}

	public function testSavingPageWithLinkAddsBacklink() {
		// load page 1
		$page1 = $this->objFromFixture('Page', 'page1');
		
		// assert backlink to page 2 doesn't exist
		$page2 = $this->objFromFixture('Page', 'page2');
		$this->assertNotContains($page2->ID, $page1->BackLinkTracking()->column('ID'), 'Assert backlink to page 2 doesn\'t exist');
		
		// add hyperlink to page 1 on page 2
		$page2->Content .= '<p><a href="[sitetree_link,id='.$page1->ID.']">Testing page 1 link</a></p>';
		$page2->write();
		
		// load page 1
		$page1 = $this->objFromFixture('Page', 'page1');
		
		// assert backlink to page 2 exists
		$this->assertContains($page2->ID, $page1->BackLinkTracking()->column('ID'), 'Assert backlink to page 2 exists');
	}
	
	public function testRemovingLinkFromPageRemovesBacklink() {
		// load page 1
		$page1 = $this->objFromFixture('Page', 'page1');
		
		// assert backlink to page 3 exits
		$page3 = $this->objFromFixture('Page', 'page3');
		$this->assertContains($page3->ID, $page1->BackLinkTracking()->column('ID'), 'Assert backlink to page 3 exists');
		
		// remove hyperlink to page 1
		$page3->Content = '<p>No links anymore!</p>';
		$page3->write();
		
		// load page 1
		$page1 = $this->objFromFixture('Page', 'page1');
		
		// assert backlink to page 3 exists
		$this->assertNotContains($page3->ID, $page1->BackLinkTracking()->column('ID'), 'Assert backlink to page 3 doesn\'t exist');
	}

	public function testChangingUrlOnDraftSiteRewritesLink() {
		// load page 1
		$page1 = $this->objFromFixture('Page', 'page1');
		
		// assert backlink to page 3 exists
		$page3 = $this->objFromFixture('Page', 'page3');
		$this->assertContains($page3->ID, $page1->BackLinkTracking()->column('ID'), 'Assert backlink to page 3 exists');
		
		// assert hyperlink to page 1's current url exists on page 3
		$links = HTTP::getLinksIn($page3->obj('Content')->forTemplate());
		$this->assertContains(Director::baseURL().'page1/', $links, 'Assert hyperlink to page 1\'s current url exists on page 3');
		
		// change url of page 1
		$page1->URLSegment = 'new-url-segment';
		$page1->write();
		
		// load page 3
		$page3 = $this->objFromFixture('Page', 'page3');

		// assert hyperlink to page 1's new url exists
		$links = HTTP::getLinksIn($page3->obj('Content')->forTemplate());
		$this->assertContains(Director::baseURL().'new-url-segment/', $links, 'Assert hyperlink to page 1\'s new url exists on page 3');
	}
	
	public function testChangingUrlOnLiveSiteRewritesLink() {
		// publish page 1 & 3
		$page1 = $this->objFromFixture('Page', 'page1');
		$page3 = $this->objFromFixture('Page', 'page3');
		$this->assertTrue($page1->doPublish());
		$this->assertTrue($page3->doPublish());
		
		// load pages from live
		$page1live = Versioned::get_one_by_stage('Page', 'Live', '"SiteTree"."ID" = ' . $page1->ID);
		$page3live = Versioned::get_one_by_stage('Page', 'Live', '"SiteTree"."ID" = ' . $page3->ID);
		
		// assert backlink to page 3 exists
		$this->assertContains($page3live->ID, $page1live->BackLinkTracking()->column('ID'), 'Assert backlink to page 3 exists');
		
		// assert hyperlink to page 1's current url exists on page 3
		$links = HTTP::getLinksIn($page3live->obj('Content')->forTemplate());
		$this->assertContains(Director::baseURL().'page1/', $links, 'Assert hyperlink to page 1\'s current url exists on page 3');
		
		// change url of page 1
		$page1live->URLSegment = 'new-url-segment';
		$page1live->writeToStage('Live');
		
		// load page 3 from live
		$page3live = Versioned::get_one_by_stage('Page', 'Live', '"SiteTree"."ID" = ' . $page3->ID);
		
		// assert hyperlink to page 1's new url exists
		Versioned::reading_stage('Live');
		$links = HTTP::getLinksIn($page3live->obj('Content')->forTemplate());
		$this->assertContains(Director::baseURL().'new-url-segment/', $links, 'Assert hyperlink to page 1\'s new url exists on page 3');
	}

	public function testPublishingPageWithModifiedUrlRewritesLink() {
		// publish page 1 & 3
		$page1 = $this->objFromFixture('Page', 'page1');
		$page3 = $this->objFromFixture('Page', 'page3');
		
		$this->assertTrue($page1->doPublish());
		$this->assertTrue($page3->doPublish());
		
		// load page 3 from live
		$page3live = Versioned::get_one_by_stage('Page', 'Live', '"SiteTree"."ID" = ' . $page3->ID);
		
		// assert hyperlink to page 1's current url exists
		$links = HTTP::getLinksIn($page3live->obj('Content')->forTemplate());
		$this->assertContains(Director::baseURL().'page1/', $links, 'Assert hyperlink to page 1\'s current url exists on page 3');
		
		// rename url of page 1 on stage
		$page1->URLSegment = 'new-url-segment';
		$page1->write();
		
		// assert hyperlink to page 1's current publish url exists
		$page3live = Versioned::get_one_by_stage('Page', 'Live', '"SiteTree"."ID" = ' . $page3->ID);
		Versioned::reading_stage('Live');
		$links = HTTP::getLinksIn($page3live->obj('Content')->forTemplate());
		$this->assertContains(Director::baseURL().'page1/', $links, 'Assert hyperlink to page 1\'s current published url exists on page 3');
		
		
		// publish page 1
		$this->assertTrue($page1->doPublish());
		
		// assert hyperlink to page 1's new published url exists
		$page3live = Versioned::get_one_by_stage('Page', 'Live', '"SiteTree"."ID" = ' . $page3->ID);
		$links = HTTP::getLinksIn($page3live->obj('Content')->forTemplate());
		$this->assertContains(Director::baseURL().'new-url-segment/', $links, 'Assert hyperlink to page 1\'s new published url exists on page 3');
	}
	
	public function testPublishingPageWithModifiedLinksRewritesLinks() {
		// publish page 1 & 3
		$page1 = $this->objFromFixture('Page', 'page1');
		$page3 = $this->objFromFixture('Page', 'page3');
		$this->assertTrue($page1->doPublish());
		$this->assertTrue($page3->doPublish());
		
		// assert hyperlink to page 1's current url exists
		$links = HTTP::getLinksIn($page3->obj('Content')->forTemplate());
		$this->assertContains(Director::baseURL().'page1/', $links, 'Assert hyperlink to page 1\'s current published url exists on page 3');
		
		// change page 1 url on draft
		$page1->URLSegment = 'new-url-segment';
		
		// save page 1
		$page1->write();
		
		// assert page 3 on draft contains new page 1 url
		$page3 = $this->objFromFixture('Page', 'page3');
		$links = HTTP::getLinksIn($page3->obj('Content')->forTemplate());
		$this->assertContains(Director::baseURL().'new-url-segment/', $links, 'Assert hyperlink to page 1\'s current draft url exists on page 3');
		
		// publish page 3
		$this->assertTrue($page3->doPublish());
		
		// assert page 3 on published site contains old page 1 url
		$page3live = Versioned::get_one_by_stage('Page', 'Live', '"SiteTree"."ID" = ' . $page3->ID);
		Versioned::reading_stage('Live');
		$links = HTTP::getLinksIn($page3live->obj('Content')->forTemplate());
		$this->assertContains(Director::baseURL().'page1/', $links, 'Assert hyperlink to page 1\'s current published url exists on page 3');
		
		// publish page 1
		$this->assertTrue($page1->doPublish());
		
		// assert page 3 on published site contains new page 1 url
		$page3live = Versioned::get_one_by_stage('Page', 'Live', '"SiteTree"."ID" = ' . $page3->ID);
		$links = HTTP::getLinksIn($page3live->obj('Content')->forTemplate());
		$this->assertContains(Director::baseURL().'new-url-segment/', $links, 'Assert hyperlink to page 1\'s current published url exists on page 3');
	}
	
	public function testLinkTrackingOnExtraContentFields() {
		$page1 = $this->objFromFixture('Page', 'page1');
		$page2 = $this->objFromFixture('Page', 'page2');
		$page1->doPublish();
		$page2->doPublish();
		
		// assert backlink to page 2 doesn't exist
		$this->assertNotContains($page2->ID, $page1->BackLinkTracking()->column('ID'), 'Assert backlink to page 2 doesn\'t exist');
		
		// add hyperlink to page 1 on page 2
		$page2->ExtraContent .= '<p><a href="[sitetree_link,id='.$page1->ID.']">Testing page 1 link</a></p>';
		$page2->write();
		$page2->doPublish();

		// assert backlink to page 2 exists
		$this->assertContains($page2->ID, $page1->BackLinkTracking()->column('ID'), 'Assert backlink to page 2 exists');

		// update page1 url
		$page1 = $this->objFromFixture('Page', 'page1');
		$page1->URLSegment = "page1-new-url";
		$page1->write();

		// confirm that draft link on page2 has been rewritten
		$page2 = $this->objFromFixture('Page', 'page2');
		$this->assertEquals('<p><a href="'.Director::baseURL().'page1-new-url/">Testing page 1 link</a></p>', $page2->obj('ExtraContent')->forTemplate());

		// confirm that published link hasn't
		$page2Live = Versioned::get_one_by_stage("Page", "Live", "\"SiteTree\".\"ID\" = $page2->ID");
		Versioned::reading_stage('Live');
		$this->assertEquals('<p><a href="'.Director::baseURL().'page1/">Testing page 1 link</a></p>', $page2Live->obj('ExtraContent')->forTemplate());
		
		// publish page1 and confirm that the link on the published page2 has now been updated
		$page1->doPublish();
		$page2Live = Versioned::get_one_by_stage("Page", "Live", "\"SiteTree\".\"ID\" = $page2->ID");
		$this->assertEquals('<p><a href="'.Director::baseURL().'page1-new-url/">Testing page 1 link</a></p>', $page2Live->obj('ExtraContent')->forTemplate());
		

		// remove hyperlink to page 1
		$page2->ExtraContent = '<p>No links anymore!</p>';
		$page2->write();

		// assert backlink to page 2 no longer exists
		$this->assertNotContains($page2->ID, $page1->BackLinkTracking()->column('ID'), 'Assert backlink to page 2 has been removed');
	}

}

class SiteTreeBacklinksTest_DOD extends DataExtension implements TestOnly {

	private static $db = array(
		'ExtraContent' => 'HTMLText',
	);

	public function updateCMSFields(FieldList $fields) {
		$fields->addFieldToTab("Root.Content", new HTMLEditorField("ExtraContent"));
	}
}

