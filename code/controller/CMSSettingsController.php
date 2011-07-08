<?php
class CMSSettingsController extends CMSMain {

	static $url_segment = 'settings';
	static $url_rule = '/$Action/$ID/$OtherID';
	static $menu_priority = -1;
	static $menu_title = 'Settings';
	
	function getEditForm($id = null, $fields = null) {
		return $this->RootForm();
	}
	
}