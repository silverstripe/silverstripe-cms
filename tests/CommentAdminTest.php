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
		$this->assertEquals(2, $resp);
	}
	
	function testNumSpam(){
		
		$comm = new CommentAdmin();
		$resp = $comm->NumSpam();
		$this->assertEquals(0, $resp);
	}
	
	function testdeletemarked(){
		$comm = $this->objFromFixture('PageComment', 'Comment1');
		$id = $comm->ID;
		$this->logInWithPermission('CMS_ACCESS_CommentAdmin');
		$response = $this->get("admin/comments/EditForm/field/Comments/item/$id/delete");
		$checkComm = DataObject::get_by_id('PageComment',$id);

		$this->assertFalse($checkComm);
	}
	
}
?>