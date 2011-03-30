<?php
/**
 * The main "content" area of the CMS.
 * This class creates a 2-frame layout - left-tree and right-form - to sit beneath the main
 * admin menu.
 * 
 * @package cms
 * @subpackage content
 * @todo Create some base classes to contain the generic functionality that will be replicated.
 */
class CMSMain extends LeftAndMain implements CurrentPageIdentifier, PermissionProvider {
	
	static $url_segment = '';
	
	static $url_rule = '/$Action/$ID/$OtherID';
	
	// Maintain a lower priority than other administration sections
	// so that Director does not think they are actions of CMSMain
	static $url_priority = 40;
	
	static $menu_title = 'Pages';
	
	static $menu_priority = 10;
	
	static $tree_class = "SiteTree";
	
	static $subitem_class = "Member";
	
	static $allowed_actions = array(
		'addpage',
		'buildbrokenlinks',
		'compareversions',
		'deleteitems',
		'DeleteItemsForm',
		'dialog',
		'duplicate',
		'duplicatewithchildren',
		'getversion',
		'publishall',
		'publishitems',
		'PublishItemsForm',
		'RootForm',
		'sidereport',
		'SideReportsForm',
		'submit',
		'versions',
		'VersionsForm',
		'EditForm',
		'AddForm',
		'SearchTreeForm',
		'SiteTreeAsUL',
		'getshowdeletedsubtree',
		'getfilteredsubtree',
		'batchactions',
	);
	
	/**
	 * SiteTree Columns that can be filtered using the the Site Tree Search button
	 */
	static $site_tree_filter_options = array(
		'Title' => array('CMSMain.TITLE', 'Title'),
		'MenuTitle' => array('CMSMain.MENUTITLE', 'Navigation Label'),
		'ClassName' => array('CMSMain.PAGETYPE', 'Page Type'), 
		'Status' => array('CMSMain.STATUS', 'Status'),
		'MetaDescription' => array('CMSMain.METADESC', 'Description'),
		'MetaKeywords' => array('CMSMain.METAKEYWORDS', 'Keywords')
	);
	
	static function T_SiteTreeFilterOptions(){
		return array(
			'Title' => _t('CMSMain.TITLEOPT', 'Title', 0, 'The dropdown title in CMSMain left SiteTreeFilterOptions'),
			'MenuTitle' => _t('CMSMain.MENUTITLEOPT', 'Navigation Label', 0, 'The dropdown title in CMSMain left SiteTreeFilterOptions'),
			'Status' => _t('CMSMain.STATUSOPT', 'Status',  0, "The dropdown title in CMSMain left SiteTreeFilterOptions"), 
			'MetaDescription' => _t('CMSMain.METADESCOPT', 'Description', 0, "The dropdown title in CMSMain left SiteTreeFilterOptions"), 
			'MetaKeywords' => _t('CMSMain.METAKEYWORDSOPT', 'Keywords', 0, "The dropdown title in CMSMain left SiteTreeFilterOptions")
		);
	}
	
	public function init() {
		// set reading lang
		if(Object::has_extension('SiteTree', 'Translatable') && !$this->isAjax()) {
			Translatable::choose_site_locale(array_keys(Translatable::get_existing_content_languages('SiteTree')));
		}
		
		parent::init();
				
		Requirements::javascript(CMS_DIR . '/javascript/CMSMain.js');
		Requirements::javascript(CMS_DIR . '/javascript/CMSMain.EditForm.js');
		Requirements::add_i18n_javascript(CMS_DIR . '/javascript/lang');
		
		Requirements::css(CMS_DIR . '/css/CMSMain.css');
		
		// navigator
		Requirements::css(CMS_DIR . '/css/SilverStripeNavigator.css');
		Requirements::javascript(CMS_DIR . '/javascript/SilverStripeNavigator.js');
		
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
		$this->generateDataTreeHints();
		$this->generateTreeStylingJS();

		// Pre-cache sitetree version numbers for querying efficiency
		Versioned::prepopulate_versionnumber_cache("SiteTree", "Stage");
		Versioned::prepopulate_versionnumber_cache("SiteTree", "Live");

		return $this->getSiteTreeFor($this->stat('tree_class'));
	}
	
	/**
	 * Use a CMSSiteTreeFilter to only get certain nodes
	 *
	 * @return string
	 */
	public function getfilteredsubtree() {
		// Sanity and security checks
		if (!isset($_REQUEST['filter'])) die('No filter passed');
		if (!ClassInfo::exists($_REQUEST['filter'])) die ('That filter class does not exist');
		if (!is_subclass_of($_REQUEST['filter'], 'CMSSiteTreeFilter')) die ('That is not a valid filter');
		
		// Do eeet!
		$filter = new $_REQUEST['filter']();
		return $filter->getTree();
	}
	
	/**
	 * Returns a list of batch actions
	 */
	function SiteTreeFilters() {
		$filters = ClassInfo::subclassesFor('CMSSiteTreeFilter');
		array_shift($filters);
		$doSet = new DataObjectSet();
		$doSet->push(new ArrayData(array(
			'ClassName' => 'all',
			'Title' => _t('CMSSiteTreeFilter.ALL', 'All items')
		)));
		foreach($filters as $filter) {
			if (call_user_func(array($filter, 'showInList'))) {
				$doSet->push(new ArrayData(array(
					'ClassName' => $filter,
					'Title' => call_user_func(array($filter, 'title'))
				)));
			}
		}
		return $doSet;
	}
	
	/**
	 * Returns the SiteTree columns that can be filtered using the the Site Tree Search button as a DataObjectSet
	 */
	public function SiteTreeFilterOptions() {
		$filter_options = new DataObjectSet();
		foreach(self::T_SiteTreeFilterOptions() as $key => $value) {
   			$record = array(
				'Column' => $key,
				'Title' => $value,
			);
			$filter_options->push(new ArrayData($record));
		}
		return $filter_options;
	}
		public function SiteTreeFilterDateField() {
			$dateField = new DateField('SiteTreeFilterDate');
			
			// TODO Enabling this means we load jQuery UI by default in the CMS,
			// which is a pretty big performance hit in 2.4 (where the library isn't used for other parts
			// of the interface).
			// $dateField->setConfig('showcalendar', true);
			
			return $dateField->Field();
		}
		public function SiteTreeFilterPageTypeField() {
			$types = SiteTree::page_type_classes(); array_unshift($types, 'All');
			$source = array_combine($types, $types);
			asort($source);
			$optionsetField = new DropdownField('ClassName', 'ClassName', $source, 'Any');
			return $optionsetField->Field();
		}	

	public function generateDataTreeHints() {
		$classes = ClassInfo::subclassesFor( $this->stat('tree_class') );

		$def['Root'] = array();

		foreach($classes as $class) {
			$obj = singleton($class);
			if($obj instanceof HiddenClass) continue;

			$allowedChildren = $obj->allowedChildren();
			if($allowedChildren != "none")  $def[$class]['allowedChildren'] = $allowedChildren;
			$def[$class]['defaultChild'] = $obj->defaultChild();
			$def[$class]['defaultParent'] = isset(SiteTree::get_by_link($obj->defaultParent())->ID) ? SiteTree::get_by_link($obj->defaultParent())->ID : null;

			if(is_array($allowedChildren)) foreach($allowedChildren as $allowedChild) {
				$def[$allowedChild]['allowedParents'][] = $class;
			}

			if($obj->stat('can_be_root')) {
				$def['Root']['allowedChildren'][] = $class;
			}
		}

		// Put data hints into a script tag at the top
		Requirements::customScript("siteTreeHints = " . Convert::raw2json($def) . ";");
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

		$result = new DataObjectSet();

		foreach($classes as $class) {
			$instance = singleton($class);

			if($instance instanceof HiddenClass) continue;

			if(!$instance->canCreate()) continue;

			// skip this type if it is restricted
			if($instance->stat('need_permission') && !$this->can(singleton($class)->stat('need_permission'))) continue;

			$addAction = $instance->i18n_singular_name();
			
			// if we're in translation mode, the link between the translated pagetype
			// title and the actual classname might not be obvious, so we add it in parantheses
			// Example: class "RedirectorPage" has the title "Weiterleitung" in German,
			// so it shows up as "Weiterleitung (RedirectorPage)"
			if(i18n::get_locale() != 'en_US') {
				$addAction .= " ({$class})";
			}

			$result->push(new ArrayData(array(
				'ClassName' => $class,
				'AddAction' => $addAction,
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
	
		return $form->formHtmlContent();
	}
	/**
	 * Get a database record to be managed by the CMS
	 */
 	public function getRecord($id) {
		$treeClass = $this->stat('tree_class');

		if($id instanceof $treeClass) {
			return $id;
		} else if($id && is_numeric($id)) {
			$version = isset($_REQUEST['Version']) ? $_REQUEST['Version'] : null;
			if(is_numeric($version)) {
				$record = Versioned::get_version($treeClass, $id, $version);
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
	 * Calls {@link SiteTree->getCMSFields()}
	 */
	public function getEditForm($id = null) {
		// Include JavaScript to ensure HtmlEditorField works.
		HtmlEditorField::include_js();

		if(!$id) $id = $this->currentPageID();
		$form = parent::getEditForm($id);
		
		// TODO Duplicate record fetching (see parent implementation)
		$record = $this->getRecord($id);
		if($record && !$record->canView()) return Security::permissionFailure($this);

		$fields = $form->Fields();
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
				$stageURLField->setValue($record->AbsoluteLink());
			}
			
			// getAllCMSActions can be used to completely redefine the action list
			if($record->hasMethod('getAllCMSActions')) {
				$actions = $record->getAllCMSActions();
			} else {
				$actions = $record->getCMSActions();
			}
			
			// Add a default or custom validator.
			// @todo Currently the default Validator.js implementation
			//  adds javascript to the document body, meaning it won't
			//  be included properly if the associated fields are loaded
			//  through ajax. This means only serverside validation
			//  will kick in for pages+validation loaded through ajax.
			//  This will be solved by using less obtrusive javascript validation
			//  in the future, see http://open.silverstripe.com/ticket/2915 and http://open.silverstripe.com/ticket/3386
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
			$form->disableDefaultAction();

			if(!$record->canEdit() || $record->IsDeletedFromStage) {
				$readonlyFields = $form->Fields()->makeReadonly();
				$form->setFields($readonlyFields);
			}
			
			$this->extend('updateEditForm', $form);

			return $form;
		} if ($id == 0 || $id == 'root') {
			return $this->RootForm();
		} else if($id) {
			return new Form($this, "EditForm", new FieldSet(
				new LabelField('PageDoesntExistLabel',_t('CMSMain.PAGENOTEXISTS',"This page doesn't exist"))), new FieldSet()
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
		$form->setHTMLID('Form_EditForm');
		$form->loadDataFrom($siteConfig);

		$this->extend('updateEditForm', $form);

		return $form;
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
		if($data['ClassName'] != $record->ClassName) {
			$newClassName = $record->ClassName;
			// The records originally saved attribute was overwritten by $form->saveInto($record) before.
			// This is necessary for newClassInstance() to work as expected, and trigger change detection
			// on the ClassName attribute
			$record->setClassName($data['ClassName']);
			// Replace $record with a new instance
			$record = $record->newClassInstance($newClassName);
		}

		// save form data into record
		$form->saveInto($record, true);
		$record->write();
		
		// If the 'Save & Publish' button was clicked, also publish the page
		if (isset($data['publish']) && $data['publish'] == 1) {
			$record->doPublish();
			
			// Update classname with original and get new instance (see above for explanation)
			$record->setClassName($data['ClassName']);
			$publishedRecord = $record->newClassInstance($record->ClassName);
			
			$this->response->addHeader(
				'X-Status',
				sprintf(
					_t(
						'LeftAndMain.STATUSPUBLISHEDSUCCESS', 
						"Published '%s' successfully",
						PR_MEDIUM,
						'Status message after publishing a page, showing the page title'
					),
					$publishedRecord->Title
				)
			);
		
			// Reload form, data and actions might have changed
			$form = $this->getEditForm($publishedRecord->ID);
		} else {
			$this->response->addHeader('X-Status', _t('LeftAndMain.SAVEDUP'));
			
			// Reload form, data and actions might have changed
			$form = $this->getEditForm($record->ID);
		}
		
		return $form->formHtmlContent();
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
			return $form->formHtmlContent();
		} else {
			return $this->redirect(Controller::join_links($this->Link('show'), $record->ID));
		}
	}

	/**
	 * @uses LeftAndMainDecorator->augmentNewSiteTreeItem()
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
		
		return $form->formHtmlContent();
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
			return ($liveRecord) ? $form->formHtmlContent() : "";
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
			new FieldSet(
				$selectorField,
				new HiddenField('ID', false, ($record) ? $record->ID : null),
				new HiddenField('Locale', false, $this->Locale)
			),
			new FieldSet(
				new FormAction('doShowSideReport', _t('CMSMain_left.ss.GO','Go'))
			)
		);
		$form->unsetValidator();
		$form->addExtraClass('oneline');
		
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

	/**
	 * @return Form
	 */
	function VersionsForm() {
		$pageID = ($this->request->requestVar('ID')) ? $this->request->requestVar('ID') : $this->currentPageID();
		$page = $this->getRecord($pageID);
		if($page) {
			$versions = $page->allVersions(
				($this->request->requestVar('ShowUnpublished')) ? 
				"" : "\"SiteTree\".\"WasPublished\" = 1"
			);

			// inject link to cms
			if($versions) foreach($versions as $k => $version) {
				$version->CMSLink = sprintf('%s/%s/%s',
					$this->Link('getversion'),
					$version->ID,
					$version->Version
				);
			}
			$vd = new ViewableData();
			$versionsHtml = $vd->customise(
				array('Versions'=>$versions)
			)->renderWith('CMSMain_versions');
		} else {
			$versionsHtml = '';
		}
		
		$form = new Form(
			$this,
			'VersionsForm',
			new FieldSet(
				new CheckboxField(
					'ShowUnpublished',
					_t('CMSMain_left.ss.SHOWUNPUB','Show unpublished versions')
				),
				new LiteralField('VersionsHtml', $versionsHtml),
				new HiddenField('ID', false, $pageID),
				new HiddenField('Locale', false, $this->Locale)
			),
			new FieldSet(
				new FormAction(
					'versions',
					_t('CMSMain.BTNREFRESH','Refresh')
				),
				new FormAction(
					'compareversions',  
					_t('CMSMain.BTNCOMPAREVERSIONS','Compare Versions')
				)
			)
		);
		$form->loadDataFrom($this->request->requestVars());
		$form->setFormMethod('GET');
		$form->unsetValidator();
		
		return $form;
	}
	
	/**
	 * Get the versions of the current page
	 */
	function versions() {
		$form = $this->VersionsForm();
		return (Director::is_ajax()) ? $form->forTemplate() : $form;
	}

	/**
	 * Roll a page back to a previous version
	 */
	function rollback($data, $form) {
		$this->extend('onBeforeRollback', $data['ID']);
		
		if(isset($data['Version']) && (bool)$data['Version']) {
			$record = $this->performRollback($data['ID'], $data['Version']);
			$message = sprintf(
			_t('CMSMain.ROLLEDBACKVERSION',"Rolled back to version #%d.  New version number is #%d"),
			$data['Version'],
			$record->Version
		);
		} else {
			$record = $this->performRollback($data['ID'], "Live");
			$message = sprintf(
				_t('CMSMain.ROLLEDBACKPUB',"Rolled back to published version. New version number is #%d"),
				$record->Version
			);
		}
		
		$this->response->addHeader('X-Status', $message);
		
		$form = $this->getEditForm($record->ID);
		
		return $form->formHtmlContent();
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
		
		return $form->formHtmlContent();
	}

	function performRollback($id, $version) {
		$record = DataObject::get_by_id($this->stat('tree_class'), $id);
		if($record && !$record->canEdit()) return Security::permissionFailure($this);
		
		$record->doRollbackTo($version);
		return $record;
	}

	/**
	 * Supports both direct URL links (format: admin/getversion/<page-id>/<version>),
	 * and through GET parameters: admin/getversion/?ID=<page-id>&Versions[]=<version>
	 */
	function getversion() {
		$id = ($this->request->param('ID')) ? 
			$this->request->param('ID') : $this->request->requestVar('ID');
		
		$version = ($this->request->param('OtherID')) ? 
			$this->request->param('OtherID') : $this->request->requestVar('Versions');
		
		$record = Versioned::get_version("SiteTree", $id, $version);
		
		if($record) {
			if($record && !$record->canView()) return Security::permissionFailure($this);
			$fields = $record->getCMSFields($this);
			$fields->removeByName("Status");

			$fields->push(new HiddenField("ID"));
			$fields->push(new HiddenField("Version"));
			
			$versionAuthor = DataObject::get_by_id('Member', $record->AuthorID);
			if(!$versionAuthor) $versionAuthor = new ArrayData(array('Title' => 'Unknown author'));
			$fields->insertBefore(
				new LiteralField(
					'YouAreViewingHeader', 
					'<p class="message notice">' .
					sprintf(
						_t(
							'CMSMain.VIEWING',
							"You are viewing version #%s, created %s by %s",
							PR_MEDIUM,
							'Version number is a linked string, created is a relative time (e.g. 2 days ago), by a specific author'
						),
						"<a href=\"admin/getversion/$record->ID/$version\" title=\"" . ($versionAuthor ? $versionAuthor->Title : '') . "\">$version</a>", 
						$record->obj('LastEdited')->Ago(),
						($versionAuthor ? $versionAuthor->Title : '')
					) .
					'</p>'
				),
				'Root'
			);

			$actions = $record->getCMSActions();

			// encode the message to appear in the body of the email
			$archiveURL = Director::absoluteBaseURL() . $record->URLSegment . '?archiveDate=' . $record->obj('LastEdited')->URLDatetime();
			
			// Ensure that source file comments are disabled
			SSViewer::set_source_file_comments(false);
			
			$archiveEmailMessage = urlencode( $this->customise( array( 'ArchiveDate' => $record->obj('LastEdited'), 'ArchiveURL' => $archiveURL ) )->renderWith( 'ViewArchivedEmail' ) );
			$archiveEmailMessage = preg_replace( '/\+/', '%20', $archiveEmailMessage );

			$fields->push( new HiddenField( 'ArchiveEmailMessage', '', $archiveEmailMessage ) );
			$fields->push( new HiddenField( 'ArchiveEmailSubject', '', preg_replace( '/\+/', '%20', urlencode( 'Archived version of ' . $record->Title ) ) ) );
			$fields->push( new HiddenField( 'ArchiveURL', '', $archiveURL ) );

			$form = new Form($this, "EditForm", $fields, $actions);
			$form->loadDataFrom($record);
			$form->loadDataFrom(array(
				"ID" => $id,
				"Version" => $version,
			));
			
			// historical version shouldn't be editable
			$readonlyFields = $form->Fields()->makeReadonly();
			$form->setFields($readonlyFields);

			$templateData = $this->customise(array(
				"EditForm" => $form
			));

			SSViewer::setOption('rewriteHashlinks', false);
			
			if(Director::is_ajax()) {
				$result = $templateData->renderWith(array($this->class . '_right', 'LeftAndMain_right'));
				$parts = split('</?form[^>]*>', $result);
				$content = $parts[sizeof($parts)-2];
				if($this->ShowSwitchView()) {
					$content .= '<div id="AjaxSwitchView">' . $this->SwitchView($record) . '</div>';
				}
				return $content;
			} else {
				return $templateData->renderWith('LeftAndMain');
			}
		}
	}

	function compareversions() {
		$id = ($this->request->param('ID')) ? 
			$this->request->param('ID') : $this->request->requestVar('ID');
		
		$versions = $this->request->requestVar('Versions');
		$version1 = ($versions && isset($versions[0])) ? 
			$versions[0] : $this->request->getVar('From');
		$version2 = ($versions && isset($versions[1])) ? 
			$versions[1] : $this->request->getVar('To');

		if( $version1 > $version2 ) {
			$toVersion = $version1;
			$fromVersion = $version2;
		} else {
			$toVersion = $version2;
			$fromVersion = $version1;
		}
		
		if(!$toVersion || !$toVersion) return false;

		$page = DataObject::get_by_id("SiteTree", $id);
		if($page && !$page->canView()) return Security::permissionFailure($this);
		
		$record = $page->compareVersions($fromVersion, $toVersion);
		
		$fromVersionRecord = Versioned::get_version('SiteTree', $id, $fromVersion);
		$toVersionRecord = Versioned::get_version('SiteTree', $id, $toVersion);
		if(!$fromVersionRecord) user_error("Can't find version $fromVersion of page $id", E_USER_ERROR);
		if(!$toVersionRecord) user_error("Can't find version $toVersion of page $id", E_USER_ERROR);
		
		if($record) {
			$fromDateNice = $fromVersionRecord->obj('LastEdited')->Ago();
			$toDateNice = $toVersionRecord->obj('LastEdited')->Ago();
			$fromAuthor = DataObject::get_by_id('Member', $fromVersionRecord->AuthorID);
			if(!$fromAuthor) $fromAuthor = new ArrayData(array('Title' => 'Unknown author'));
			$toAuthor = DataObject::get_by_id('Member', $toVersionRecord->AuthorID);
			if(!$toAuthor) $toAuthor = new ArrayData(array('Title' => 'Unknown author'));

			$fields = $record->getCMSFields($this);
			$fields->push(new HiddenField("ID"));
			$fields->push(new HiddenField("Version"));
			$fields->insertBefore(
				new LiteralField(
					'YouAreComparingHeader',
					'<p class="message notice">' . 
					sprintf(
						_t('CMSMain.COMPARINGV',"Comparing versions %s and %s"),
						"<a href=\"admin/getversion/$id/$fromVersionRecord->Version\" title=\"$fromAuthor->Title\">$fromVersionRecord->Version</a> <small>($fromDateNice)</small>",
						"<a href=\"admin/getversion/$id/$toVersionRecord->Version\" title=\"$toAuthor->Title\">$toVersionRecord->Version</a> <small>($toDateNice)</small>"
					) .
					'</p>'
				), 
				"Root"
			);

			$actions = new FieldSet();

			$form = new Form($this, "EditForm", $fields, $actions);
			$form->loadDataFrom($record);
			$form->loadDataFrom(array(
				"ID" => $id,
				"Version" => $fromVersion,
			));
			$form->addExtraClass('compare');
			
			// comparison views shouldn't be editable
			$readonlyFields = $form->Fields()->makeReadonly();
			$form->setFields($readonlyFields);
			
			foreach($form->Fields()->dataFields() as $field) {
				$field->dontEscape = true;
			}

			if($this->isAjax()) {
				return $form->formHtmlContent();
			} else {
				$templateData = $this->customise(array(
					"EditForm" => $form
				));
				return $templateData->renderWith('LeftAndMain');
			}	
		}
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

	function AddForm() {
		$pageTypes = array();

		foreach( $this->PageTypes() as $arrayData ) {
			$pageTypes[$arrayData->getField('ClassName')] = $arrayData->getField('AddAction');
		}
		
		$fields = new FieldSet(
			new HiddenField("ParentID"),
			new DropdownField("PageType", "", $pageTypes, 'Page')
		);
		
		$this->extend('updatePageOptions', $fields);
		
		$actions = new FieldSet(
			new FormAction("doAdd", _t('CMSMain.GO',"Go"))
		);

		$form = new Form($this, "AddForm", $fields, $actions);
		$form->addExtraClass('actionparams');
		$form->addExtraClass('oneline');
		
		return $form;
	}

	/**
	 * Form used to filter the sitetree. It can only be used via javascript for now.
	 * 
	 * @return Form
	 */
	function SearchTreeForm() {
		// get all page types in a dropdown-compatible format
		$pageTypes = SiteTree::page_type_classes(); 
		array_unshift($pageTypes, 'All');
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

		$showDefaultFields = array();
		$form = new Form(
			$this,
			'SearchTreeForm',
			new FieldSet(
				$showDefaultFields[] = new DropdownField(
					'FilterClass', 
					_t('CMSMain.SearchTreeFormPagesDropdown', 'Pages'), 
					$filterMap
				),
				$showDefaultFields[] = new TextField(
					'Title', 
					_t('CMSMain.TITLEOPT', 'Title')
				),
				new TextField('Content', _t('CMSMain.TEXTOPT','Text', PR_MEDIUM, 'Text field for fulltext search in page content')),
				new DateField('EditedSince', _t('CMSMain_left.ss.EDITEDSINCE','Edited Since')),
				new DropdownField(
					'ClassName', 
					_t('CMSMain.PAGETYPEOPT','Page Type', PR_MEDIUM, 'Dropdown for limiting search to a page type'), 
					$pageTypes, 
					null, 
					null, 
					_t('CMSMain.PAGETYPEANYOPT','Any')
				),
				new TextField(
					'MenuTitle', 
					_t('CMSMain.MENUTITLEOPT', 'Navigation Label')
				),
				new TextField(
					'Status',
					_t('CMSMain.STATUSOPT', 'Status')
				),
				new TextField(
					'MetaDescription',
					_t('CMSMain.METADESCOPT', 'Description')
				),
				new TextField(
					'MetaKeywords',
					_t('CMSMain.METAKEYWORDSOPT', 'Keywords')
				)
			),
			new FieldSet(
				new ResetFormAction(
					'clear', 
					_t('CMSMain_left.ss.CLEAR', 'Clear')
				),
				new FormAction(
					'doSearchTree', 
					_t('CMSMain_left.ss.SEARCH', 'Search')
				)
			)
		);
		$form->setFormMethod('GET');
		$form->disableSecurityToken();
		$form->unsetValidator();
		
		foreach($showDefaultFields as $f) $f->addExtraClass('show-default');
		
		return $form;
	}
	
	function doSearchTree($data, $form) {
		return $this->getsubtree($this->request);
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
			$fields = new FieldSet();
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
		
		return $form->formHtmlContent();
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
			
			return $form->formHtmlContent();
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
			
			return $form->formHtmlContent();
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

/**
 * @package cms
 * @subpackage content
 */
class CMSMainMarkingFilter {
	
	function __construct() {
		$this->ids = array();
		$this->expanded = array();
		
		$where = array();
		
		// Match against URLSegment, Title, MenuTitle & Content
		if (isset($_REQUEST['SiteTreeSearchTerm'])) {
			$term = Convert::raw2sql($_REQUEST['SiteTreeSearchTerm']);
			$where[] = "\"URLSegment\" LIKE '%$term%' OR \"Title\" LIKE '%$term%' OR \"MenuTitle\" LIKE '%$term%' OR \"Content\" LIKE '%$term%'";
		}
		
		// Match against date
		if (isset($_REQUEST['SiteTreeFilterDate'])) {
			$date = $_REQUEST['SiteTreeFilterDate'];
			$date = ((int)substr($date,6,4)) . '-' . ((int)substr($date,3,2)) . '-' . ((int)substr($date,0,2));
			$where[] = "\"LastEdited\" > '$date'"; 
		}
		
		// Match against exact ClassName
		if (isset($_REQUEST['ClassName']) && $_REQUEST['ClassName'] != 'All') {
			$klass = Convert::raw2sql($_REQUEST['ClassName']);
			$where[] = "\"ClassName\" = '$klass'";
		}
		
		// Partial string match against a variety of fields 
		foreach (CMSMain::T_SiteTreeFilterOptions() as $key => $value) {
			if (!empty($_REQUEST[$key])) {
				$match = Convert::raw2sql($_REQUEST[$key]);
				$where[] = "\"$key\" LIKE '%$match%'";
			}
		}
		
		$where = empty($where) ? '' : 'WHERE (' . implode(') AND (',$where) . ')';
		
		$parents = array();
		
		/* Do the actual search */
		$res = DB::query('SELECT "ParentID", "ID" FROM "SiteTree" '.$where);
		if (!$res) return;
		
		/* And keep a record of parents we don't need to get parents of themselves, as well as IDs to mark */
		foreach($res as $row) {
			if ($row['ParentID']) $parents[$row['ParentID']] = true;
			$this->ids[$row['ID']] = true;
		}
		
		/* We need to recurse up the tree, finding ParentIDs for each ID until we run out of parents */
		while (!empty($parents)) {
			$res = DB::query('SELECT "ParentID", "ID" FROM "SiteTree" WHERE "ID" in ('.implode(',',array_keys($parents)).')');
			$parents = array();

			foreach($res as $row) {
				if ($row['ParentID']) $parents[$row['ParentID']] = true;
				$this->ids[$row['ID']] = true;
				$this->expanded[$row['ID']] = true;
			}
		}
	}
	
	function mark($node) {
		$id = $node->ID;
		if(array_key_exists((int) $id, $this->expanded)) $node->markOpened();
		return array_key_exists((int) $id, $this->ids) ? $this->ids[$id] : false;
	}
}

?>
