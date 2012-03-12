<?php

/**
 * @package cms
 */
class CMSPageEditController extends CMSMain {

	static $url_segment = 'page/edit';
	static $url_rule = '/$Action/$ID/$OtherID';
	static $url_priority = 41;
	static $required_permission_codes = 'CMS_ACCESS_CMSMain';

	public function Breadcrumbs($unlinked = false) {
		$crumbs = parent::Breadcrumbs($unlinked);
		// Remove "root" element, as its already shown in the tree panel
		$crumbs->shift();
		return $crumbs;
	}
}