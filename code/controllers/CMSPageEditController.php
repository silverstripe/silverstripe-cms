<?php

/**
 * @package cms
 */
class CMSPageEditController extends CMSMain {

	static $url_segment = 'pages/edit';
	static $url_rule = '/$Action/$ID/$OtherID';
	static $url_priority = 41;
	static $required_permission_codes = 'CMS_ACCESS_CMSMain';
	static $session_namespace = 'CMSMain';

	public function Breadcrumbs($unlinked = false) {
		$crumbs = parent::Breadcrumbs($unlinked);
		$crumbs[0]->Title = _t('CMSPagesController.MENUTITLE');
		return $crumbs;
	}

}
