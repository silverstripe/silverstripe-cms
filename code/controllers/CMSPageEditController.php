<?php

/**
 * @package cms
 */
class CMSPageEditController extends CMSMain {

	private static $url_segment = 'pages/edit';
	private static $url_rule = '/$Action/$ID/$OtherID';
	private static $url_priority = 41;
	private static $required_permission_codes = 'CMS_ACCESS_CMSMain';
	private static $session_namespace = 'CMSMain';

	public function Breadcrumbs($unlinked = false) {
		$crumbs = parent::Breadcrumbs($unlinked);
		$crumbs[0]->Title = _t('CMSPagesController.MENUTITLE');
		return $crumbs;
	}

}
