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
	
	static $url_segment = 'page';
	
	static $url_rule = '/$Action/$ID/$OtherID';
	
	// Maintain a lower priority than other administration sections
	// so that Director does not think they are actions of CMSMain
	static $url_priority = 40;
	
	static $menu_title = 'Edit Page';
	
	static $menu_priority = 10;
	
	static $tree_class = "SiteTree";
	
	static $subitem_class = "Member";
	
	static $allowed_actions = array(
		'buildbrokenlinks',
		'deleteitems',
		'DeleteItemsForm',
		'dialog',
		'duplicate',
		'duplicatewithchildren',
		'publishall',
		'publishitems',
		'PublishItemsForm',
		'submit',
		'EditForm',
		'SearchForm',
		'SiteTreeAsUL',
		'getshowdeletedsubtree',
		'batchactions',
		'ListView',
		'getListView',
	);
	
	public function init() {
		// set reading lang
		if(Object::has_extension('SiteTree', 'Translatable') && !$this->isAjax()) {
			Translatable::choose_site_locale(array_keys(Translatable::get_existing_content_languages('SiteTree')));
		}
		
		parent::init();
		
		Requirements::css(CMS_DIR . '/css/screen.css');
		
		Requirements::combine_files(
			'cmsmain.js',
			array_merge(
				array(
					CMS_DIR . '/javascript/CMSMain.js',
					CMS_DIR . '/javascript/CMSMain.EditForm.js',
					CMS_DIR . '/javascript/CMSMain.AddForm.js',
					CMS_DIR . '/javascript/CMSPageHistoryController.js',
					CMS_DIR . '/javascript/CMSMain.Tree.js',
					CMS_DIR . '/javascript/SilverStripeNavigator.js'
				),
				Requirements::add_i18n_javascript(CMS_DIR . '/javascript/lang', true, true)
			)
		);

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
	 * @return string
	 */
	public function PreviewLink() {
		$record = $this->getRecord($this->currentPageID());
		$baseLink = ($record && $record instanceof Page) ? $record->Link('?stage=Stage') : Director::absoluteBaseURL();
		return $baseLink;
	}

	/**
	 * Return the entire site tree as a nested set of ULs
	 */
	public function SiteTreeAsUL() {
		$html = '';

		// Include custom CSS for tree icons inline, as the tree might be loaded
		// via Ajax, in which case we can't inject it into the HTML header easily through the HTTP response.
		$css = $this->generateTreeStylingCSS();
		if($this->isAjax()) {
			$html .= "<style type=\"text/css\">\n" . $css . "</style>\n";				
		} else {
			Requirements::customCSS($css);
		}

		// Pre-cache sitetree version numbers for querying efficiency
		Versioned::prepopulate_versionnumber_cache("SiteTree", "Stage");
		Versioned::prepopulate_versionnumber_cache("SiteTree", "Live");
		$html .= $this->getSiteTreeFor($this->stat('tree_class'));

		return $html;
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
			Object::create('ResetFormAction', 'clear', _t('CMSMain_left.ss.CLEAR', 'Clear'))
				->addExtraClass('ss-ui-action-minor'),
			FormAction::create('doSearch',  _t('CMSMain_left.ss.SEARCH', 'Search'))
		);

		// Use <button> to allow full jQuery UI styling
		foreach($actions->dataFields() as $action) $action->setUseButtonTag(true);
		
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
	 * @return ArrayList
	 */
	public function Breadcrumbs($unlinked = false) {
		$items = parent::Breadcrumbs($unlinked);

		// The root element should point to the pages tree view,
		// rather than the actual controller (which would just show an empty edit form)
		$items[0]->Title = self::menu_title_for_class('CMSPagesController');
		$items[0]->Link = singleton('CMSPagesController')->Link();

		return $items;
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
		$def['Root']['disallowedParents'] = array();

		foreach($classes as $class) {
			$obj = singleton($class);
			
			if($obj instanceof HiddenClass) continue;
			
			$allowedChildren = $obj->allowedChildren();
			
			// SiteTree::allowedChildren() returns null rather than an empty array if SiteTree::allowed_chldren == 'none'
			if($allowedChildren == null) $allowedChildren = array();
			$allowedChildren = array_keys(array_diff($classes, $allowedChildren));
			if($allowedChildren) $def[$class]['disallowedChildren'] = $allowedChildren;
			
			$defaultChild = $obj->defaultChild();
			
			if($defaultChild != 'Page' && $defaultChild != null) 
				$def[$class]['defaultChild'] = $defaultChild;
			
			$defaultParent = $obj->defaultParent();
			$parent = SiteTree::get_by_link($defaultParent);
			
			$id = $parent ? $parent->id : null;
			
			if ($defaultParent != 1 && $defaultParent != null)  $def[$class]['defaultParent'] = $defaultParent;
			
			if(isset($def[$class]['disallowedChildren'])) {
				foreach($def[$class]['disallowedChildren'] as $disallowedChild) {
					$def[$disallowedChild]['disallowedParents'][] = $class;
				}
			}
			
			// Are any classes allowed to be parents of root?
			$def['Root']['disallowedParents'][] = $class;
		}

		return Convert::raw2xml(Convert::raw2json($def));
	}
	
	/**
	 * Include CSS for page icons. We're not using the JSTree 'types' option
	 * because it causes too much performance overhead just to add some icons.
	 * 
	 * @return String CSS 
	 */
	function generateTreeStylingCSS() {
		$css = ''; 
		
		$classes = ClassInfo::subclassesFor('SiteTree'); 
		foreach($classes as $class) {
			$obj = singleton($class); 
			$iconSpec = $obj->stat('icon'); 

			if(!$iconSpec) continue;

			// Legacy support: We no longer need separate icon definitions for folders etc.
			$iconFile = (is_array($iconSpec)) ? $iconSpec[0] : $iconSpec;

			// Legacy support: Add file extension if none exists
			if(!pathinfo($iconFile, PATHINFO_EXTENSION)) $iconFile .= '-file.gif';

			$iconPathInfo = pathinfo($iconFile); 
			
			// Base filename 
			$baseFilename = $iconPathInfo['dirname'] . '/' . $iconPathInfo['filename'];
			$fileExtension = $iconPathInfo['extension'];

			if(Director::fileExists($iconFile)) {
				$css .= sprintf(
					"li.class-%s > a .jstree-pageicon { background: transparent url('%s') 0 0 no-repeat; }\n",
					$class, $iconFile
				);	
			} else {
				// Support for more sophisticated rules, e.g. sprited icons
				$css .= sprintf(
					"li.class-%s > a .jstree-pageicon { %s }\n",
					$class, $iconFile
				);
			}
			

		}

		return $css;
	}

	/**
	 * Populates an array of classes in the CMS
	 * which allows the user to change the page type.
	 *
	 * @return SS_List
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
	 * Get a database record to be managed by the CMS.
	 *
	 * @param int $id Record ID
	 * @param int $versionID optional Version id of the given record
	 */
 	public function getRecord($id, $versionID = null) {
		$treeClass = $this->stat('tree_class');

		if($id instanceof $treeClass) {
			return $id;
		} 
		else if($id && is_numeric($id)) {
			if($this->request->getVar('Version')) {
				$versionID = (int) $this->request->getVar('Version');
			}
			
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
			$deletedFromStage = $record->IsDeletedFromStage;
			$deleteFromLive = !$record->ExistsOnLive;

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
			
			if(!$deletedFromStage) {
				$stageURLField->setValue(Controller::join_links($record->AbsoluteLink(), '?stage=Stage'));
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

			// Use <button> to allow full jQuery UI styling
			$actionsFlattened = $actions->dataFields();
			if($actionsFlattened) foreach($actionsFlattened as $action) $action->setUseButtonTag(true);
			
			if($record->hasMethod('getCMSValidator')) {
				$validator = $record->getCMSValidator();
			} else {
				$validator = new RequiredFields();
			}
			
			$form = new Form($this, "EditForm", $fields, $actions, $validator);
			$form->loadDataFrom($record);
			$stageURLField->setValue(Controller::join_links($record->getStageURLSegment(), '?stage=Stage'));
			$form->disableDefaultAction();
			$form->addExtraClass('cms-edit-form');
			$form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
			// TODO Can't merge $FormAttributes in template at the moment
			$form->addExtraClass('center ss-tabset ' . $this->BaseCSSClasses());
			if($form->Fields()->hasTabset()) $form->Fields()->findOrMakeTab('Root')->setTemplate('CMSTabSet');

			if(!$record->canEdit() || $deletedFromStage) {
				$readonlyFields = $form->Fields()->makeReadonly();
				$form->setFields($readonlyFields);
			}

			$this->extend('updateEditForm', $form);
			return $form;
		} else if($id) {
			return new Form($this, "EditForm", new FieldList(
				new LabelField('PageDoesntExistLabel',_t('CMSMain.PAGENOTEXISTS',"This page doesn't exist"))), new FieldList()
			);
		}
	}
	
	/**
	 * Returns the files and subfolders contained in the currently selected folder,
	 * defaulting to the root node. Doubles as search results, if any search parameters
	 * are set through {@link SearchForm()}.
	 * 
	 * @return SS_List
	 */
	public function getList() {
		$list = new DataList($this->stat('tree_class'));
		
		$request = $this->request;
		$filter = null;
		$ids = array();
		if($filterClass = $request->requestVar('FilterClass')){
			if(!is_subclass_of($filterClass, 'CMSSiteTreeFilter')) {
				throw new Exception(sprintf('Invalid filter class passed: %s', $filterClass));
			}
			$filter = new $filterClass($request->requestVars());
			foreach($pages=$filter->pagesIncluded() as $pageMap){
				$ids[] = $pageMap['ID'];
			}
		}
		
		if(count($ids)) $list->where('"'.$this->stat('tree_class').'"."ID" IN ('.implode(",", $ids).')');
		return $list;
	}
	
	public function getListView(){
		$list = $this->getList();
		$gridFieldConfig = GridFieldConfig::create()->addComponents(
			new GridFieldSortableHeader(),
			new GridFieldDataColumns(),
			new GridFieldPaginator(15),
			new GridFieldEditButton(),
			new GridFieldDeleteAction(),
			new GridFieldDetailForm()
		);
		$gridField = new GridField('Page','Pages', $list, $gridFieldConfig);
		$listview = new Form(
			$this,
			'ListView',
			new FieldList($gridField),
			new FieldList()
		);

		$this->extend('updateListView', $listview);
		$listview->disableSecurityToken();
		return $listview;
	}
	
	public function getListViewHTML(){
		return $this->getListView()->forTemplate();
	}
	
	public function ListView() {
		return $this->getListView();
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
			if(!$record || !$record->ID) throw new SS_HTTPResponse_Exception("Bad record ID #$SQL_id", 404);
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
		$recordTitle = $record->Title;
		$recordID = $record->ID;
		
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

		$form->sessionMessage(
			sprintf(
				_t('CMSMain.REMOVED', 'Deleted \'%s\'%s from live site'), 
				$recordTitle, 
				$descRemoved
			),
			'good'
		);

		// Even if the record has been deleted from stage and live, it can be viewed in "archive mode"
		return $this->redirect(Controller::join_links($this->Link('show'), $recordID));
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
		
		$id = (int) $data['ID'];
		$restoredPage = Versioned::get_latest_version("SiteTree", $id);
		if(!$restoredPage) 	return new SS_HTTPResponse("SiteTree #$id not found", 400);
		
		$record = Versioned::get_one_by_stage(
			'SiteTree', 
			'Live', 
			sprintf("\"SiteTree_Live\".\"ID\" = '%d'", (int)$data['ID'])
		);

		// a user can restore a page without publication rights, as it just adds a new draft state
		// (this action should just be available when page has been "deleted from draft")
		if($record && !$record->canEdit()) return Security::permissionFailure($this);
		if(!$record || !$record->ID) throw new SS_HTTPResponse_Exception("Bad record ID #$id", 404);

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
		$id = Convert::raw2sql($data['ID']);
		$record = DataObject::get_one(
			"SiteTree", 
			sprintf("\"SiteTree\".\"ID\" = %d", $id)
		);
		if($record && !$record->canDelete()) return Security::permissionFailure();
		if(!$record || !$record->ID) throw new SS_HTTPResponse_Exception("Bad record ID #$id", 404);
		
		// save ID and delete record
		$recordID = $record->ID;
		$record->delete();

		$form->sessionMessage(
			sprintf(
				_t('CMSMain.REMOVEDPAGEFROMDRAFT',"Removed '%s' from the draft site"),
				$record->Title
			),
			'good'
		);

		// Even if the record has been deleted from stage and live, it can be viewed in "archive mode"
		return $this->redirect(Controller::join_links($this->Link('show'), $recordID));
	}

	function publish($data, $form) {
		$data['publish'] = '1';
		
		return $this->save($data, $form);
	}

	function unpublish($data, $form) {
		$className = $this->stat('tree_class');
		$record = DataObject::get_by_id($className, $data['ID']);
		
		if($record && !$record->canDeleteFromLive()) return Security::permissionFailure($this);
		if(!$record || !$record->ID) throw new SS_HTTPResponse_Exception("Bad record ID #" . (int)$data['ID'], 404);
		
		$record->doUnpublish();
		
		$this->response->addHeader(
			'X-Status',
			sprintf(_t('CMSMain.REMOVEDPAGE',"Removed '%s' from the published site"),$record->Title)
		);
		
		// Reload form, data and actions might have changed
		$form = $this->getEditForm($record->ID);
		
		return $form->forTemplate();
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
		
		increase_time_limit_to();
		increase_memory_limit_to();
		
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

	function publishall($request) {
		if(!Permission::check('ADMIN')) return Security::permissionFailure($this);

		increase_time_limit_to();
		increase_memory_limit_to();
		
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
			if($page && (!$page->canEdit() || !$page->canCreate())) return Security::permissionFailure($this);
			if(!$page || !$page->ID) throw new SS_HTTPResponse_Exception("Bad record ID #$id", 404);

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
			if($page && (!$page->canEdit() || !$page->canCreate())) return Security::permissionFailure($this);
			if(!$page || !$page->ID) throw new SS_HTTPResponse_Exception("Bad record ID #$id", 404);

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
	 * (automacially replaced by build scripts).
	 * 
	 * @return string
	 */
	public function CMSVersion() {
		$cmsVersion = file_get_contents(BASE_PATH . '/cms/silverstripe_version');
		if(!$cmsVersion) $cmsVersion = _t('LeftAndMain.VersionUnknown');
		$sapphireVersion = file_get_contents(BASE_PATH . '/cms/silverstripe_version');
		if(!$sapphireVersion) $sapphireVersion = _t('LeftAndMain.VersionUnknown');
		return sprintf(
			"cms: %s, sapphire: %s",
			$cmsVersion,
			$sapphireVersion
		);
	}

	function providePermissions() {
		$title = _t("CMSPagesController.MENUTITLE", LeftAndMain::menu_title_for_class('CMSPagesController'));
		return array(
			"CMS_ACCESS_CMSMain" => array(
				'name' => sprintf(_t('CMSMain.ACCESS', "Access to '%s' section"), $title),
				'category' => _t('Permission.CMS_ACCESS_CATEGORY', 'CMS Access'),
				'help' => _t(
					'CMSMain.ACCESS_HELP',
					'Allow viewing of the section containing page tree and content. View and edit permissions can be handled through page specific dropdowns, as well as the separate "Content permissions".'
				),
				'sort' => -99 // below "CMS_ACCESS_LeftAndMain", but above everything else
			)
		);
	}

}
