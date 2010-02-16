<?php

class CommentAdminTest extends FunctionalTest {
	
	static $fixture_file = 'cms/tests/CMSMainTest.yml';
	
	function testNumModerated() {
		
		$comm = new CommentAdmin();
		$resp = $comm->NumModerated();
		$this->assertEquals(1, $resp);
	}
	
	function testNumUnmoderated(){
		
		$comm = new CommentAdmin();
		$resp = $comm->NumUnmoderated();
		$this->assertEquals(1, $resp);
	}
	
	function testNumSpam(){
		
		$comm = new CommentAdmin();
		$resp = $comm->NumSpam();
		$this->assertEquals(0, $resp);
	}
	
	function testacceptmarked(){
		$id = $this->idFromFixture('PageComment', 'Comment1');
		$this->logInWithPermssion('ADMIN');
		$result = $this->get('admin/comments/EditForm/field/Comments/item/2/delete');
		$this->assertEquals(200, $result->getStatusCode());
	}
	
}
?>