<?php
class CMSPageHistoryController extends CMSMain {

	static $url_segment = 'page/history';
	static $url_rule = '/$Action/$ID/$OtherID';
	static $url_priority = 42;
	
	function getEditForm($id = null, $fields = null) {
		return "Not implemented yet";
	}
	
}