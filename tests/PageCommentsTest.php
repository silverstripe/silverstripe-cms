<?php

class PageCommentsTest extends FunctionalTest {
	
	static $fixture_file = 'cms/tests/PageCommentsTest.yml';
	
	static $use_draft_site = true;
	
	function testCanView() {
		$visitor = $this->objFromFixture('Member', 'visitor');
		$admin = $this->objFromFixture('Member', 'commentadmin');
		$comment = $this->objFromFixture('PageComment', 'firstComA');
		
		$this->assertTrue($comment->canView($visitor), 
			'Unauthenticated members can view comments associated to a page with ProvideComments=1'
		);
		$this->assertTrue($comment->canView($admin),
			'Admins with CMS_ACCESS_CommentAdmin permissions can view comments associated to a page with ProvideComments=1'
		);
		
		$disabledComment = $this->objFromFixture('PageComment', 'disabledCom');
		
		$this->assertFalse($disabledComment->canView($visitor),
		'Unauthenticated members can not view comments associated to a page with ProvideComments=0'
		);
		$this->assertTrue($disabledComment->canView($admin),
			'Admins with CMS_ACCESS_CommentAdmin permissions can view comments associated to a page with ProvideComments=0'
		);
	}
	
	function testCanEdit() {
		$visitor = $this->objFromFixture('Member', 'visitor');
		$admin = $this->objFromFixture('Member', 'commentadmin');
		$comment = $this->objFromFixture('PageComment', 'firstComA');
		
		$this->assertFalse($comment->canEdit($visitor));
		$this->assertTrue($comment->canEdit($admin));
	}
	
	function testCanDelete() {
		$visitor = $this->objFromFixture('Member', 'visitor');
		$admin = $this->objFromFixture('Member', 'commentadmin');
		$comment = $this->objFromFixture('PageComment', 'firstComA');
		
		$this->assertFalse($comment->canEdit($visitor));
		$this->assertTrue($comment->canEdit($admin));
	}
	
	function testDeleteComment() {
		$firstPage = $this->objFromFixture('Page', 'first');
		$this->autoFollowRedirection = false;
		$this->logInAs('commentadmin');
		
		$firstComment = $this->objFromFixture('PageComment', 'firstComA');
		$firstCommentID = $firstComment->ID;
		$response = $this->get($firstPage->RelativeLink());
		$token = SecurityToken::inst();
		$url = sprintf('PageComment/deletecomment/%d/', $firstComment->ID);
		$url = $token->addToUrl($url);
		$response = $this->get($url);
		
		$this->assertFalse(DataObject::get_by_id('PageComment', $firstCommentID));
	}
	
	function testDeleteAllCommentsOnPage() {
		$second = $this->objFromFixture('Page', 'second');
		$this->autoFollowRedirection = false;
		$this->logInAs('commentadmin');
		
		$response = $this->get($second->RelativeLink());
		$token = SecurityToken::inst();
		$url = sprintf('PageComment/deleteallcomments?pageid=%d', $second->ID);
		$url = $token->addToUrl($url);
		$response = $this->get($url);
		$response = $this->get($second->RelativeLink());

		$secondComments = DataObject::get('PageComment', '"ParentID" = '.$second->ID);
		$this->assertNull($secondComments);
		
		$first = $this->objFromFixture('Page', 'first');
		$firstComments = DataObject::get('PageComment', '"ParentID" = '.$first->ID);
		$this->assertNotNull($firstComments);
		
		$third = $this->objFromFixture('Page', 'third');
		$thirdComments = DataObject::get('PageComment', '"ParentID" = '.$third->ID);
		$this->assertEquals($thirdComments->Count(), 3);
	}
	
	function testCommenterURLWrite() {
		$comment = new PageComment();
		// We only care about the CommenterURL, so only set that
		// Check a http and https URL. Add more test urls here as needed.
		$protocols = array(
			'Http',
			'Https',
		);
		$url = '://example.com';
		foreach($protocols as $protocol) {
			$comment->CommenterURL = $protocol . $url;
			// The protocol should stay as if, assuming it is valid
			$comment->write();
			$this->assertEquals($comment->CommenterURL, $protocol . $url, $protocol . ':// is a valid protocol');
		}
	}
}
