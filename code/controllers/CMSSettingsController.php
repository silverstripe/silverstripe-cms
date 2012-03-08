<?php
class CMSSettingsController extends CMSMain {

	static $url_segment = 'settings';
	static $url_rule = '/$Action/$ID/$OtherID';
	static $menu_priority = -1;
	static $menu_title = 'Settings';
	
		/**
	 * @return Form
	 */
	function getEditForm($id = null) {
		$siteConfig = SiteConfig::current_site_config();
		$fields = $siteConfig->getCMSFields();

		$actions = $siteConfig->getCMSActions();
		$form = new Form($this, 'EditForm', $fields, $actions);
		$form->addExtraClass('root-form');
		$form->addExtraClass('cms-edit-form');
		// TODO Can't merge $FormAttributes in template at the moment
		$form->addExtraClass('cms-content center ss-tabset');
		if($form->Fields()->hasTabset()) $form->Fields()->findOrMakeTab('Root')->setTemplate('CMSTabSet');
		$form->setHTMLID('Form_EditForm');
		$form->loadDataFrom($siteConfig);
		$form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));

		// Use <button> to allow full jQuery UI styling
		$actions = $actions->dataFields();
		if($actions) foreach($actions as $action) $action->setUseButtonTag(true);

		$this->extend('updateEditForm', $form);

		return $form;
	}
	
	function PreviewLink() {
		return false;
	}

	function Breadcrumbs() {
		return new ArrayList(array(
			new ArrayData(array(
				'Title' => $this->SectionTitle(),
				'Link' => false
			))
		));
	}
	
}