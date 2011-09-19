<?php

/**
 * @package cms
 */
class CMSPageSettingsController extends CMSMain {

	static $url_segment = 'page/settings';
	
	static $url_rule = '/$Action/$ID/$OtherID';
	
	static $url_priority = 42;
		
	function getEditForm($id = null, $fields = null) {
		$record = $this->getRecord($id ? $id : $this->currentPageID());
		
		return parent::getEditForm($record, ($record) ? $record->getSettingsFields() : null);
	}
}