<?php
/**
 * Imports {@link Group} records by CSV upload, as defined in
 * {@link GroupCsvBulkLoader}.
 * 
 * @package cms
 * @subpackage batchactions
 */
class GroupImportForm extends Form {
	
	/**
	 * @var Group Optional group relation
	 */
	protected $group;
	
	function __construct($controller, $name, $fields = null, $actions = null, $validator = null) {
		if(!$fields) {
			$fields = new FieldSet(
				$fileField = new FileField(
					'CsvFile', 
					_t(
						'SecurityAdmin_MemberImportForm.FileFieldLabel', 
						'CSV File <small>(Allowed extensions: *.csv)</small>'
					)
				)
			);
			$fileField->setAllowedExtensions(array('csv'));
		}
		
		if(!$actions) $actions = new FieldSet(
			new FormAction('doImport', _t('SecurityAdmin_MemberImportForm.BtnImport', 'Import'))
		);
		
		if(!$validator) $validator = new RequiredFields('CsvFile');
		
		
		parent::__construct($controller, $name, $fields, $actions, $validator);
	}
	
	function doImport($data, $form) {
		$loader = new GroupCsvBulkLoader();
		
		// load file
		$result = $loader->load($data['CsvFile']['tmp_name']);
		
		// result message
		$msgArr = array();
		if($result->CreatedCount()) $msgArr[] = sprintf(
			_t('GroupImportForm.ResultCreated', 'Created %d groups'),
			$result->CreatedCount()
		);
		if($result->UpdatedCount()) $msgArr[] = sprintf(
			_t('GroupImportForm.ResultUpdated', 'Updated %d groups'),
			$result->UpdatedCount()
		);
		if($result->DeletedCount()) $msgArr[] = sprintf(
			_t('GroupImportForm.ResultDeleted', 'Deleted %d groups'),
			$result->DeletedCount()
		);
		$msg = ($msgArr) ? implode(',', $msgArr) : _t('MemberImportForm.ResultNone', 'No changes');
	
		$this->sessionMessage($msg, 'good');
		
		Director::redirectBack();
	}
	
}
?>