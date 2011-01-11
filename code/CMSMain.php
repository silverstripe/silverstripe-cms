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
		'canceldraftchangesdialog',
		'compareversions',
		'createtranslation',
		'deleteitems',
		'DeleteItemsForm',
		'dialog',
		'duplicate',
		'duplicatewithchildren',
		'getpagecount',
		'getversion',
		'publishall',
		'publishitems',
		'PublishItemsForm',
		'RootForm',
		'sidereport',
		'submit',
		'versions',
		'EditForm',
		'AddPageOptionsForm',
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
		parent::init();
		
		// Locale" attribute is either explicitly added by LeftAndMain Javascript logic,
		// or implied on a translated record (see {@link Translatable->updateCMSFields()}).
		// $Lang serves as a "context" which can be inspected by Translatable - hence it
		// has the same name as the database property on Translatable.
		if($this->getRequest()->requestVar("Locale")) {
			$this->Locale = $this->getRequest()->requestVar("Locale");
		} elseif($this->getRequest()->requestVar("locale")) {
			$this->Locale = $this->getRequest()->requestVar("locale");
		} else {
			$this->Locale = Translatable::default_locale();
		}
		Translatable::set_current_locale($this->Locale);
		
		// collect languages for TinyMCE spellchecker plugin.
		// see http://wiki.moxiecode.com/index.php/TinyMCE:Plugins/spellchecker
		$langName = i18n::get_locale_name($this->Locale);
		HtmlEditorConfig::get('cms')->setOption('spellchecker_languages', "+{$langName}={$this->Locale}");
				
		Requirements::javascript(CMS_DIR . '/javascript/CMSMain.js');
		Requirements::javascript(CMS_DIR . '/javascript/CMSMain_left.js');
		Requirements::javascript(CMS_DIR . '/javascript/CMSMain_right.js');
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
			Requirements::customScript("window.name = windowName('cms');");
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

		return $this->getSiteTreeFor("SiteTree");
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
		Requirements::customScript("siteTreeHints = " . $this->jsDeclaration($def) . ";");
	}

	public function generateTreeStylingJS() {
		$classes = ClassInfo::subclassesFor('SiteTree');
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
	 * Return a javascript instanciation of this array
	 */
	protected function jsDeclaration($array) {
		if(is_array($array)) {
			$object = false;
			foreach(array_keys($array) as $key) {
				if(!is_numeric($key)) {
					$object = true;
					break;
				}
			}

			if($object) {
				foreach($array as $k => $v) {
					$parts[] = "$k : " . $this->jsDeclaration($v);
				}
				return " {\n " . implode(", \n", $parts) . " }\n";
			} else {
				foreach($array as $part) $parts[] = $this->jsDeclaration($part);
				return " [ " . implode(", ", $parts) . " ]\n";
			}
		} else {
			return "'" . addslashes($array) . "'";
		}
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
		FormResponse::status_message('Saved site configuration', "good");
		FormResponse::add("$('Form_EditForm').resetElements();");

		$title = Convert::raw2js($siteConfig->Title);
		FormResponse::add("$('sitetree').setNodeTitle(0, \"$title\");");

		return FormResponse::respond();
	}
	/**
	 * Get a database record to be managed by the CMS
	 */
 	public function getRecord($id) {
		$treeClass = $this->stat('tree_class');

		if($id && is_numeric($id)) {
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

	public function getEditForm($id) {
		$record = $this->getRecord($id);

		if($record) {
			if($record->IsDeletedFromStage) $record->Status = _t('CMSMain.REMOVEDFD',"Removed from the draft site");

			$fields = $record->getCMSFields($this);
			if ($fields == null) {
				user_error("getCMSFields returned null on a '".get_class($record)."' object - it should return a FieldSet object. Perhaps you forgot to put a return statement at the end of your method?", E_USER_ERROR);
			}
			$fields->push($idField = new HiddenField("ID"));
			$fields->push($liveURLField = new HiddenField("LiveURLSegment"));
			$fields->push($stageURLField = new HiddenField("StageURLSegment"));

			/*if( substr($record->ID, 0, 3 ) == 'new' )*/
			$fields->push(new HiddenField('Sort','', $record->Sort ));

			$idField->setValue($id);
			
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
				new LabelField('PageDoesntExistLabel',_t('CMSMain.PAGENOTEXISTS',"This page doesn't exist"))), new FieldSet());

		}
	}

	/**
	 * @return Form
	 */
	function RootForm() {
		$siteConfig = SiteConfig::current_site_config();
		$fields = $siteConfig->getCMSFields();
		if(Object::has_extension('SiteConfig',"Translatable")) {
			$fields->push(new HiddenField('Locale','', $siteConfig->Locale));
		}

		$form = new Form($this, 'RootForm', $fields, $siteConfig->getCMSActions());
		$form->setHTMLID('Form_EditForm');
		$form->loadDataFrom($siteConfig);

		$this->extend('updateEditForm', $form);

		return $form;
	}

	//------------------------------------------------------------------------------------------//
	// Data saving handlers


	public function addpage($data, $form) {
		// Protect against CSRF on destructive action
		if(!SecurityToken::inst()->checkRequest($this->request)) return $this->httpError(400);
		
		$className = isset($_REQUEST['PageType']) ? $_REQUEST['PageType'] : "Page";
		$parent = isset($_REQUEST['ParentID']) ? $_REQUEST['ParentID'] : 0;
		$suffix = isset($_REQUEST['Suffix']) ? "-" . $_REQUEST['Suffix'] : null;

		if(!$parent && isset($_REQUEST['Parent'])) {
			$page = SiteTree::get_by_link($_REQUEST['Parent']);
			if($page) $parent = $page->ID;
		}

		if(is_numeric($parent)) $parentObj = DataObject::get_by_id("SiteTree", $parent);
		else $parentObj = null;
		if(!$parentObj || !$parentObj->ID) $parent = 0;
		
		if($parentObj){
			if(!$parentObj->canAddChildren()) return Security::permissionFailure($this);
			if(!singleton($className)->canCreate()) return Security::permissionFailure($this);
		}else{
			if(!SiteConfig::current_site_config()->canCreateTopLevel())
				return Security::permissionFailure($this);
		}

		$p = $this->getNewItem("new-$className-$parent".$suffix, false);
		$p->Locale = $_REQUEST['Locale'];
		$p->write();

		return $this->returnItemToUser($p);
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
	public function deletefromlive($urlParams, $form) {
		$id = $_REQUEST['ID'];
		Versioned::reading_stage('Live');
		$record = DataObject::get_by_id("SiteTree", $id);
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

		FormResponse::add($this->deleteTreeNodeJS($record));
		FormResponse::status_message(sprintf(_t('CMSMain.REMOVED', 'Deleted \'%s\'%s from live site'), $record->Title, $descRemoved), 'good');

		return FormResponse::respond();
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
	public function revert($urlParams, $form) {
		$id = (int)$_REQUEST['ID'];
		$record = Versioned::get_one_by_stage('SiteTree', 'Live', "\"SiteTree_Live\".\"ID\" = '{$id}'");

		// a user can restore a page without publication rights, as it just adds a new draft state
		// (this action should just be available when page has been "deleted from draft")
		if(isset($record) && $record && !$record->canEdit()) return Security::permissionFailure($this);

		$record->doRevertToLive();

		$title = Convert::raw2js($record->Title);
		FormResponse::get_page($id);
		FormResponse::add("$('sitetree').setNodeTitle($id, '$title');");
		FormResponse::status_message(sprintf(_t('CMSMain.RESTORED',"Restored '%s' successfully",PR_MEDIUM,'Param %s is a title'),$title),'good');

		return FormResponse::respond();
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
		
		if(Director::is_ajax()) {
			// need a valid ID value even if the record doesn't have one in the database
			// (its still present in the live tables)
			$liveRecord = Versioned::get_one_by_stage('SiteTree', 'Live', "\"SiteTree_Live\".\"ID\" = $recordID");
			// if the page has never been published to live, we need to act the same way as in deletefromlive()
			if($liveRecord) {
				// the form is readonly now, so we need to refresh the representation
				FormResponse::get_page($recordID);
				return $this->tellBrowserAboutPublicationChange($liveRecord, sprintf(_t('CMSMain.REMOVEDPAGEFROMDRAFT',"Removed '%s' from the draft site"),$record->Title));
			} else {
				FormResponse::add($this->deleteTreeNodeJS($record));
				FormResponse::status_message(sprintf(_t('CMSMain.REMOVEDPAGEFROMDRAFT',"Removed '%s' from the draft site"),$record->Title), 'good');
				return FormResponse::respond();
			}			
		} else {
			Director::redirectBack();
		}
	}
	
	function SideReports() {
		return SS_Report::get_reports('SideReport');
	}

	/*
	 * Return a dropdown for selecting reports
	 */
	function ReportSelector() {
		// $options[""] = _t('CMSMain.CHOOSEREPORT',"(Choose a report)");
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
		
		return new GroupedDropdownField("ReportSelector", _t('CMSMain.REPORT', 'Report'),$finalOptions);
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
	 * Get the content for a side report
	 */
	function sidereport() {
		$reportClass = $this->urlParams['ID'];
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
	 * Get the versions of the current page
	 */
	function versions() {
		$pageID = $this->urlParams['ID'];
		$page = $this->getRecord($pageID);
		if($page) {
			$versions = $page->allVersions($_REQUEST['unpublished'] ? "" : "\"SiteTree\".\"WasPublished\" = 1");
			return array(
				'Versions' => $versions,
			);
		} else {
			return sprintf(_t('CMSMain.VERSIONSNOPAGE',"Can't find page #%d",PR_LOW),$pageID);
		}
	}

	/**
	 * Roll a page back to a previous version
	 */
	function rollback($data, $form) {
		if(isset($_REQUEST['Version']) && (bool)$_REQUEST['Version']) {
			$this->extend('onBeforeRollback', $_REQUEST['ID']);
			$record = $this->performRollback($_REQUEST['ID'], $_REQUEST['Version']);
			echo sprintf(_t('CMSMain.ROLLEDBACKVERSION',"Rolled back to version #%d.  New version number is #%d"),$_REQUEST['Version'],$record->Version);
		} else {
			$record = $this->performRollback($_REQUEST['ID'], "Live");
			echo sprintf(_t('CMSMain.ROLLEDBACKPUB',"Rolled back to published version. New version number is #%d"),$record->Version);
		}
	}

	function unpublish($data, $form) {
		$SQL_id = Convert::raw2sql($_REQUEST['ID']);

		$page = DataObject::get_by_id("SiteTree", $SQL_id);
		
		if($page && !$page->canDeleteFromLive()) return Security::permissionFailure($this);
		
		$page->doUnpublish();
		
		return $this->tellBrowserAboutPublicationChange($page, sprintf(_t('CMSMain.REMOVEDPAGE',"Removed '%s' from the published site"),$page->Title));
	}
	
	/**
	 * Return a few pieces of information about a change to a page
	 *  - Send the new status message
	 *  - Update the action buttons
	 *  - Update the treenote
	 *  - Send a status message
	 */
	function tellBrowserAboutPublicationChange($page, $statusMessage) {
		$JS_title = Convert::raw2js($page->TreeTitle());

		$JS_stageURL = $page->IsDeletedFromStage ? '' : Convert::raw2js($page->AbsoluteLink());
		$liveRecord = Versioned::get_one_by_stage('SiteTree', 'Live', "\"SiteTree\".\"ID\" = $page->ID");

		$JS_liveURL = $liveRecord ? Convert::raw2js($liveRecord->AbsoluteLink()) : '';

		FormResponse::add($this->getActionUpdateJS($page));
		FormResponse::update_status($page->Status);
		
		if($JS_stageURL || $JS_liveURL) {
			FormResponse::add("\$('sitetree').setNodeTitle($page->ID, '$JS_title');");
		} else {
			FormResponse::add("var node = $('sitetree').getTreeNodeByIdx('$page->ID');");
			FormResponse::add("if(node && node.parentTreeNode) node.parentTreeNode.removeTreeNode(node);");
			FormResponse::add("$('Form_EditForm').reloadIfSetTo($page->ID);");
		}
		
		if($statusMessage) FormResponse::status_message($statusMessage, 'good');
		FormResponse::add("$('Form_EditForm').elements.StageURLSegment.value = '$JS_stageURL';");
		FormResponse::add("$('Form_EditForm').elements.LiveURLSegment.value = '$JS_liveURL';");
		FormResponse::add("$('Form_EditForm').notify('PagePublished', $('Form_EditForm').elements.ID.value);");

		return FormResponse::respond();
	}

	function performRollback($id, $version) {
		$record = DataObject::get_by_id($this->stat('tree_class'), $id);
		if($record && !$record->canEdit()) return Security::permissionFailure($this);
		
		$record->doRollbackTo($version);
		return $record;
	}

	function getversion() {
		$id = $this->urlParams['ID'];
		$version = str_replace('&ajax=1','',$this->urlParams['OtherID']);
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
				$result = $templateData->renderWith($this->class . '_right');
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
		$id = (int)$this->urlParams['ID'];
		$version1 = (int)$_REQUEST['From'];
		$version2 = (int)$_REQUEST['To'];

		if( $version1 > $version2 ) {
			$toVersion = $version1;
			$fromVersion = $version2;
		} else {
			$toVersion = $version2;
			$fromVersion = $version1;
		}

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
			
			// comparison views shouldn't be editable
			$readonlyFields = $form->Fields()->makeReadonly();
			$form->setFields($readonlyFields);
			
			foreach($form->Fields()->dataFields() as $field) {
				$field->dontEscape = true;
			}

			return $this->sendFormToBrowser(array(
				"EditForm" => $form
			));
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

	function dialog() {
		Requirements::clear();

		$buttons = new DataObjectSet;
		if($_REQUEST['Buttons']) foreach($_REQUEST['Buttons'] as $button) {
			list($name, $title) = explode(',',$button,2);
			$buttons->push(new ArrayData(array(
				"Name" => $name,
				"Title" => $title,
			)));
		}

		return array(
			"Message" => htmlentities($_REQUEST['Message']),
			"Buttons" => $buttons,
			"Modal" => $_REQUEST['Modal'] ? true : false,
		);
	}

	function canceldraftchangesdialog() {
		Requirements::clear();
		Requirements::css(CMS_DIR . 'css/dialog.css');
		Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/prototype/prototype.js');
		Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/behaviour/behaviour.js');
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/prototype_improvements.js');
		Requirements::javascript(CMS_DIR . '/javascript/dialog.js');

		$message = _t('CMSMain.COPYPUBTOSTAGE',"Do you really want to copy the published content to the stage site?");
		$buttons = "<button name=\"OK\">" . _t('CMSMain.OK','OK') ."</button><button name=\"Cancel\">" . _t('CMSMain.CANCEL',"Cancel") . "</button>";

		return $this->customise( array(
			'Message' => $message,
			'Buttons' => $buttons,
			'DialogType' => 'alert'
		))->renderWith('Dialog');
	}
	
	/**
	 * Batch Actions Handler
	 */
	function batchactions() {
		return new CMSBatchActionHandler($this, 'batchactions');
	}
	
	/**
	 * @return Form
	 */
	public function PublishItemsForm() {
		$form = new Form(
			$this,
			'PublishItemsForm',
			new FieldSet(
				new HiddenField('csvIDs'),
				new CheckboxField('ShowDrafts', _t('CMSMain_left.ss.SHOWONLYCHANGED','Show only changed pages'))
			),
			new FieldSet(
				new FormAction('publishitems', _t('CMSMain_left.ss.PUBLISHCONFIRM','Publish the selected pages'))
			)
		);
		$form->addExtraClass('actionparams');
		return $form;
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
	
	/**
	 * @return Form
	 */
	function DeleteItemsForm() {
		$form = new Form(
			$this,
			'DeleteItemsForm',
			new FieldSet(
				new LiteralField('SelectedPagesNote',
					sprintf('<p>%s</p>', _t('CMSMain_left.ss.SELECTPAGESACTIONS','Select the pages that you want to change &amp; then click an action:'))
				),
				new HiddenField('csvIDs')
			),
			new FieldSet(
				new FormAction('deleteitems', _t('CMSMain_left.ss.DELETECONFIRM','Delete the selected pages'))
			)
		);
		$form->addExtraClass('actionparams');
		return $form;
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

	function AddPageOptionsForm() {
		$pageTypes = array();

		foreach( $this->PageTypes() as $arrayData ) {
			$pageTypes[$arrayData->getField('ClassName')] = $arrayData->getField('AddAction');
		}
		
		$fields = new FieldSet(
			new HiddenField("ParentID"),
			new HiddenField("Locale", 'Locale', Translatable::get_current_locale()),
			new DropdownField("PageType", "", $pageTypes, 'Page')
		);
		
		$this->extend('updatePageOptions', $fields);
		
		$actions = new FieldSet(
			new FormAction("addpage", _t('CMSMain.GO',"Go"))
		);

		return new Form($this, "AddPageOptionsForm", $fields, $actions);
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
		if(($id = $_REQUEST['ID']) && is_numeric($id)) {
			$restoredPage = Versioned::get_latest_version("SiteTree", $id);
			if($restoredPage) {
				$restoredPage = $restoredPage->doRestoreToStage();

				FormResponse::get_page($id);
				$title = Convert::raw2js($restoredPage->TreeTitle());
				FormResponse::add("$('sitetree').setNodeTitle($id, '$title');");
				FormResponse::status_message(sprintf(_t('CMSMain.RESTORED',"Restored '%s' successfully",PR_MEDIUM,'Param %s is a title'),$title),'good');
				return FormResponse::respond();

			} else {
				return new SS_HTTPResponse("SiteTree #$id not found", 400);
			}
		} else {
			return new SS_HTTPResponse("Please pass an ID in the form content", 400);
		}
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

			return $this->returnItemToUser($newPage);
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

			return $this->returnItemToUser($newPage);
		} else {
			user_error("CMSMain::duplicate() Bad ID: '$id'", E_USER_WARNING);
		}
	}
	

	
	/**
	 * Create a new translation from an existing item, switch to this language and reload the tree.
	 */
	function createtranslation($request) {
		// Protect against CSRF on destructive action
		if(!SecurityToken::inst()->checkRequest($request)) return $this->httpError(400);
		
		$langCode = Convert::raw2sql($_REQUEST['newlang']);
		$originalLangID = (int)$_REQUEST['ID'];

		$record = $this->getRecord($originalLangID);
		
		$this->Locale = $langCode;
		Translatable::set_current_locale($langCode);
		
		// Create a new record in the database - this is different
		// to the usual "create page" pattern of storing the record
		// in-memory until a "save" is performed by the user, mainly
		// to simplify things a bit.
		// @todo Allow in-memory creation of translations that don't persist in the database before the user requests it
		$translatedRecord = $record->createTranslation($langCode);

		$url = sprintf(
			"%s/%d/?locale=%s", 
			$this->Link('show'),
			$translatedRecord->ID,
			$langCode
		);
		FormResponse::add(sprintf('window.location.href = "%s";', $url));
		return FormResponse::respond();
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
	
	/**
     * Returns all languages with languages already used appearing first.
     * Called by the SSViewer when rendering the template.
     */
    function LangSelector() {
		$member = Member::currentUser(); 
		$dropdown = new LanguageDropdownField(
			'LangSelector', 
			'Language', 
			array(),
			'SiteTree', 
			'Locale-English',
			singleton('SiteTree')
		);
		$dropdown->setValue(Translatable::get_current_locale());
		return $dropdown;
    }

	/**
	 * Determine if there are more than one languages in our site tree.
	 * 
	 * @return boolean
	 */
	function MultipleLanguages() {
		$langs = Translatable::get_existing_content_languages('SiteTree');

		return (count($langs) > 1);
	}
	
	/**
	 * @return boolean
	 */
	function IsTranslatableEnabled() {
		return Object::has_extension('SiteTree', 'Translatable');
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
