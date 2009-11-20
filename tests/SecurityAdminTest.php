<?php
/**
 * @package cms
 * @subpackage tests
 */
class SecurityAdminTest extends FunctionalTest {
	static $fixture_file = 'cms/tests/CMSMainTest.yml';
	
	function testGroupExport() {
		$this->session()->inst_set('loggedInAs', $this->idFromFixture('Member', 'admin'));
		
		/* First, open the applicable group */
		$this->get('admin/security/getitem?ID=' . $this->idFromFixture('Group','admin'));
		$this->assertRegExp('/<input[^>]+id="Form_EditForm_Title"[^>]+value="Administrators"[^>]*>/',$this->content());
		
		/* Then load the export page */
		$this->get('admin/security//EditForm/field/Members/export');
		$lines = preg_split('/\n/', $this->content());
		
		$this->assertEquals(count($lines), 3, "Export with members has one content row");
		$this->assertRegExp('/"","","admin@example.com"/', $lines[1], "Member values are correctly exported");
	}

	function testEmptyGroupExport() {
		$this->session()->inst_set('loggedInAs', $this->idFromFixture('Member', 'admin'));
		
		/* First, open the applicable group */
		$this->get('admin/security/getitem?ID=' . $this->idFromFixture('Group','empty'));
		$this->assertRegExp('/<input[^>]+id="Form_EditForm_Title"[^>]+value="Empty Group"[^>]*>/',$this->content());
		
		/* Then load the export page */
		$this->get('admin/security//EditForm/field/Members/export');
		$lines = preg_split('/\n/', $this->content());
		
		$this->assertEquals(count($lines), 2, "Empty export only has header fields and an empty row");
		$this->assertEquals($lines[1], '', "Empty export only has no content row");
	}
	
	function testHidePermissions() {
		$permissionCheckboxSet = new PermissionCheckboxSetField('Permissions','Permissions','Permission','GroupID');
		$this->assertContains('CMS_ACCESS_CMSMain', $permissionCheckboxSet->Field());
		$this->assertContains('CMS_ACCESS_AssetAdmin', $permissionCheckboxSet->Field());
		
		SecurityAdmin::hide_permissions(array('CMS_ACCESS_CMSMain','CMS_ACCESS_AssetAdmin'));
		$this->assertNotContains('CMS_ACCESS_CMSMain', $permissionCheckboxSet->Field());
		$this->assertNotContains('CMS_ACCESS_AssetAdmin', $permissionCheckboxSet->Field());
	}
	
}

?>