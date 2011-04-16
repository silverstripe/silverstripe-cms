<?php
class CMSPageEditController extends CMSMain {

	static $url_segment = 'page/edit';
	static $url_rule = '/$Action/$ID/$OtherID';
	static $url_priority = 41;
	
	function getEditForm($id = null, $fields = null) {
		$record = $this->getRecord($id ? $id : $this->currentPageID());
		return parent::getEditForm($record, ($record) ? $record->getCMSFields() : null);
	}
	
}