<?php

/**
 * @package cms
 */
class CMSPageEditController extends CMSMain {

	static $url_segment = 'page/edit';
	static $url_rule = '/$Action/$ID/$OtherID';
	static $url_priority = 41;

	public function getEditForm($id = null, $fields = null) {
		$left = new SSViewer('CMSPageController_EditForm_Left');
		
		$form = parent::getEditForm($id, $fields);
		$form->Left = $left->process($this);
		
		return $form;
	}
}