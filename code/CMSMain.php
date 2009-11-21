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
	
	static $menu_title = 'Site Content';
	
	static $menu_priority = 10;
	
	static $tree_class = "SiteTree";
	
	static $subitem_class = "Member";
	
	static $allowed_actions = array(
		'addmember',
		'doAdd',
		'buildbrokenlinks',
		'compareversions',
		'createtranslation',
		'delete',
		'deletefromlive',
		'duplicate',
		'duplicatewithchildren',
		'getversion',
		'publishall',
		'restore',
		'revert',
		'rollback',
		'sidereports',
		'SideReportsForm',
		'submit',
		'unpublish',
		'versions',
		'EditForm',
		'AddForm',
		'SiteTreeAsUL',
		'getshowdeletedsubtree',
		'SearchTreeForm',
		'ReportForm',
		'LangForm',
		'VersionsForm'
	);
	
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
		Requirements::javascript(CMS_DIR . '/javascript/CMSMain.Tree.js');
		Requirements::javascript(CMS_DIR . '/javascript/CMSMain.EditForm.js');
		Requirements::javascript(CMS_DIR . '/javascript/CMSMain.Translatable.js');
		
		Requirements::css(CMS_DIR . '/css/CMSMain.css');
		
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
	
	protected function getMarkingFilter($params) {
		return new CMSMainMarkingFilter($params);
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
			if(!Director::fileExists($openFolderImage) || $option = "file") $openFolderImage = $fileImage;
			$closedFolderImage = $icon . '-closedfolder.gif';
			if(!Director::fileExists($closedFolderImage) || $option = "file") $closedFolderImage = $fileImage;

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

	function save_siteconfig($data, $form) {
		$siteConfig = SiteConfig::current_site_config();
		$form->saveInto($siteConfig);
		$siteConfig->write();
		FormResponse::status_message('Saved site configuration', "good");
		FormResponse::add("$('Form_EditForm').resetElements();");
		return FormResponse::respond();
	}
	/**
	 * Get a database record to be managed by the CMS
	 */
 	public function getRecord($id) {

		$treeClass = $this->stat('tree_class');

		if($id && is_numeric($id)) {
			$record = DataObject::get_one( $treeClass, "\"$treeClass\".\"ID\" = $id");

			// Then, try getting a record from the live site
			if(!$record) {
				// $record = Versioned::get_one_by_stage($treeClass, "Live", "\"$treeClass\".\"ID\" = $id");
				Versioned::reading_stage('Live');
				singleton($treeClass)->flushCache();

				$record = DataObject::get_one( $treeClass, "\"$treeClass\".\"ID\" = $id");
				if($record) Versioned::reading_stage(null);
			}
			
			// Then, try getting a deleted record
			if(!$record) {
				$record = Versioned::get_latest_version($treeClass, $id);
			}

			// Don't open a page from a different locale
			if($record && Translatable::is_enabled() && $record->Locale && $record->Locale != Translatable::get_current_locale()) {
				$record = null;
			}

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

		$form = parent::getEditForm($id);
		
		// TODO Duplicate record fetching (see parent implementation)
		if(!$id) $id = $this->currentPageID();	
		$record = ($id && $id != "root") ? DataObject::get_by_id($this->stat('tree_class'), $id) : null;
		
		$fields = $form->Fields();
		$actions = $form->Actions();

		if($record) {
			$fields->push($idField = new HiddenField("ID", false, $id));
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
			
			$deleteAction = new FormAction(
				'delete',
				_t('CMSMain.DELETE','Delete from the draft site')
			);
			$deleteAction->addExtraClass('delete');
			$actions->insertBefore($deleteAction, 'action_save');
			
			if($record->IsDeletedFromStage) {
				$form->makeReadonly();
			}
		} elseif ($id == 0) {
			$siteConfig = SiteConfig::current_site_config();
			$form = new Form($this, "EditForm", $siteConfig->getFormFields(), $siteConfig->getFormActions());
			$form->loadDataFrom($siteConfig);
			return $form;
		} else {
			$form = $this->EmptyForm();
		}
				
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
		
		// if changed to a single_instance_only page type
		if ($record->stat('single_instance_only')) {
			FormResponse::add("jQuery('#sitetree li.{$record->ClassName}').addClass('{$record->stat('single_instance_only_css_class')}');");
			FormResponse::add($this->hideSingleInstanceOnlyFromCreateFieldJS($record));
		}
		else {
			FormResponse::add("jQuery('#sitetree li.{$record->ClassName}').removeClass('{$record->stat('single_instance_only_css_class')}');");
		}
		// if chnaged from a single_instance_only page type
		$sampleOriginalClassObject = new $data['ClassName']();
		if($sampleOriginalClassObject->stat('single_instance_only')) {
			FormResponse::add($this->showSingleInstanceOnlyInCreateFieldJS($sampleOriginalClassObject));
		}

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
			
			$form->loadDataFrom($publishedRecord);
		} else {
			$this->response->addHeader('X-Status', _t('LeftAndMain.SAVEDUP'));
			
			// write process might've changed the record, so we reload before returning
			$form->loadDataFrom($record);
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

		if(is_numeric($parentID)) $parentObj = DataObject::get_by_id("SiteTree", $parentID);
		if(!$parentObj || !$parentObj->ID) $parentID = 0;
		
		if($parentObj && !$parentObj->canAddChildren()) return Security::permissionFailure($this);
		if(!singleton($className)->canCreate()) return Security::permissionFailure($this);

		$p = $this->getNewItem("new-$className-$parentID".$suffix, false);
		$p->Locale = $data['Locale'];
		$p->write();
		
		$form = $this->getEditForm($p->ID);
		
		return $form->formHtmlContent();
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
		if($newItem->castingHelperPair('Sort')) {
			$newItem->Sort = DB::query("SELECT MAX(\"Sort\") FROM \"SiteTree\" WHERE \"ParentID\" = '" . Convert::raw2sql($parentID) . "'")->value() + 1;
		}

		if( Member::currentUser() )
			$newItem->OwnerID = Member::currentUser()->ID;

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
		if($record && !$record->canDelete()) return Security::permissionFailure($this);
		
		$descRemoved = '';
		$descendantsRemoved = 0;
		
		// before deleting the records, get the descendants of this tree
		if($record) {
			$descendantIDs = $record->getDescendantIDList('SiteTree');

			// then delete them from the live site too
			$descendantsRemoved = 0;
			foreach( $descendantIDs as $descID )
				if( $descendant = DataObject::get_by_id('SiteTree', $descID) ) {
					$descendant->delete();
					$descendantsRemoved++;
				}

			// delete the record
			$record->delete();
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
		if(!isset($data['ID'])) return new HTTPResponse("Please pass an ID in the form content", 400);
		
		$restoredPage = Versioned::get_latest_version("SiteTree", $data['ID']);
		if(!$restoredPage) 	return new HTTPResponse("SiteTree #$id not found", 400);
		
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
		
		if(Director::is_ajax()) {
			// need a valid ID value even if the record doesn't have one in the database
			// (its still present in the live tables)
			$liveRecord = Versioned::get_one_by_stage(
				'SiteTree', 
				'Live', 
				"\"SiteTree_Live\".\"ID\" = $recordID"
			);
			return ($liveRecord) ? $form->formHtmlContent() : "";
		} else {
			Director::redirectBack();
		}
	}
	
	function sidereports() {
		return new SideReportsHandler($this, 'sidereports');
	}
	
	function SideReportsForm() {
		$record = $this->currentPage();
		$reports = $this->sidereports()->getReportClasses();
		$options = array();
		foreach($reports as $report) {
			if($report != 'SideReport' && singleton($report)->canView()) {
				$options[singleton($report)->group()][singleton($report)->sort()][$report] = singleton($report)->title();
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
		$selectorField = new GroupedDropdownField(
			"ReportClass", 
			_t('CMSMain.REPORT', 'Report'),
			$finalOptions
		);
		
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
		
		return $form;
	}
	
	/**
	 * @return Form
	 */
	function doShowSideReport($data, $form) {
		$form = $this->sidereports()->getForm($data['ReportClass'], $data);
		return $form->forTemplate();
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
		$page = DataObject::get_by_id("SiteTree", $data['ID']);
		if($page && !$page->canPublish()) return Security::permissionFailure($this);
		
		$page->doUnpublish();
		
		$this->response->addHeader(
			'X-Status',
			sprintf(_t('CMSMain.REMOVEDPAGE',"Removed '%s' from the published site"),$page->Title)
		);
		
		$form->loadDataFrom($page);
		
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

			$actions = new FieldSet(
				new FormAction("email", _t('CMSMain.EMAIL',"Email")),
				new FormAction("print", _t('CMSMain.PRINT',"Print")),
				new FormAction("rollback", _t('CMSMain.ROLLBACK',"Roll back to this version"))
			);

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

			SSViewer::setOption('rewriteHashlinks', false);
			
			if(Director::is_ajax()) {
				return $form->formHtmlContent();
			} else {
				$templateData = $this->customise(array(
					"EditForm" => $form
				));
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
			
			// comparison views shouldn't be editable
			$readonlyFields = $form->Fields()->makeReadonly();
			$form->setFields($readonlyFields);
			
			foreach($form->Fields()->dataFields() as $field) {
				$field->dontEscape = true;
			}

			if(Director::is_ajax()) {
				return $form->formHtmlContent();
			} else {
				$templateData = $this->customise(array(
					"EditForm" => $form
				));
				return $templateData->renderWith('LeftAndMain');
			}
		}
	}

	function buildbrokenlinks() {
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
			new HiddenField("Locale", 'Locale', Translatable::get_current_locale()),
			new DropdownField("PageType", "", $pageTypes, 'Page')
		);
		
		$actions = new FieldSet(
			new FormAction("doAdd", _t('CMSMain.GO',"Go"))
		);

		$form = new Form($this, "AddForm", $fields, $actions);
		
		$form->addExtraClass('actionparams');
		
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
			if(!call_user_func(array($filter, 'showInList'))) continue;
			
			$filterMap[$filter] = call_user_func(array($filter, 'title'));
		}
				
		$form = new Form(
			$this,
			'SearchTreeForm',
			new FieldSet(
				new TextField(
					'Title', 
					_t('CMSMain.TITLEOPT', 'Title')
				),
				new DropdownField('filter', 'Type', $filterMap, null, null, 'Any'),
				new TextField('Content', 'Text'),
				new CalendarDateField('EditedSince', _t('CMSMain_left.ss.EDITEDSINCE','Edited Since')),
				new DropdownField('ClassName', 'Page Type', $pageTypes, null, null, 'Any'),
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
		$form->unsetValidator();
		
		return $form;
	}
	
	function doSearchTree($data, $form) {
		return $this->getsubtree($this->request);
	}

	function publishall() {
		ini_set("memory_limit", -1);
		ini_set('max_execution_time', 0);
		
		$response = "";

		if(isset($this->requestParams['confirm'])) {
			$start = 0;
			$pages = DataObject::get("SiteTree", "", "", "", "$start,30");
			$count = 0;
			if($pages){
				while(true) {
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
			}
			$response .= sprintf(_t('CMSMain.PUBPAGES',"Done: Published %d pages"), $count);

		} else {
			$response .= '<h1>' . _t('CMSMain.PUBALLFUN','"Publish All" functionality') . '</h1>
				<p>' . _t('CMSMain.PUBALLFUN2', 'Pressing this button will do the equivalent of going to every page and pressing "publish".  It\'s
				intended to be used after there have been massive edits of the content, such as when the site was
				first built.') . '</p>
				<form method="post" action="publishall">
					<input type="submit" name="confirm" value="'
					. _t('CMSMain.PUBALLCONFIRM',"Please publish every page in the site, copying content stage to live",PR_LOW,'Confirmation button') .'" />
				</form>';
		}
		
		return $response;
	}
	
	/**
	 * Restore a completely deleted page from the SiteTree_versions table.
	 */
	function restore($data, $form) {
		if(!isset($data['ID']) || !is_numeric($data['ID'])) {
			return new HTTPResponse("Please pass an ID in the form content", 400);
		}
		
		$id = (int)$data['ID'];
		$restoredPage = Versioned::get_latest_version("SiteTree", $id);
		if(!$restoredPage) 	return new HTTPResponse("SiteTree #$id not found", 400);
		
		$restoredPage = $restoredPage->doRestoreToStage();
		
		$this->response->addHeader(
			'X-Status',
			sprintf(
				_t('CMSMain.RESTORED',"Restored '%s' successfully",PR_MEDIUM,'Param %s is a title'),
				$restoredPage->TreeTitle
			)
		);
		
		$form = $this->getEditForm($id);
		return $form->formHtmlContent();
	}

	function duplicate() {
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
			
			$form = $this->getEditForm($newPage->ID);
			return $form->formHtmlContent();
		} else {
			user_error("CMSMain::duplicate() Bad ID: '$id'", E_USER_WARNING);
		}
	}

	function duplicatewithchildren() {
		if(($id = $this->urlParams['ID']) && is_numeric($id)) {
			$page = DataObject::get_by_id("SiteTree", $id);
			if($page && (!$page->canEdit() || !$page->canCreate())) {
				return Security::permissionFailure($this);
			}

			$newPage = $page->duplicateWithChildren();

			$form = $this->getEditForm($newPage->ID);
			return $form->formHtmlContent();
		} else {
			user_error("CMSMain::duplicate() Bad ID: '$id'", E_USER_WARNING);
		}
	}
	
	/**
	 * Create a new translation from an existing item, switch to this language and reload the tree.
	 */
	function createtranslation($request) {
		$langCode = Convert::raw2sql($request->getVar('newlang'));
		$originalLangID = (int)$request->getVar('ID');

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
		
		return Director::redirect($url);
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
					"Access to %s",
					PR_MEDIUM,
					"Item in permission selection identifying the admin section, with title and classname. Example: Access to Files & Images"
				), $title),
				'category' => _t('Permission.CMS_ACCESS_CATEGORY', 'CMS Access')
			);
		}
		$perms["CMS_ACCESS_LeftAndMain"] = array(
			'name' => _t('CMSMain.ACCESSALLINTERFACES', 'Access to all CMS sections'),
			'category' => _t('Permission.CMS_ACCESS_CATEGORY', 'CMS Access'),
			'sort' => -100
		);
		
		if (isset($perms['CMS_ACCESS_ModelAdmin'])) unset($perms['CMS_ACCESS_ModelAdmin']);
		
		return $perms;
	}
	
	/**
	 * Returns a form with all languages with languages already used appearing first.
	 * 
	 * @return Form
	 */
	function LangForm() {
		$member = Member::currentUser(); //check to see if the current user can switch langs or not
		if(Permission::checkMember($member, 'VIEW_LANGS')) {
			$field = new LanguageDropdownField(
				'Locale', 
				// TODO i18n
				'Language', 
				array(), 
				'SiteTree', 
				'Locale-English',
				singleton('SiteTree')
			);
			$field->setValue(Translatable::get_current_locale());
        } else {
			// user doesn't have permission to switch langs 
			// so just show a string displaying current language
			$field = new LiteralField(
				'Locale', 
				i18n::get_locale_name( Translatable::get_current_locale())
			);
		}
		
		$form = new Form(
			$this,
			'LangForm',
			new FieldSet(
				$field
			),
			new FieldSet(
				new FormAction('selectlang', _t('CMSMain_left.ss.GO','Go'))
			)
		);
		$form->unsetValidator();
		
		return $form;
	}
	
	function selectlang($data, $form) {
		return $this;
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

class CMSMainMarkingFilter extends LeftAndMainMarkingFilter{

	protected function getQuery($params) {
		$where = array();
		
		$SQL_params = Convert::raw2sql($params);
		foreach($SQL_params as $name => $val) {
			switch($name) {
				// Match against URLSegment, Title, MenuTitle & Content
				case 'SiteTreeSearchTerm':
					$where[] = "\"URLSegment\" LIKE '%$val%' OR \"Title\" LIKE '%$val%' OR \"MenuTitle\" LIKE '%$val%' OR \"Content\" LIKE '%$val%'";
					break;
				// Match against date
				case 'SiteTreeFilterDate':
					$val = ((int)substr($val,6,4)) 
						. '-' . ((int)substr($val,3,2)) 
						. '-' . ((int)substr($val,0,2));
					$where[] = "\"LastEdited\" > '$val'";
					break;
				// Match against exact ClassName
				case 'ClassName':
					if($val && $val != 'All') {
						$where[] = "\"ClassName\" = '$val'";
					}
					break;
				default:
					// Partial string match against a variety of fields 
					if(!empty($val) && singleton("SiteTree")->hasDatabaseField($name)) {
						$where[] = "\"$name\" LIKE '%$val%'";
					}
			}
		}
		
		return new SQLQuery(
			array("ParentID", "ID"),
			'SiteTree',
			$where
		);
	}

}

?>
