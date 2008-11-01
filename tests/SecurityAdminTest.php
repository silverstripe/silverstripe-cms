<?php

class SecurityAdminTest extends FunctionalTest {
	static $fixture_file = 'cms/tests/CMSMainTest.yml';
	
	function testGroupExport() {
		$this->session()->inst_set('loggedInAs', $this->idFromFixture('Member', 'admin'));
		
		/* First, open the applicable group */
		$this->get('admin/security/getitem?ID=' . $this->idFromFixture('Group','admin'));
		$this->assertRegExp('/<input[^>]+id="Form_EditForm_Title"[^>]+value="Administrators"[^>]*>/',$this->content());
		
		/* Then load the export page */
		$this->get('admin/security//EditForm/field/Members/export');
		
		$this->assertRegExp(
			'/"' . _t('MemberTableField.FIRSTNAME') . '","' . _t('MemberTableField.SURNAME') . '","' . _t('MemberTableField.EMAIL') . '"/', 
			$this->content()
		);
		$this->assertRegExp('/"","","admin@example.com"/', $this->content());
	}

	function testEmptyGroupExport() {
		$this->session()->inst_set('loggedInAs', $this->idFromFixture('Member', 'admin'));
		
		/* First, open the applicable group */
		$this->get('admin/security/getitem?ID=' . $this->idFromFixture('Group','empty'));
		$this->assertRegExp('/<input[^>]+id="Form_EditForm_Title"[^>]+value="Empty Group"[^>]*>/',$this->content());
		
		/* Then load the export page */
		$this->get('admin/security//EditForm/field/Members/export');
		
		$this->assertRegExp(
			'/"' . _t('MemberTableField.FIRSTNAME') . '","' . _t('MemberTableField.SURNAME') . '","' . _t('MemberTableField.EMAIL') . '"/', 
			$this->content()
		);
	}
	
}

?>