<?php
class CMSPageAddController extends CMSPageEditController {

	static $url_segment = 'pages/add';
	static $url_rule = '/$Action/$ID/$OtherID';
	static $url_priority = 42;
	static $menu_title = 'Add page';
	static $required_permission_codes = 'CMS_ACCESS_CMSMain';

	static $allowed_actions = array(
		'AddForm',
		'doAdd',
	);

	/**
	 * @return Form
	 */
	function AddForm() {
		$record = $this->currentPage();
		
		$pageTypes = array();
		foreach($this->PageTypes() as $type) {
			$html = sprintf('<span class="icon class-%s"></span><strong class="title">%s</strong><span class="description">%s</span>',
				$type->getField('ClassName'),
				$type->getField('AddAction'),
				$type->getField('Description')
			);
			$pageTypes[$type->getField('ClassName')] = $html;
		}
		// Ensure generic page type shows on top
		if(isset($pageTypes['Page'])) {
			$pageTitle = $pageTypes['Page'];
			$pageTypes = array_merge(array('Page' => $pageTitle), $pageTypes);
		}

		$numericLabelTmpl = '<span class="step-label"><span class="flyout">%d</span><span class="arrow"></span><span class="title">%s</span></span>';

		$topTitle = _t('CMSPageAddController.ParentMode_top', 'Top level');
		$childTitle = _t('CMSPageAddController.ParentMode_child', 'Under another page');

		$fields = new FieldList(
			// new HiddenField("ParentID", false, ($this->parentRecord) ? $this->parentRecord->ID : null),
			// TODO Should be part of the form attribute, but not possible in current form API
			$hintsField = new LiteralField('Hints', sprintf('<span class="hints" data-hints="%s"></span>', $this->SiteTreeHints())),
			new LiteralField('PageModeHeader', sprintf($numericLabelTmpl, 1, _t('CMSMain.ChoosePageParentMode', 'Choose where to create this page'))),
			
			$parentModeField = new SelectionGroup(
				"ParentModeField",
				array(
					"top//$topTitle" => null, //new LiteralField("Dummy", ''),
					"child//$childTitle" => $parentField = new TreeDropdownField(
						"ParentID", 
						"",
						'SiteTree',
						'ID',
						'TreeTitle'
					)
				)
			),
			$typeField = new OptionsetField(
				"PageType", 
				sprintf($numericLabelTmpl, 2, _t('CMSMain.ChoosePageType', 'Choose page type')), 
				$pageTypes, 
				'Page'
			)
		);
		$parentField->setShowSearch(true);
		$parentModeField->setValue($this->request->getVar('ParentID') ? 'child' : 'top');
		$parentModeField->addExtraClass('parent-mode');

		// CMSMain->currentPageID() automatically sets the homepage,
		// which we need to counteract in the default selection (which should default to root, ID=0)
		$homepageSegment = RootURLController::get_homepage_link();
		if($record && $record->URLSegment != $homepageSegment) {
			$parentField->setValue($record->ID);	
		}
		
		$actions = new FieldList(
			// $resetAction = new ResetFormAction('doCancel', _t('CMSMain.Cancel', 'Cancel')),
			FormAction::create("doAdd", _t('CMSMain.Create',"Create"))
				->addExtraClass('ss-ui-action-constructive')->setAttribute('data-icon', 'accept')
				->setUseButtonTag(true)
		);
		
		$this->extend('updatePageOptions', $fields);
		
		$form = new Form($this, "AddForm", $fields, $actions);
		$form->addExtraClass('cms-add-form stacked cms-content center cms-edit-form ' . $this->BaseCSSClasses());
		$form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));

		if($parentID = $this->request->getVar('ParentID')) {
			$form->Fields()->dataFieldByName('ParentID')->setValue((int)$parentID);
		}

		return $form;
	}

	public function doAdd($data, $form) {
		$className = isset($data['PageType']) ? $data['PageType'] : "Page";
		$parentMode = isset($data['ParentModeField']) ? $data['ParentModeField'] : "top";
		$parentID = isset($data['ParentID']) ? (int)$data['ParentID'] : 0;

		if ($parentMode == "top") $parentID = 0;

		$suffix = isset($data['Suffix']) ? "-" . $data['Suffix'] : null;

		if(!$parentID && isset($data['Parent'])) {
			$page = SiteTree:: get_by_link(Convert::raw2sql($data['Parent']));
			if($page) $parentID = $page->ID;
		}

		if(is_numeric($parentID) && $parentID > 0) $parentObj = DataObject::get_by_id("SiteTree", $parentID);
		else $parentObj = null;
		
		if(!$parentObj || !$parentObj->ID) $parentID = 0;

		if($parentObj) {
			if(!$parentObj->canAddChildren()) return Security::permissionFailure($this);
			if(!singleton($className)->canCreate()) return Security::permissionFailure($this);
		} else {
			if(!SiteConfig::current_site_config()->canCreateTopLevel())
				return Security::permissionFailure($this);
		}
		
		$record = $this->getNewItem("new-$className-$parentID".$suffix, false);
		if(class_exists('Translatable') && $record->hasExtension('Translatable')) $record->Locale = $data['Locale'];
		$record->write();
		$editController = singleton('CMSPageEditController');
		$editController->setCurrentPageID($record->ID);
		
		return $this->redirect(Controller::join_links(singleton('CMSPageEditController')->Link('show'), $record->ID));
	}

}
