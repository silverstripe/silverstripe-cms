<?php
class CMSSettingsController extends LeftAndMain {

	static $url_segment = 'settings';
	static $url_rule = '/$Action/$ID/$OtherID';
	static $menu_priority = -1;
	static $menu_title = 'Settings';
	
		/**
	 * @return Form
	 */
	function getEditForm($id = null, $fields = null) {
		$siteConfig = SiteConfig::current_site_config();
		$fields = $siteConfig->getCMSFields();

		$actions = $siteConfig->getCMSActions();
		$form = new Form($this, 'EditForm', $fields, $actions);
		$form->addExtraClass('root-form');

		$form->addExtraClass('cms-edit-form cms-panel-padded center');

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


	/**
	 * Save the current sites {@link SiteConfig} into the database
	 *
	 * @param array $data 
	 * @param Form $form 
	 * @return String
	 */
	function save_siteconfig($data, $form) {
		$siteConfig = SiteConfig::current_site_config();
		$form->saveInto($siteConfig);
		$siteConfig->write();
		
		$this->response->addHeader('X-Status', rawurlencode(_t('LeftAndMain.SAVEDUP')));
	
		return $form->forTemplate();
	}
	
	function LinkPreview() {
		return false;
	}

	function Breadcrumbs($unlinked = false) {
		return new ArrayList(array(
			new ArrayData(array(
				'Title' => $this->SectionTitle(),
				'Link' => false
			))
		));
	}
	
}
