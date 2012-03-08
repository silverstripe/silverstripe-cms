<?php
class CMSPageAddController extends CMSMain {

	static $url_segment = 'page/add';
	static $url_rule = '/$Action/$ID/$OtherID';
	static $url_priority = 42;
	static $menu_title = 'Add page';
	static $required_permission_codes = 'CMS_ACCESS_CMSMain';
	
	function AddForm() {
		$form = parent::AddForm();

		$form->addExtraClass('cms-content center cms-edit-form ' . $this->BaseCSSClasses());
		$form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
		if($parentID = $this->request->getVar('ParentID')) {
			$form->Fields()->dataFieldByName('ParentID')->setValue((int)$parentID);
		}

		return $form;
	}

	function doAdd($data, $form) {
		$return = parent::doAdd($data, $form);
		$this->getResponse()->addHeader('X-Controller', 'CMSPageEditController');
		return $return;
	}

}