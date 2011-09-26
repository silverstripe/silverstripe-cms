<?php
/**
 * The main "content" area of the CMS.
 *
 * This class creates a 2-frame layout - left-tree and right-form - to sit beneath the main
 * admin menu.
 * 
 * @package cms
 * @subpackage controller
 * @todo Create some base classes to contain the generic functionality that will be replicated.
 */
class CMSMain extends LeftAndMain implements CurrentPageIdentifier, PermissionProvider {
	
	static $url_segment = '';
	
	static $url_rule = '/$Action/$ID/$OtherID';
	
	// Maintain a lower priority than other administration sections
	// so that Director does not think they are actions of CMSMain
	static $url_priority = 40;
	
	static $menu_title = 'Edit Page';
	
	static $menu_priority = 10;
	
	static $tree_class = "SiteTree";
	
	static $subitem_class = "Member";
	
	static $allowed_actions = array(
		'addpage',
		'buildbrokenlinks',
		'deleteitems',
		'DeleteItemsForm',
		'dialog',
		'duplicate',
		'duplicatewithchildren',
		'publishall',
		'publishitems',
		'PublishItemsForm',
		'RootForm',
		'sidereport',
		'SideReportsForm',
		'submit',
		'EditForm',
		'AddForm',
		'SearchForm',
		'SiteTreeAsUL',
		'getshowdeletedsubtree',
		'batchactions',
	);
	
	public function init() {
		// set reading lang
		if(Object::has_extension('SiteTree', 'Translatable') && !$this->isAjax()) {
			Translatable::choose_site_locale(array_keys(Translatable::get_existing_content_languages('SiteTree')));
		}
		
		parent::init();
				
		Requirements::javascript(CMS_DIR . '/javascript/CMSMain.js');
		Requirements::javascript(CMS_DIR . '/javascript/CMSMain.EditForm.js');
		Requirements::javascript(CMS_DIR . '/javascript/CMSMain.AddForm.js');
		Requirements::add_i18n_javascript(CMS_DIR . '/javascript/lang');
		
		Requirements::css(CMS_DIR . '/css/CMSMain.css');
		
		// navigator
		// Requirements::css(CMS_DIR . '/css/SilverStripeNavigator.css');
		Requirements::javascript(CMS_DIR . '/javascript/SilverStripeNavigator.js');
		
		Requirements::combine_files(
			'cmsmain.js',
			array(
				CMS_DIR . '/javascript/CMSMain.js',
				CMS_DIR . '/javascript/CMSMain.EditForm.js',
				CMS_DIR . '/javascript/CMSMain.AddForm.js',
				CMS_DIR . '/javascript/CMSPageHistoryController.js',
				CMS_DIR . '/javascript/SilverStripeNavigator.js'
			)
		);
		
		HtmlEditorField::include_js();
		
		CMSBatchActionHandler::register('publish', 'CMSBatchAction_Publish');
		CMSBatchActionHandler::register('unpublish', 'CMSBatchAction_Unpublish');
		CMSBatchActionHandler::register('delete', 'CMSBatchAction_Delete');
		CMSBatchActionHandler::register('deletefromlive', 'CMSBatchAction_DeleteFromLive');
	}
	
	/**
	 * If this is set to true, the "switchView" context in the
	 * template is shown, with links to the staging and publish site.
	 *
	 * @return boolean
	 */
	function ShowSwitchView() {
		return true;
	}
	
	/**
	 * Overloads the LeftAndMain::ShowView. Allows to pass a page as a parameter, so we are able
	 * to switch view also for archived versions.
	 */
	function SwitchView($page = null) {
		if(!$page) {
			$page = $this->currentPage();
		}
		
		if($page) {
			$nav = SilverStripeNavigator::get_for_record($page);
			return $nav['items'];
		}
	}

	//------------------------------------------------------------------------------------------//
	// Main controllers

	//------------------------------------------------------------------------------------------//
	// Main UI components

	/**
	 * Override {@link LeftAndMain} Link to allow blank URL segment for CMSMain.
	 * 
	 * @return string
	 */
	public function Link($action = null) {
		return Controller::join_links(
			$this->stat('url_base', true),
			$this->stat('url_segment', true), // in case we want to change the segment
			'/', // trailing slash needed if $action is null!
			"$action"
		);
	}

	/**
	 * Return the entire site tree as a nested set of ULs
	 */
	public function SiteTreeAsUL() {
		$this->generateTreeStylingJS();

		// Pre-cache sitetree version numbers for querying efficiency
		Versioned::prepopulate_versionnumber_cache("SiteTree", "Stage");
		Versioned::prepopulate_versionnumber_cache("SiteTree", "Live");

		return $this->getSiteTreeFor($this->stat('tree_class'));
	}
	
	function SearchForm() {
		// get all page types in a dropdown-compatible format
		$pageTypes = SiteTree::page_type_classes(); 
		array_unshift($pageTypes, _t('CMSMain.PAGETYPEANYOPT','Any'));
		$pageTypes = array_combine($pageTypes, $pageTypes);
		asort($pageTypes);
		
		// get all filter instances
		$filters = ClassInfo::subclassesFor('CMSSiteTreeFilter');
		$filterMap = array();
		// remove base class
		array_shift($filters);
		// add filters to map
		foreach($filters as $filter) {
			$filterMap[$filter] = call_user_func(array($filter, 'title'));
		}
		// ensure that 'all pages' filter is on top position
		uasort($filterMap, 
			create_function('$a,$b', 'return ($a == "CMSSiteTreeFilter_Search") ? 1 : -1;')
		);
		
		$fields = new FieldList(
			new TextField('Term', _t('CMSSearch.FILTERLABELTEXT', 'Content')),
			$dateGroup = new FieldGroup(
				$dateFrom = new DateField('LastEditedFrom', _t('CMSSearch.FILTERDATEFROM', 'From')),
				$dateTo = new DateField('LastEditedTo', _t('CMSSearch.FILTERDATETO', 'To'))
			),
			new DropdownField(
				'FilterClass', 
				_t('CMSMain.PAGES', 'Pages'), 
				$filterMap
			),
			new DropdownField(
				'ClassName', 
				_t('CMSMain.PAGETYPEOPT','Page Type', PR_MEDIUM, 'Dropdown for limiting search to a page type'), 
				$pageTypes, 
				null, 
				null, 
				_t('CMSMain.PAGETYPEANYOPT','Any')
			)
			// new TextField('MetaTags', _t('CMSMain.SearchMetaTags', 'Meta tags'))
		);
		$dateGroup->subfieldParam = 'FieldHolder';
		$dateFrom->setConfig('showcalendar', true);
		$dateTo->setConfig('showcalendar', true);

		$actions = new FieldList(
			$resetAction = new ResetFormAction('clear', _t('CMSMain_left.ss.CLEAR', 'Clear')),
			$searchAction = new FormAction('doSearch',  _t('CMSMain_left.ss.SEARCH', 'Search'))
		);
		$resetAction->addExtraClass('ss-ui-action-minor');
		
		$form = new Form($this, 'SearchForm', $fields, $actions);
		$form->setFormMethod('GET');
		$form->disableSecurityToken();
		$form->unsetValidator();
		
		return $form;
	}
	
	function doSearch($data, $form) {
		return $this->getsubtree($this->request);
	}

	/**
	 * Create serialized JSON string with site tree hints data to be injected into
	 * 'data-hints' attribute of root node of jsTree.
	 * 
	 * @return String Serialized JSON
	 */
	public function SiteTreeHints() {
	  $classes = ClassInfo::subclassesFor( $this->stat('tree_class') );

		$def['Root'] = array();
		$def['Root']['disallowedChildren'] = array();
		$def['Root']['disallowedParents'] = array();

		foreach($classes as $class) {
			$obj = singleton($class);
			if($obj instanceof HiddenClass) continue;
			
			$allowedChildren = $obj->allowedChildren();
			//SiteTree::allowedChildren() returns null rather than an empty array if SiteTree::allowed_chldren == 'none'
			if ($allowedChildren == null) $allowedChildren = array();
			$def[$class]['disallowedChildren'] = array_keys(array_diff($classes, $allowedChildren));
			
			$defaultChild = $obj->defaultChild();
			if ($defaultChild != 'Page' && $defaultChild != null) $def[$class]['defaultChild'] = $defaultChild;
			
			$defaultParent = isset(SiteTree::get_by_link($obj->defaultParent())->ID) ? SiteTree::get_by_link($obj->defaultParent())->ID : null;
			if ($defaultParent != 1 && $defaultParent != null)  $def[$class]['defaultParent'] = $defaultParent;
			
		  if(is_array($def[$class]['disallowedChildren'])) foreach($def[$class]['disallowedChildren'] as $disallowedChild) {
				$def[$disallowedChild]['disallowedParents'][] = $class;
			}
			
			//Are any classes allowed to be parents of root?
			$def['Root']['disallowedParents'][] = $class;
		}

		return Convert::raw2xml(Convert::raw2json($def));
	}

	public function generateTreeStylingJS() {
		$classes = ClassInfo::subclassesFor($this->stat('tree_class'));
		foreach($classes as $class) {
			$obj = singleton($class);
			if($obj instanceof HiddenClass) continue;
			if($icon = $obj->stat('icon')) $iconInfo[$class] = $icon;
		}
		$iconInfo['BrokenLink'] = 'cms/images/treeicons/brokenlink';


		$js = "var _TREE_ICONS = [];\n";


		foreach($iconInfo as $class => $icon) {
			// SiteTree::$icon can be set to array($icon, $option)
			// $option can be "file" or "folder" to force the icon to always be the file or the folder form
			$option = null;
			if(is_array($icon)) list($icon, $option) = $icon;

			$fileImage = ($option == "folder") ? $icon . '-openfolder.gif' : $icon . '-file.gif';
			$openFolderImage = $icon . '-openfolder.gif';
			if(!Director::fileExists($openFolderImage) || $option == "file") $openFolderImage = $fileImage;
			$closedFolderImage = $icon . '-closedfolder.gif';
			if(!Director::fileExists($closedFolderImage) || $option == "file") $closedFolderImage = $fileImage;

			$js .= <<<JS
				_TREE_ICONS['$class'] = {
					fileIcon: '$fileImage',
					openFolderIcon: '$openFolderImage',
					closedFolderIcon: '$closedFolderImage'
				};

JS;
		}

		Requirements::customScript($js);
	}

	/**
	 * Populates an array of classes in the CMS
	 * which allows the user to change the page type.
	 *
	 * @return DataObjectSet
	 */
	public function PageTypes() {
		$classes = SiteTree::page_type_classes();

		$result = new ArrayList();

		foreach($classes as $class) {
			$instance = singleton($class);

			if($instance instanceof HiddenClass) continue;

			if(!$instance->canCreate()) continue;

			// skip this type if it is restricted
			if($instance->stat('need_permission') && !$this->can(singleton($class)->stat('need_permission'))) continue;

			$addAction = $instance->i18n_singular_name();
			
			// Get description
			$description = _t($class . 'DESCRIPTION');
			if(!$description) $description = $instance->uninherited('description');
			if($class == 'Page' && !$description) $description = singleton('SiteTree')->uninherited('description');
			
			$result->push(new ArrayData(array(
				'ClassName' => $class,
				'AddAction' => $addAction,
				'Description' => $description,
				// TODO Sprite support
				'IconURL' => $instance->stat('icon')
			)));
		}
		
		$result->sort('AddAction');
		
		return $result;
	}

	/**
	 * Save the current sites {@link SiteConfig} into the database
	 *
	 * @param array $data 
	 * @param Form $form 
	 * @return FormResponse
	 */
	function save_siteconfig($data, $form) {
		$siteConfig = SiteConfig::current_site_config();
		$form->saveInto($siteConfig);
		$siteConfig->write();
		
		$this->response->addHeader('X-Status', _t('LeftAndMain.SAVEDUP'));
	
		return $form->forTemplate();
	}
	
	/**
	 * Get a database record to be managed by the CMS.
	 *
	 * @param int $id Record ID
	 * @param int $versionID optional Version id of the given record
	 */
 	public function getRecord($id, $versionID = null) {
		$treeClass = $this->stat('tree_class');

		if($id instanceof $treeClass) {
			return $id;
		} else if($id && is_numeric($id)) {
			if(isset($_REQUEST['Version'])) $versionID = (int) $_REQUEST['Version'];

			if($versionID) {
				$record = Versioned::get_version($treeClass, $id, $versionID);
			} else {
				$record = DataObject::get_one($treeClass, "\"$treeClass\".\"ID\" = $id");
			}

			// Then, try getting a record from the live site
			if(!$record) {
				// $record = Versioned::get_one_by_stage($treeClass, "Live", "\"$treeClass\".\"ID\" = $id");
				Versioned::reading_stage('Live');
				singleton($treeClass)->flushCache();

				$record = DataObject::get_one( $treeClass, "\"$treeClass\".\"ID\" = $id");
				if($record) Versioned::set_reading_mode('');
			}
			
			// Then, try getting a deleted record
			if(!$record) {
				$record = Versioned::get_latest_version($treeClass, $id);
			}

			// Don't open a page from a different locale
			/** The record's Locale is saved in database in 2.4, and not related with Session,
			 *  we should not check their locale matches the Translatable::get_current_locale,
			 * 	here as long as we all the HTTPRequest is init with right locale.
			 *	This bit breaks the all FileIFrameField functions if the field is used in CMS
			 *  and its relevent ajax calles, like loading the tree dropdown for TreeSelectorField. 
			 */
			/* if($record && Object::has_extension('SiteTree', 'Translatable') && $record->Locale && $record->Locale != Translatable::get_current_locale()) {
				$record = null;
			}*/

			return $record;

		} else if(substr($id,0,3) == 'new') {
			return $this->getNewItem($id);
		}
	}
	
	/**
	 * @param Int $id
	 * @param FieldList $fields
	 * @return Form
	 */
	public function getEditForm($id = null, $fields = null) {
		if(!$id) $id = $this->currentPageID();
		$form = parent::getEditForm($id);
		
		// TODO Duplicate record fetching (see parent implementation)
		$record = $this->getRecord($id);
		if($record && !$record->canView()) return Security::permissionFailure($this);

		if(!$fields) $fields = $form->Fields();
		$actions = $form->Actions();

		if($record) {
			$fields->push($idField = new HiddenField("ID", false, $id));
			// Necessary for different subsites
			$fields->push($liveURLField = new HiddenField("AbsoluteLink", false, $record->AbsoluteLink()));
			$fields->push($liveURLField = new HiddenField("LiveURLSegment"));
			$fields->push($stageURLField = new HiddenField("StageURLSegment"));
			$fields->push(new HiddenField("TreeTitle", false, $record->TreeTitle));

			$fields->push(new HiddenField('Sort','', $record->Sort));

			if($record->ID && is_numeric( $record->ID ) ) {
				$liveRecord = Versioned::get_one_by_stage('SiteTree', 'Live', "\"SiteTree\".\"ID\" = $record->ID");
				if($liveRecord) $liveURLField->setValue($liveRecord->AbsoluteLink());
			}
			
			if(!$record->IsDeletedFromStage) {
				$stageURLField->setValue(Controller::join_links($record->AbsoluteLink(), '?Stage=stage'));
			}
			
			// Added in-line to the form, but plucked into different view by LeftAndMain.Preview.js upon load
			if(in_array('CMSPreviewable', class_implements($record)) && !$fields->fieldByName('SilverStripeNavigator')) {
				$navField = new LiteralField('SilverStripeNavigator', $this->getSilverStripeNavigator());
				$navField->setAllowHTML(true);
				$fields->push($navField);
			}
			
			// getAllCMSActions can be used to completely redefine the action list
			if($record->hasMethod('getAllCMSActions')) {
				$actions = $record->getAllCMSActions();
			} else {
				$actions = $record->getCMSActions();
			}
			
			if($record->hasMethod('getCMSValidator')) {
				$validator = $record->getCMSValidator();
			} else {
				$validator = new RequiredFields();
			}
			
			// The clientside (mainly LeftAndMain*.js) rely on ajax responses
			// which can be evaluated as javascript, hence we need
			// to override any global changes to the validation handler.
			$validator->setJavascriptValidationHandler('prototype');
			
			$form = new Form($this, "EditForm", $fields, $actions, $validator);
			$form->loadDataFrom($record);
			$stageURLField->setValue(Controller::join_links($record->getStageURLSegment(), '?Stage=stage'));
			$form->disableDefaultAction();
			$form->addExtraClass('cms-edit-form');
			$form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
			// TODO Can't merge $FormAttributes in template at the moment
			$form->addExtraClass('cms-content center ss-tabset ' . $this->BaseCSSClasses());
			if($form->Fields()->hasTabset()) $form->Fields()->findOrMakeTab('Root')->setTemplate('CMSTabSet');

			if(!$record->canEdit() || $record->IsDeletedFromStage) {
				$readonlyFields = $form->Fields()->makeReadonly();
				$form->setFields($readonlyFields);
			}

			$this->extend('updateEditForm', $form);

			return $form;
		} if ($id == 0 || $id == 'root') {
			return $this->RootForm();
		} else if($id) {
			return new Form($this, "EditForm", new FieldList(
				new LabelField('PageDoesntExistLabel',_t('CMSMain.PAGENOTEXISTS',"This page doesn't exist"))), new FieldList()
			);
		}
	}

	/**
	 * @return Form
	 */
	function RootForm() {
		$siteConfig = SiteConfig::current_site_config();
		$fields = $siteConfig->getCMSFields();

		$form = new Form($this, 'RootForm', $fields, $siteConfig->getCMSActions());
		$form->addExtraClass('root-form');
		$form->addExtraClass('cms-edit-form');
		// TODO Can't merge $FormAttributes in template at the moment
		$form->addExtraClass('cms-content center ss-tabset');
		if($form->Fields()->hasTabset()) $form->Fields()->findOrMakeTab('Root')->setTemplate('CMSTabSet');
		$form->setHTMLID('Form_EditForm');
		$form->loadDataFrom($siteConfig);
		$form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));

		$this->extend('updateEditForm', $form);

		return $form;
	}
	
	public function currentPageID() {
		$id = parent::currentPageID();
		
		// Fall back to homepage record
		if(!$id) {
			$homepageSegment = RootURLController::get_homepage_link();
			$homepageRecord = DataObject::get_one('SiteTree', sprintf('"URLSegment" = \'%s\'', $homepageSegment));
			if($homepageRecord) $id = $homepageRecord->ID;
		}
		
		return $id;
	}

	//------------------------------------------------------------------------------------------//
	// Data saving handlers

	/**
	 * Save and Publish page handler
	 */
	public function save($data, $form) {
		$className = $this->stat('tree_class');

		// Existing or new record?
		$SQL_id = Convert::raw2sql($data['ID']);
		if(substr($SQL_id,0,3) != 'new') {
			$record = DataObject::get_by_id($className, $SQL_id);
			if($record && !$record->canEdit()) return Security::permissionFailure($this);
		} else {
			if(!singleton($this->stat('tree_class'))->canCreate()) return Security::permissionFailure($this);
			$record = $this->getNewItem($SQL_id, false);
		}
		
		// TODO Coupling to SiteTree
		$record->HasBrokenLink = 0;
		$record->HasBrokenFile = 0;

		$record->writeWithoutVersion();

		// Update the class instance if necessary
		if(isset($data['ClassName']) && $data['ClassName'] != $record->ClassName) {
			$newClassName = $record->ClassName;
			// The records originally saved attribute was overwritten by $form->saveInto($record) before.
			// This is necessary for newClassInstance() to work as expected, and trigger change detection
			// on the ClassName attribute
			$record->setClassName($data['ClassName']);
			// Replace $record with a new instance
			$record = $record->newClassInstance($newClassName);
		}

		// save form data into record
		$form->saveInto($record);
		$record->write();
		
		// If the 'Save & Publish' button was clicked, also publish the page
		if (isset($data['publish']) && $data['publish'] == 1) {
			$record->doPublish();
			
			// Update classname with original and get new instance (see above for explanation)
			if(isset($data['ClassName'])) {
				$record->setClassName($data['ClassName']);
				$publishedRecord = $record->newClassInstance($record->ClassName);
			}
			
			$this->response->addHeader(
				'X-Status',
				sprintf(
					_t(
						'LeftAndMain.STATUSPUBLISHEDSUCCESS', 
						"Published '%s' successfully",
						PR_MEDIUM,
						'Status message after publishing a page, showing the page title'
					),
					$record->Title
				)
			);
		
			// Reload form, data and actions might have changed
			$form = $this->getEditForm($record->ID);
		} else {
			$this->response->addHeader('X-Status', _t('LeftAndMain.SAVEDUP'));
			
			// Reload form, data and actions might have changed
			$form = $this->getEditForm($record->ID);
		}
		
		return $form->forTemplate();
	}


	public function doAdd($data, $form) {
		$className = isset($data['PageType']) ? $data['PageType'] : "Page";
		$parentID = isset($data['ParentID']) ? (int)$data['ParentID'] : 0;
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
		
		$form = $this->getEditForm($record->ID);
		
		if(isset($data['returnID'])) {
			return $record->ID;
		} else if(Director::is_ajax()) {
			$form = $this->getEditForm($record->ID);
			return $form->forTemplate();
		} else {
			return $this->redirect(Controller::join_links($this->Link('show'), $record->ID));
		}
	}

	/**
	 * @uses LeftAndMainExtension->augmentNewSiteTreeItem()
	 */
	public function getNewItem($id, $setID = true) {
		list($dummy, $className, $parentID, $suffix) = array_pad(explode('-',$id),4,null);
		
		$newItem = new $className();

	    if( !$suffix ) {
			$sessionTag = "NewItems." . $parentID . "." . $className;
    		if(Session::get($sessionTag)) {
		    	$suffix = '-' . Session::get($sessionTag);
		    	Session::set($sessionTag, Session::get($sessionTag) + 1);
		    }
		    else
		    	Session::set($sessionTag, 1);

		    	$id = $id . $suffix;
	    }

		$newItem->Title = _t('CMSMain.NEW',"New ",PR_MEDIUM,'"New " followed by a className').$className;
		$newItem->URLSegment = "new-" . strtolower($className);
		$newItem->ClassName = $className;
		$newItem->ParentID = $parentID;

		// DataObject::fieldExists only checks the current class, not the hierarchy
		// This allows the CMS to set the correct sort value
		if($newItem->castingHelper('Sort')) {
			$newItem->Sort = DB::query("SELECT MAX(\"Sort\") FROM \"SiteTree\" WHERE \"ParentID\" = '" . Convert::raw2sql($parentID) . "'")->value() + 1;
		}

		if($setID) $newItem->ID = $id;

		# Some modules like subsites add extra fields that need to be set when the new item is created
		$this->extend('augmentNewSiteTreeItem', $newItem);
		
		return $newItem;
	}

	/**
	 * Delete the page from live. This means a page in draft mode might still exist.
	 * 
	 * @see delete()
	 */
	public function deletefromlive($data, $form) {
		Versioned::reading_stage('Live');
		$record = DataObject::get_by_id("SiteTree", $data['ID']);
		if($record && !($record->canDelete() && $record->canDeleteFromLive())) return Security::permissionFailure($this);
		
		$descRemoved = '';
		$descendantsRemoved = 0;
		
		// before deleting the records, get the descendants of this tree
		if($record) {
			$descendantIDs = $record->getDescendantIDList();

			// then delete them from the live site too
			$descendantsRemoved = 0;
			foreach( $descendantIDs as $descID )
				if( $descendant = DataObject::get_by_id('SiteTree', $descID) ) {
					$descendant->doDeleteFromLive();
					$descendantsRemoved++;
				}

			// delete the record
			$record->doDeleteFromLive();
		}

		Versioned::reading_stage('Stage');

		if(isset($descendantsRemoved)) {
			$descRemoved = " and $descendantsRemoved descendants";
			$descRemoved = sprintf(' '._t('CMSMain.DESCREMOVED', 'and %s descendants'), $descendantsRemoved);
		} else {
			$descRemoved = '';
		}

		$this->response->addHeader(
			'X-Status',
			sprintf(
				_t('CMSMain.REMOVED', 'Deleted \'%s\'%s from live site'), 
				$record->Title, 
				$descRemoved
			)
		);

		// nothing to return
		return '';
	}

	/**
	 * Actually perform the publication step
	 */
	public function performPublish($record) {
		if($record && !$record->canPublish()) return Security::permissionFailure($this);
		
		$record->doPublish();
	}

	/**
 	 * Reverts a page by publishing it to live.
 	 * Use {@link restorepage()} if you want to restore a page
 	 * which was deleted from draft without publishing.
 	 * 
 	 * @uses SiteTree->doRevertToLive()
	 */
	public function revert($data, $form) {
		if(!isset($data['ID'])) return new SS_HTTPResponse("Please pass an ID in the form content", 400);
		
		$restoredPage = Versioned::get_latest_version("SiteTree", $data['ID']);
		if(!$restoredPage) 	return new SS_HTTPResponse("SiteTree #$id not found", 400);
		
		$record = Versioned::get_one_by_stage(
			'SiteTree', 
			'Live', 
			sprintf("\"SiteTree_Live\".\"ID\" = '%d'", (int)$data['ID'])
		);

		// a user can restore a page without publication rights, as it just adds a new draft state
		// (this action should just be available when page has been "deleted from draft")
		if(isset($record) && $record && !$record->canEdit()) {
			return Security::permissionFailure($this);
		}

		$record->doRevertToLive();
		
		$this->response->addHeader(
			'X-Status',
			sprintf(
				_t('CMSMain.RESTORED',"Restored '%s' successfully",PR_MEDIUM,'Param %s is a title'),
				$record->Title
			)
		);
		
		$form = $this->getEditForm($record->ID);
		
		return $form->forTemplate();
	}
	
	/**
	 * Delete the current page from draft stage.
	 * @see deletefromlive()
	 */
	public function delete($data, $form) {
		$record = DataObject::get_one(
			"SiteTree", 
			sprintf("\"SiteTree\".\"ID\" = %d", Convert::raw2sql($data['ID']))
		);
		if($record && !$record->canDelete()) return Security::permissionFailure();
		
		// save ID and delete record
		$recordID = $record->ID;
		$record->delete();
		
		$this->response->addHeader(
			'X-Status',
			sprintf(
				_t('CMSMain.REMOVEDPAGEFROMDRAFT',"Removed '%s' from the draft site"),
				$record->Title
			)
		);
		
		if($this->isAjax()) {
			// need a valid ID value even if the record doesn't have one in the database
			// (its still present in the live tables)
			$liveRecord = Versioned::get_one_by_stage(
				'SiteTree', 
				'Live', 
				"\"SiteTree_Live\".\"ID\" = $recordID"
			);
			return ($liveRecord) ? $form->forTemplate() : "";
		} else {
			$this->redirectBack();
		}
	}
	
	/**
	 * Return the CMS's HTML-editor toolbar
	 */
	public function EditorToolbar() {
		return Object::create('HtmlEditorField_Toolbar', $this, "EditorToolbar");
	}
	
	/**
	 * @return Array
	 */
	function SideReports() {
		return SS_Report::get_reports('SideReport');
	}
	
	/**
	 * @return Form
	 */
	function SideReportsForm() {
		$record = $this->currentPage();
		
		foreach($this->SideReports() as $report) {
			if($report->canView()) {
			 	$options[$report->group()][$report->sort()][$report->ID()] = $report->title();
			}
		}
		
		$finalOptions = array();
		foreach($options as $group => $weights) {
			ksort($weights);
			foreach($weights as $weight => $reports) {
				foreach($reports as $class => $report) {
					$finalOptions[$group][$class] = $report;
				}
			}
		}
		
		$selectorField = new GroupedDropdownField("ReportClass", _t('CMSMain.REPORT', 'Report'),$finalOptions);
		
		$form = new Form(
			$this,
			'SideReportsForm',
			new FieldList(
				$selectorField,
				new HiddenField('ID', false, ($record) ? $record->ID : null),
				new HiddenField('Locale', false, $this->Locale)
			),
			new FieldList(
				new FormAction('doShowSideReport', _t('CMSMain_left.ss.GO','Go'))
			)
		);
		$form->unsetValidator();
		
		$this->extend('updateSideReportsForm', $form);
		
		return $form;
	}
	
	/**
	 * Generate the parameter HTML for SideReports that have params
	 *
	 * @return LiteralField
	 */
	function ReportFormParameters() {
		$forms = array();
		foreach($this->SideReports() as $report) {
			if ($report->canView()) {
				if ($fieldset = $report->parameterFields()) {
					$formHtml = '';
					foreach($fieldset as $field) {
						$formHtml .= $field->FieldHolder();
					}
					$forms[$report->ID()] = $formHtml;
				}
			}
		}
		$pageHtml = '';
		foreach($forms as $class => $html) {
			$pageHtml .= "<div id=\"SideReportForm_$class\" style=\"display:none\">$html</div>\n\n";
		} 
		return new LiteralField("ReportFormParameters", '<div id="SideReportForms" style="display:none">'.$pageHtml.'</div>');
	}
	
	/**
	 * @return Form
	 */
	function doShowSideReport($data, $form) {
		$reportClass = (isset($data['ReportClass'])) ? $data['ReportClass'] : $this->urlParams['ID'];
		$reports = $this->SideReports();
		if(isset($reports[$reportClass])) {
			$report = $reports[$reportClass];
			if($report) {
				$view = new SideReportView($this, $report);
				$view->setParameters($this->request->requestVars());
				return $view->forTemplate();
			} else {
				return false;
			}
		}
	}

	function publish($data, $form) {
		$data['publish'] = '1';
		
		return $this->save($data, $form);
	}

	function unpublish($data, $form) {
		$className = $this->stat('tree_class');
		$record = DataObject::get_by_id($className, $data['ID']);
		
		if($record && !$record->canDeleteFromLive()) return Security::permissionFailure($this);
		
		$record->doUnpublish();
		
		$this->response->addHeader(
			'X-Status',
			sprintf(_t('CMSMain.REMOVEDPAGE',"Removed '%s' from the published site"),$record->Title)
		);
		
		// Reload form, data and actions might have changed
		$form = $this->getEditForm($record->ID);
		
		return $form->forTemplate();
	}

	function sendFormToBrowser($templateData) {
		if(Director::is_ajax()) {
			SSViewer::setOption('rewriteHashlinks', false);
			$result = $this->customise($templateData)->renderWith($this->class . '_right');
			$parts = split('</?form[^>]*>', $result);
			return $parts[sizeof($parts)-2];
		} else {
			return array(
				"Right" => $this->customise($templateData)->renderWith($this->class . '_right'),
			);
		}
	}

	/**
	 * Batch Actions Handler
	 */
	function batchactions() {
		return new CMSBatchActionHandler($this, 'batchactions');
	}
	
	function BatchActionParameters() {
		$batchActions = CMSBatchActionHandler::$batch_actions;

		$forms = array();
		foreach($batchActions as $urlSegment => $batchAction) {
			$SNG_action = singleton($batchAction);
			if ($SNG_action->canView() && $fieldset = $SNG_action->getParameterFields()) {
				$formHtml = '';
				foreach($fieldset as $field) {
					$formHtml .= $field->Field();
				}
				$forms[$urlSegment] = $formHtml;
			}
		}
		$pageHtml = '';
		foreach($forms as $urlSegment => $html) {
			$pageHtml .= "<div class=\"params\" id=\"BatchActionParameters_$urlSegment\">$html</div>\n\n";
		} 
		return new LiteralField("BatchActionParameters", '<div id="BatchActionParameters" style="display:none">'.$pageHtml.'</div>');
	}
	/**
	 * Returns a list of batch actions
	 */
	function BatchActionList() {
		return $this->batchactions()->batchActionList();
	}
	
	function buildbrokenlinks($request) {
		// Protect against CSRF on destructive action
		if(!SecurityToken::inst()->checkRequest($request)) return $this->httpError(400);
		
		if($this->urlParams['ID']) {
			$newPageSet[] = DataObject::get_by_id("Page", $this->urlParams['ID']);
		} else {
			$pages = DataObject::get("Page");
			foreach($pages as $page) $newPageSet[] = $page;
			$pages = null;
		}

		$content = new HtmlEditorField('Content');
		$download = new HtmlEditorField('Download');

		foreach($newPageSet as $i => $page) {
			$page->HasBrokenLink = 0;
			$page->HasBrokenFile = 0;

			$lastUsage = (memory_get_usage() - $lastPoint);
			$lastPoint = memory_get_usage();
			$content->setValue($page->Content);
			$content->saveInto($page);

			$download->setValue($page->Download);
			$download->saveInto($page);

			echo "<li>$page->Title (link:$page->HasBrokenLink, file:$page->HasBrokenFile)";

			$page->writeWithoutVersion();
			$page->destroy();
			$newPageSet[$i] = null;
		}
	}

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
		
		$fields = new FieldList(
			// new HiddenField("ParentID", false, ($this->parentRecord) ? $this->parentRecord->ID : null),
			// TODO Should be part of the form attribute, but not possible in current form API
			$hintsField = new LiteralField('Hints', sprintf('<span class="hints" data-hints="%s"></span>', $this->SiteTreeHints())),
			$parentField = new TreeDropdownField("ParentID", _t('CMSMain.AddFormParentLabel', 'Parent page'), 'SiteTree'),
			new OptionsetField("PageType", "", $pageTypes, 'Page')
		);
		$parentField->setValue(($record) ? $record->ID : null);
		
		$actions = new FieldList(
			// $resetAction = new ResetFormAction('doCancel', _t('CMSMain.Cancel', 'Cancel')),
			$createAction = new FormAction("doAdd", _t('CMSMain.Create',"Create"))
		);
		// $resetAction->addExtraClass('ss-ui-action-destructive');
		$createAction->addExtraClass('ss-ui-action-constructive');
		
		$this->extend('updatePageOptions', $fields);
		
		$form = new Form($this, "AddForm", $fields, $actions);
		$form->addExtraClass('cms-add-form');
		
		return $form;
	}
	
	/**
	 * Helper function to get page count
	 */
	function getpagecount() {
		ini_set('max_execution_time', 0);
		$excludePages = split(" *, *", $_GET['exclude']);

		$pages = DataObject::get("SiteTree", "\"ParentID\" = 0");
		foreach($pages as $page) $pageArr[] = $page;

		while(list($i,$page) = each($pageArr)) {
			if(!in_array($page->URLSegment, $excludePages)) {
				if($children = $page->AllChildren()) {
					foreach($children as $child) $pageArr[] = $child;
				}


				if(!$_GET['onlywithcontent'] || strlen(Convert::xml2raw($page->Content)) > 100) {
					echo "<li>" . $page->Breadcrumbs(null, true) . "</li>";
					$count++;
				} else {
					echo "<li style=\"color: #777\">" . $page->Breadcrumbs(null, true) . " - " . _t('CMSMain.NOCONTENT',"no content") . "</li>";
				}

			}
		}

		echo '<p>' . _t('CMSMain.TOTALPAGES',"Total pages: ") . "$count</p>";
	}

	function publishall($request) {
		ini_set("memory_limit", -1);
		ini_set('max_execution_time', 0);
		
		$response = "";

		if(isset($this->requestParams['confirm'])) {
			// Protect against CSRF on destructive action
			if(!SecurityToken::inst()->checkRequest($request)) return $this->httpError(400);
			
			$start = 0;
			$pages = DataObject::get("SiteTree", "", "", "", "$start,30");
			$count = 0;
			while($pages) {
				foreach($pages as $page) {
					if($page && !$page->canPublish()) return Security::permissionFailure($this);
					
					$page->doPublish();
					$page->destroy();
					unset($page);
					$count++;
					$response .= "<li>$count</li>";
				}
				if($pages->Count() > 29) {
					$start += 30;
					$pages = DataObject::get("SiteTree", "", "", "", "$start,30");
				} else {
					break;
				}
			}
			$response .= sprintf(_t('CMSMain.PUBPAGES',"Done: Published %d pages"), $count);

		} else {
			$token = SecurityToken::inst();
			$fields = new FieldList();
			$token->updateFieldSet($fields);
			$tokenField = $fields->First();
			$tokenHtml = ($tokenField) ? $tokenField->FieldHolder() : '';
			$response .= '<h1>' . _t('CMSMain.PUBALLFUN','"Publish All" functionality') . '</h1>
				<p>' . _t('CMSMain.PUBALLFUN2', 'Pressing this button will do the equivalent of going to every page and pressing "publish".  It\'s
				intended to be used after there have been massive edits of the content, such as when the site was
				first built.') . '</p>
				<form method="post" action="publishall">
					<input type="submit" name="confirm" value="'
					. _t('CMSMain.PUBALLCONFIRM',"Please publish every page in the site, copying content stage to live",PR_LOW,'Confirmation button') .'" />'
					. $tokenHtml .
				'</form>';
		}
		
		return $response;
	}
	
	/**
	 * Restore a completely deleted page from the SiteTree_versions table.
	 */
	function restore($data, $form) {
		if(!isset($data['ID']) || !is_numeric($data['ID'])) {
			return new SS_HTTPResponse("Please pass an ID in the form content", 400);
		}
		
		$id = (int)$data['ID'];
		$restoredPage = Versioned::get_latest_version("SiteTree", $id);
		if(!$restoredPage) 	return new SS_HTTPResponse("SiteTree #$id not found", 400);
		
		$restoredPage = $restoredPage->doRestoreToStage();
		
		$this->response->addHeader(
			'X-Status',
			sprintf(
				_t('CMSMain.RESTORED',"Restored '%s' successfully",PR_MEDIUM,'Param %s is a title'),
				$restoredPage->TreeTitle
			)
		);
		
		// Reload form, data and actions might have changed
		$form = $this->getEditForm($restoredPage->ID);
		
		return $form->forTemplate();
	}

	function duplicate($request) {
		// Protect against CSRF on destructive action
		if(!SecurityToken::inst()->checkRequest($request)) return $this->httpError(400);
		
		if(($id = $this->urlParams['ID']) && is_numeric($id)) {
			$page = DataObject::get_by_id("SiteTree", $id);
			if($page && (!$page->canEdit() || !$page->canCreate())) {
				return Security::permissionFailure($this);
			}

			$newPage = $page->duplicate();
			
			// ParentID can be hard-set in the URL.  This is useful for pages with multiple parents
			if($_GET['parentID'] && is_numeric($_GET['parentID'])) {
				$newPage->ParentID = $_GET['parentID'];
				$newPage->write();
			}
			
			// Reload form, data and actions might have changed
			$form = $this->getEditForm($newPage->ID);
			
			return $form->forTemplate();
		} else {
			user_error("CMSMain::duplicate() Bad ID: '$id'", E_USER_WARNING);
		}
	}

	function duplicatewithchildren($request) {
		// Protect against CSRF on destructive action
		if(!SecurityToken::inst()->checkRequest($request)) return $this->httpError(400);
		
		if(($id = $this->urlParams['ID']) && is_numeric($id)) {
			$page = DataObject::get_by_id("SiteTree", $id);
			if($page && (!$page->canEdit() || !$page->canCreate())) {
				return Security::permissionFailure($this);
			}

			$newPage = $page->duplicateWithChildren();

			// Reload form, data and actions might have changed
			$form = $this->getEditForm($newPage->ID);
			
			return $form->forTemplate();
		} else {
			user_error("CMSMain::duplicate() Bad ID: '$id'", E_USER_WARNING);
		}
	}
	
	/**
	 * Return the version number of this application.
	 * Uses the subversion path information in <mymodule>/silverstripe_version
	 * (automacially replaced $URL$ placeholder).
	 * 
	 * @return string
	 */
	public function CMSVersion() {
		$sapphireVersionFile = file_get_contents(BASE_PATH . '/sapphire/silverstripe_version');
		$cmsVersionFile = file_get_contents(BASE_PATH . '/cms/silverstripe_version');
		
		$sapphireVersion = $this->versionFromVersionFile($sapphireVersionFile);
		$cmsVersion = $this->versionFromVersionFile($cmsVersionFile);

		return "cms: $cmsVersion, sapphire: $sapphireVersion";
	}

	/**
	 * Provide the permission codes used by LeftAndMain.
	 * Can't put it on LeftAndMain since that's an abstract base class.
	 */
	function providePermissions() {
		$classes = ClassInfo::subclassesFor('LeftAndMain');

		foreach($classes as $i => $class) {
			$title = _t("{$class}.MENUTITLE", LeftAndMain::menu_title_for_class($class));
			$perms["CMS_ACCESS_" . $class] = array(
				'name' => sprintf(_t(
					'CMSMain.ACCESS', 
					"Access to '%s' section",
					PR_MEDIUM,
					"Item in permission selection identifying the admin section. Example: Access to 'Files & Images'"
				), $title, null),
				'category' => _t('Permission.CMS_ACCESS_CATEGORY', 'CMS Access')
			);
		}
		$perms["CMS_ACCESS_LeftAndMain"] = array(
			'name' => _t('CMSMain.ACCESSALLINTERFACES', 'Access to all CMS sections'),
			'category' => _t('Permission.CMS_ACCESS_CATEGORY', 'CMS Access'),
			'help' => _t('CMSMain.ACCESSALLINTERFACESHELP', 'Overrules more specific access settings.'),
			'sort' => -100
		);

		$perms['CMS_ACCESS_CMSMain']['help'] = _t(
			'CMSMain.ACCESS_HELP',
			'Allow viewing of the section containing page tree and content. View and edit permissions can be handled through page specific dropdowns, as well as the separate "Content permissions".'
		);
		$perms['CMS_ACCESS_SecurityAdmin']['help'] = _t(
			'SecurityAdmin.ACCESS_HELP',
			'Allow viewing, adding and editing users, as well as assigning permissions and roles to them.'
		);

		if (isset($perms['CMS_ACCESS_ModelAdmin'])) unset($perms['CMS_ACCESS_ModelAdmin']);

		return $perms;
	}

}
