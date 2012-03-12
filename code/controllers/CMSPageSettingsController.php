<?php

/**
 * @package cms
 */
class CMSPageSettingsController extends CMSMain {

	static $url_segment = 'page/settings';
	static $url_rule = '/$Action/$ID/$OtherID';
	static $url_priority = 42;
	static $required_permission_codes = 'CMS_ACCESS_CMSMain';
		
	function getEditForm($id = null, $fields = null) {
		$record = $this->getRecord($id ? $id : $this->currentPageID());
		
		return parent::getEditForm($record, ($record) ? $record->getSettingsFields() : null);
	}

	public function Breadcrumbs($unlinked = false) {
		$crumbs = parent::Breadcrumbs($unlinked);
		// Remove "root" element, as its already shown in the tree panel
		$crumbs->shift();
		return $crumbs;
	}
}