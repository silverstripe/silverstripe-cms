<?php

class PageCommentsTest extends FunctionalTest {
	
	static $fixture_file = 'cms/tests/PageCommentsTest.yml';
	
	
	function testDeleteAllCommentsOnPage() {
		
		
		$second = $this->objFromFixture('Page', 'second');
		$this->autoFollowRedirection = false;
		$this->logInAs('admin');
		Director::test('second-page', null, $this->session());
		Director::test('PageComment/deleteallcomments?pageid='.$second->ID,
			null, $this->session());
		Director::test('second-page', null, $this->session());
		
		$secondComments = DataObject::get('PageComment', 'ParentID = '.$second->ID);
		$this->assertNull($secondComments);
		
		$first = $this->objFromFixture('Page', 'first');
		$firstComments = DataObject::get('PageComment', 'ParentID = '.$first->ID);
		$this->assertNotNull($firstComments);
		
		$third = $this->objFromFixture('Page', 'third');
		$thirdComments = DataObject::get('PageComment', 'ParentID = '.$third->ID);
		$this->assertEquals($thirdComments->Count(), 3);
	}
}
?>
