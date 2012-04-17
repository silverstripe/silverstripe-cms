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

	/**
	 * Doesn't deal with a single record, and we need
	 * to avoid session state from previous record edits leaking in here.
	 */
	public function currentPageID() {
		return false;
	}

	public function isCurrentPage(DataObject $record) {
		return false;
	}
}
