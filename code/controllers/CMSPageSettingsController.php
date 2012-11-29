<?php

/**
 * @package cms
 */
class CMSPageSettingsController extends CMSMain {

	static $url_segment = 'pages/settings';
	static $url_rule = '/$Action/$ID/$OtherID';
	static $url_priority = 42;
	static $required_permission_codes = 'CMS_ACCESS_CMSMain';
	static $session_namespace = 'CMSMain';
		
	public function getEditForm($id = null, $fields = null) {
		$record = $this->getRecord($id ? $id : $this->currentPageID());
		
		return parent::getEditForm($record, ($record) ? $record->getSettingsFields() : null);
	}

	public function Breadcrumbs($unlinked = false) {
		$crumbs = parent::Breadcrumbs($unlinked);
		$crumbs[0]->Title = _t('CMSPagesController.MENUTITLE');
		return $crumbs;
	}

}
