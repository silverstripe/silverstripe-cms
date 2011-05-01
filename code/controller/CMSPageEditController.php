<?php
class CMSPageEditController extends CMSMain {

	static $url_segment = 'page/edit';
	static $url_rule = '/$Action/$ID/$OtherID';
	static $url_priority = 41;
	
	function getEditForm($id = null, $fields = null) {
		$record = $this->getRecord($id ? $id : $this->currentPageID());
		$form = parent::getEditForm($record, ($record) ? $record->getCMSFields() : null);
		
		// TODO Replace with preview button
		$form->Fields()->addFieldToTab(
			'Root.Main', 
			new LiteralField('SwitchView', sprintf('<div class="cms-switch-view field"><label>Preview:</label><div class="middleColumn">%s</div></div>', $this->SwitchView()))
		);
		
		return $form;
	}
	
}