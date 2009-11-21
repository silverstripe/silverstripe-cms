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
		'addpage',
		'buildbrokenlinks',
		'compareversions',
		'createtranslation',
		'delete',
		'deletefromlive',
		'deleteitems',
		'DeleteItemsForm',
		'duplicate',
		'duplicatewithchildren',
		'getpagecount',
		'getversion',
		'publishall',
		'publishitems',
		'PublishItemsForm',
		'restore',
		'revert',
		'rollback',
		'sidereport',
		'submit',
		'unpublish',
		'versions',
		'EditForm',
		'AddPageOptionsForm',
		'SiteTreeAsUL',
		'getshowdeletedsubtree',
		'getfilteredsubtree',
		'batchactions',
		'SearchTreeForm'
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
		
		Requirements::javascript(CMS_DIR . '/javascript/CMSMain.BatchActions.js');
		Requirements::javascript(CMS_DIR . '/javascript/CMSMain.Translatable.js');
		
		Requirements::css(CMS_DIR . '/css/CMSMain.css');
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

		return $this->getSiteTreeFor("SiteTree");
	}
	
	/**
	 * Use a CMSSiteTreeFilter to only get certain nodes
	 *
	 * @return string
	 */
	public function getfilteredsubtree($data, $form) {
		$params = $form->getData();
		
		// Get the tree
		$tree = $this->getSiteTreeFor(
			$this->stat('tree_class'), 
			$data['ID'], 
			null, 
			array(new CMSMainMarkingFilter($params), 'mark')
		);

		// Trim off the outer tag
		$tree = ereg_replace('^[ \t\r\n]*<ul[^>]*>','', $tree);
		$tree = ereg_replace('</ul[^>]*>[ \t\r\n]*$','', $tree);
		
		return $tree;
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
		
		$record = ($id) ? $this->getRecord($id) : null;
		if($record && !$record->canView()) return Security::permissionFailure($this);

		if($record) {
			if($record->IsDeletedFromStage) $record->Status = _t('CMSMain.REMOVEDFD',"Removed from the draft site");

			$fields = $record->getCMSFields($this);
			if ($fields == null) {
				user_error("getCMSFields returned null on a '".get_class($record)."' object - it should return a FieldSet object. Perhaps you forgot to put a return statement at the end of your method?", E_USER_ERROR);
			}
			$fields->push($idField = new HiddenField("ID", false, $id));
			$fields->push($liveURLField = new HiddenField("LiveURLSegment"));
			$fields->push($stageURLField = new HiddenField("StageURLSegment"));
			$fields->push($stageURLField = new HiddenField("TreeTitle", false, $record->TreeTitle));

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
				// add default actions if none are defined
				if(!$actions || !$actions->Count()) {
					if($record->canEdit()) {
						$actions->push(new FormAction('save',_t('CMSMain.SAVE','Save')));
						$actions->push($deleteAction = new FormAction('delete',_t('CMSMain.DELETE','Delete from the draft site')));
						$deleteAction->addExtraClass('delete');
					}
				}
			}
			
			$form = new Form($this, "EditForm", $fields, $actions);
			$form->loadDataFrom($record);
			$form->disableDefaultAction();
			
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
				// The clientside (mainly LeftAndMain*.js) rely on ajax responses
				// which can be evaluated as javascript, hence we need
				// to override any global changes to the validation handler.
				$validator->setJavascriptValidationHandler('prototype');
				$form->setValidator($validator);
			} else {
				$form->unsetValidator();
			}

			if(!$record->canEdit() || $record->IsDeletedFromStage) {
				$readonlyFields = $form->Fields()->makeReadonly();
				$form->setFields($readonlyFields);
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


	public function addpage($data, $form) {
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

	/*
	 * Return a dropdown for selecting reports
	 */
	function ReportSelector() {
		$reports = ClassInfo::subclassesFor("SideReport");

		// $options[""] = _t('CMSMain.CHOOSEREPORT',"(Choose a report)");
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
		
		return new GroupedDropdownField("ReportSelector", _t('CMSMain.REPORT', 'Report'),$finalOptions);
	}
	function ReportFormParameters() {
		$reports = ClassInfo::subclassesFor("SideReport");

		$forms = array();
		foreach($reports as $report) {
			if ($report != 'SideReport' && singleton($report)->canView()) {
				if ($fieldset = singleton($report)->getParameterFields()) {
					$formHtml = '';
					foreach($fieldset as $field) {
						$formHtml .= $field->FieldHolder();
					}
					$forms[$report] = $formHtml;
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
		$report = ClassInfo::exists($reportClass) ? new $reportClass() : false;
		$report->setParams($this->request->requestVars());
		return $report ? $report->getHTML() : false;
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

			$templateData = $this->customise(array(
				"EditForm" => $form
			));

			SSViewer::setOption('rewriteHashlinks', false);
			
			if(Director::is_ajax()) {
				$result = $templateData->renderWith($this->class . '_right');
				$parts = split('</?form[^>]*>', $result);
				return $parts[sizeof($parts)-2];
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
			if ($fieldset = singleton($batchAction)->getParameterFields()) {
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
		
		$actions = new FieldSet(
			new FormAction("addpage", _t('CMSMain.GO',"Go"))
		);

		$form = new Form($this, "AddPageOptionsForm", $fields, $actions);
		
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
					'getfilteredsubtree', 
					_t('CMSMain_left.ss.SEARCH', 'Search')
				)
			)
		);
		$form->unsetValidator();
		
		return $form;
	}
	
	/**
	 * @return Form
	 */
	function BatchActionsForm() {
		$actions = $this->batchactions()->batchActionList();
		$actionsMap = array();
		foreach($actions as $action) $actionsMap[$action->Link] = $action->Title;
		
		$form = new Form(
			$this,
			'BatchActionsForm',
			new FieldSet(
				new LiteralField(
					'Intro',
					sprintf('<p><small>%s</small></p>',
						_t(
							'CMSMain_left.ss.SELECTPAGESACTIONS',
							'Select the pages that you want to change &amp; then click an action:'
						)
					)
				),
				new HiddenField('csvIDs'),
				new DropdownField(
					'Action',
					false,
					$actionsMap
				)
			),
			new FieldSet(
				// TODO i18n
				new FormAction('submit', "Go")
			)
		);
		$form->addExtraClass('actionparams');
		$form->unsetValidator();
		
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

class CMSMainMarkingFilter {
	
	/**
	 * @var array Request params (unsanitized)
	 */
	protected $params = array();
	
	/**
	 * @param array $params Request params (unsanitized)
	 */
	function __construct($params = null) {
		$this->ids = array();
		$this->expanded = array();
		$this->params = $params;
		
		$where = array();
		
		$SQL_params = Convert::raw2sql($this->params);
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
					if($val != 'All') {
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
