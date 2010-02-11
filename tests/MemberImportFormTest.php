<?php
/**
 * @package cms
 * @subpackage tests
 */
class MemberImportFormTest extends SapphireTest {
	
	function testLoad() {
		$form = new MemberImportForm(
			new Controller(),
			'Form'
		);
		$data = array(
			'CsvFile' => array(
				'tmp_name' => 'cms/tests/MemberImportFormTest.yml'
			)
		);
		$form->doImport($data, $form);
	}
	
}