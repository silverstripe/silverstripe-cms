<?php
class CMSPageAddController extends CMSPageEditController {

	private static $url_segment = 'pages/add';
	private static $url_rule = '/$Action/$ID/$OtherID';
	private static $url_priority = 42;
	private static $menu_title = 'Add page';
	private static $required_permission_codes = 'CMS_ACCESS_CMSMain';

	private static $allowed_actions = array(
		'AddForm',
		'doAdd',
		'doCancel'
	);

	/**
	 * @return Form
	 */
	function AddForm() {
		$pageTypes = array();
		foreach($this->PageTypes() as $type) {
			$html = sprintf('<span class="page-icon class-%s"></span><strong class="title">%s</strong><span class="description">%s</span>',
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
			// TODO Should be part of the form attribute, but not possible in current form API
			$hintsField = new LiteralField(
				'Hints', 
				sprintf('<span class="hints" data-hints="%s"></span>', Convert::raw2xml($this->SiteTreeHints()))
			),
			new LiteralField('PageModeHeader', sprintf($numericLabelTmpl, 1, _t('CMSMain.ChoosePageParentMode', 'Choose where to create this page'))),
			$parentModeField = new SelectionGroup(
				"ParentModeField",
				array(
					new SelectionGroup_Item(
						"top",
						null,
						$topTitle
					),
					new SelectionGroup_Item(
						'child',
						$parentField = new TreeDropdownField(
							"ParentID", 
							"",
							'SiteTree',
							'ID',
							'TreeTitle'
						),
						$childTitle
					)
				)
			),
			$typeField = new OptionsetField(
				"PageType", 
				sprintf($numericLabelTmpl, 2, _t('CMSMain.ChoosePageType', 'Choose page type')), 
				$pageTypes, 
				'Page'
			),
			new LiteralField(
				'RestrictedNote',
				sprintf(
					'<p class="message notice message-restricted">%s</p>',
					_t(
						'CMSMain.AddPageRestriction', 
						'Note: Some page types are not allowed for this selection'
			)
				)
			)
		);
		$parentField->setSearchFunction(function ($sourceObject, $labelField, $search) {
			return DataObject::get(
				$sourceObject, 
				sprintf(
					"\"MenuTitle\" LIKE '%%%s%%' OR \"Title\" LIKE '%%%s%%'",
					Convert::raw2sql($search),
					Convert::raw2sql($search)
				)
			);
		});

		// TODO Re-enable search once it allows for HTML title display, 
		// see http://open.silverstripe.org/ticket/7455
		// $parentField->setShowSearch(true);
		
		$parentModeField->addExtraClass('parent-mode');

		// CMSMain->currentPageID() automatically sets the homepage,
		// which we need to counteract in the default selection (which should default to root, ID=0)
		if($parentID = $this->request->getVar('ParentID')) {
			$parentModeField->setValue('child');
			$parentField->setValue((int)$parentID);
		} else {
			$parentModeField->setValue('top');
		}
		
		$actions = new FieldList(
			FormAction::create("doAdd", _t('CMSMain.Create',"Create"))
				->addExtraClass('ss-ui-action-constructive')->setAttribute('data-icon', 'accept')
				->setUseButtonTag(true),
			FormAction::create("doCancel", _t('CMSMain.Cancel',"Cancel"))
				->addExtraClass('ss-ui-action-destructive ss-ui-action-cancel')
				->setUseButtonTag(true)
		);
		
		$this->extend('updatePageOptions', $fields);
		
		$form = CMSForm::create( 
			$this, "AddForm", $fields, $actions
		)->setHTMLID('Form_AddForm');
		$form->setResponseNegotiator($this->getResponseNegotiator());
		$form->addExtraClass('cms-add-form stacked cms-content center cms-edit-form ' . $this->BaseCSSClasses());
		$form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));

		return $form;
	}

	public function doAdd($data, $form) {
		$className = isset($data['PageType']) ? $data['PageType'] : "Page";
		$parentID = isset($data['ParentID']) ? (int)$data['ParentID'] : 0;

		$suffix = isset($data['Suffix']) ? "-" . $data['Suffix'] : null;

		if(!$parentID && isset($data['Parent'])) {
			$page = SiteTree::get_by_link($data['Parent']);
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
		if(class_exists('Translatable') && $record->hasExtension('Translatable') && isset($data['Locale'])) {
			$record->Locale = $data['Locale'];
		}

		try {
			$record->write();
		} catch(ValidationException $ex) {
			$form->sessionMessage($ex->getResult()->message(), 'bad');
			return $this->getResponseNegotiator()->respond($this->request);
		}

		$editController = singleton('CMSPageEditController');
		$editController->setCurrentPageID($record->ID);

		Session::set(
			"FormInfo.Form_EditForm.formError.message", 
			_t('CMSMain.PageAdded', 'Successfully created page')
		);
		Session::set("FormInfo.Form_EditForm.formError.type", 'good');
		
		return $this->redirect(Controller::join_links(singleton('CMSPageEditController')->Link('show'), $record->ID));
	}

	public function doCancel($data, $form) {
		return $this->redirect(singleton('CMSMain')->Link());
	}

}
