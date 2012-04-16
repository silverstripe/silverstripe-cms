<?php

/**
 * @package cms
 */
class CMSPagesController extends CMSMain {
	
	static $url_segment = 'pages';
	static $url_rule = '/$Action/$ID/$OtherID';
	static $url_priority = 40;
	static $menu_title = 'Pages';	
	static $required_permission_codes = 'CMS_ACCESS_CMSMain';
	
	function PreviewLink() {
		return false;
	}

	public function currentPageID() {
		return false;
	}
}
