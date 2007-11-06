<?php

/**
 * LeftAndMain is the parent class of all the two-pane views in the CMS.
 * If you are wanting to add more areas to the CMS, you can do it by subclassing LeftAndMain.
 */
abstract class LeftAndMain extends Controller {
	static $tree_class = null;
	static $extra_menu_items = array(), $removed_menu_items = array(), $replaced_menu_items = array();
	static $ForceReload;

	function init() {
		Director::set_site_mode('cms');
		
		// set language
		$member = Member::currentUser();
		if(!empty($member->Locale)) {
			i18n::set_locale($member->Locale);
		}

		parent::init();
		
		// Allow customisation of the access check by a decorator
		if($this->hasMethod('alternateAccessCheck')) {
			$isAllowed = $this->alternateAccessCheck();
			
		// Default security check for LeftAndMain sub-class permissions
		} else {
			$isAllowed = Permission::check("CMS_ACCESS_$this->class");
			if(!$isAllowed && $this->class == 'CMSMain') {
				// When access /admin/, we should try a redirect to another part of the admin rather than be locked out
				$menu = $this->MainMenu();
				if(($first = $menu->First()) && $first->Link) {
					Director::redirect($first->Link);
				}
			}
		}

		// Don't continue if there's already been a redirection request.
		if(Director::redirected_to()) return;

		// Access failure!		
		if(!$isAllowed) {
			$messageSet = array(
				'default' => _t('LeftAndMain.PERMDEFAULT',"Please choose an authentication method and enter your credentials to access the CMS."),
				'alreadyLoggedIn' => _t('LeftAndMain.PERMALREADY',"I'm sorry, but you can't access that part of the CMS.  If you want to log in as someone else, do so below"),
				'logInAgain' => _t('LeftAndMain.PERMAGAIN',"You have been logged out of the CMS.  If you would like to log in again, enter a username and password below."),
			);

			Security::permissionFailure($this, $messageSet);
			return;
		}
		
		Requirements::javascript('jsparty/prototype.js');
		Requirements::javascript('jsparty/behaviour.js');
		Requirements::javascript('jsparty/prototype_improvements.js');
		Requirements::javascript('jsparty/loader.js');
		Requirements::javascript('jsparty/hover.js');
		Requirements::javascript('jsparty/layout_helpers.js');

		Requirements::javascript(MCE_ROOT . 'tiny_mce_src.js');
		Requirements::javascript('jsparty/tiny_mce_improvements.js');

		Requirements::javascript('jsparty/scriptaculous/effects.js');
		Requirements::javascript('jsparty/scriptaculous/dragdrop.js');
		Requirements::javascript('jsparty/scriptaculous/controls.js');

		Requirements::css('jsparty/greybox/greybox.css');
		Requirements::javascript('jsparty/greybox/AmiJS.js');
		Requirements::javascript('jsparty/greybox/greybox.js');
		
		Requirements::javascript('jsparty/tree/tree.js');
		Requirements::css('jsparty/tree/tree.css');

		Requirements::javascript('jsparty/tabstrip/tabstrip.js');
		Requirements::css('jsparty/tabstrip/tabstrip.css');
		
		Requirements::css('cms/css/TinyMCEImageEnhancement.css');
		Requirements::javascript('cms/javascript/TinyMCEImageEnhancement.js');
		
		Requirements::javascript('cms/javascript/LeftAndMain.js');
		Requirements::javascript('cms/javascript/LeftAndMain_left.js');
		Requirements::javascript('cms/javascript/LeftAndMain_right.js');
	
		Requirements::css('sapphire/css/Form.css');

		Requirements::javascript('cms/javascript/MemberList.js');
		Requirements::javascript('cms/javascript/ForumAdmin.js');
		Requirements::javascript('cms/javascript/SideTabs.js');
		Requirements::javascript('cms/javascript/TaskList.js');
		Requirements::javascript('cms/javascript/CommentList.js');
		Requirements::javascript('cms/javascript/SideReports.js');
		Requirements::javascript('cms/javascript/LangSelector.js');
		Requirements::javascript('cms/javascript/TranslationTab.js');
		Requirements::javascript('sapphire/javascript/Validator.js');
		Requirements::javascript('sapphire/javascript/UniqueFields.js');
		Requirements::javascript('sapphire/javascript/RedirectorPage.js');
		Requirements::javascript('sapphire/javascript/DataReport.js' );
		Requirements::css('sapphire/css/SubmittedFormReportField.css');

		Requirements::javascript('sapphire/javascript/FieldEditor.js');
		Requirements::css('sapphire/css/FieldEditor.css');

		Requirements::css('sapphire/css/TableListField.css');
		Requirements::css('sapphire/css/ComplexTableField.css');
		Requirements::javascript('sapphire/javascript/TableListField.js');
		Requirements::javascript('sapphire/javascript/TableField.js');
		Requirements::javascript('sapphire/javascript/ComplexTableField.js');
		Requirements::javascript('sapphire/javascript/RelationComplexTableField.js');
		
		Requirements::css('sapphire/css/TreeDropdownField.css');
		Requirements::css('sapphire/css/CheckboxSetField.css');
		
		Requirements::javascript('jsparty/calendar/calendar.js');
		Requirements::javascript('jsparty/calendar/lang/calendar-en.js');
		Requirements::javascript('jsparty/calendar/calendar-setup.js');
		Requirements::css('sapphire/css/CalendarDateField.css');
		Requirements::css('jsparty/calendar/calendar-win2k-1.css');
		
		Requirements::css('sapphire/javascript/DropdownTimeField.js');
		Requirements::css('sapphire/css/DropdownTimeField.css');
		Requirements::css('sapphire/css/PopupDateTimeField.css');
		
		Requirements::javascript('sapphire/javascript/SelectionGroup.js');
		Requirements::css('sapphire/css/SelectionGroup.css');
		
		Requirements::javascript('jsparty/SWFUpload/SWFUpload.js');
		Requirements::javascript('cms/javascript/Upload.js');
		
		Requirements::themedCSS('typography');
		
		// For Widgets
		Requirements::css('cms/css/WidgetAreaEditor.css');
		Requirements::javascript('cms/javascript/WidgetAreaEditor.js');
		
		// For Blog
		Requirements::javascript('blog/javascript/bbcodehelp.js');

		Requirements::javascript("sapphire/javascript/Security_login.js");

		$dummy = null;
		$this->extend('augmentInit', $dummy);
	}

	/**
	 * Returns true if the current user can access the CMS
	 */
	function canAccessCMS() {

		$member = Member::currentUser();

		if($member) {
			if($groups = $member->Groups()) {
				foreach($groups as $group) if($group->CanCMS) return true;
			}
		}

		return false;
	}

	/**
	 * Returns true if the current user has administrative rights in the CMS
	 */
	function canAdminCMS() {
		if($member = Member::currentUser()) return $member->isAdmin();
	}

	//------------------------------------------------------------------------------------------//
	// Main controllers

	/**
	 * You should implement a Link() function in your subclass of LeftAndMain,
	 * to point to the URL of that particular controller.
	 */
	abstract public function Link();

	public function show($params) {
		if($params['ID']) $this->setCurrentPageID($params['ID']);
		if(isset($params['OtherID']))
			Session::set('currentMember', $params['OtherID']);

		if(Director::is_ajax()) {
			SSViewer::setOption('rewriteHashlinks', false);
			return $this->EditForm()->formHtmlContent();

		} else {
			return array();
		}
	}


	public function getitem() {
		$this->setCurrentPageID($_REQUEST['ID']);
		SSViewer::setOption('rewriteHashlinks', false);
		// Changed 3/11/2006 to not use getLastFormIn because that didn't have _form_action, _form_name, etc.

		$form = $this->EditForm();
		if($form) return $form->formHtmlContent();
		else return "";
	}
	public function getLastFormIn($html) {
		$parts = split('</?form[^>]*>', $html);
		return $parts[sizeof($parts)-2];
	}

	//------------------------------------------------------------------------------------------//
	// Main UI components

	/**
	 * Returns the main menu of the CMS.  This is also used by init() to work out which sections the user
	 * has access to.
	 */
	public function MainMenu() {
		// Don't accidentally return a menu if you're not logged in - it's used to determine access.
		if(!Member::currentUserID()) return new DataObjectSet();

		// Built-in modules

		// array[0]: Name of the icon
		// array[1]: URL to visi
		// array[2]: The controller class for this menu, used to check permisssions.  If blank, it's assumed that this is public, and always shown to
		//           users who have the rights to access some other part of the admin area.
		$menuSrc = array(
			_t('LeftAndMain.SITECONTENT',"Site Content",PR_HIGH,"Menu title") => array("content", "admin/", "CMSMain"),
			_t('LeftAndMain.FILESIMAGES',"Files & Images",PR_HIGH,"Menu title") => array("files", "admin/assets/", "AssetAdmin"),
			_t('LeftAndMain.NEWSLETTERS',"Newsletters",PR_HIGH,"Menu title") => array("newsletter", "admin/newsletter/", "NewsletterAdmin"),
			_t('LeftAndMain.REPORTS',"Reports",PR_HIGH,'Menu title') => array("report", "admin/reports/", "ReportAdmin"),
			_t('LeftAndMain.SECURITY',"Security",PR_HIGH,'Menu title') => array("security", "admin/security/", "SecurityAdmin"),
			_t('LeftAndMain.COMMENTS',"Comments",PR_HIGH,'Menu title') => array("comments", "admin/comments/", "CommentAdmin"),
			_t('LeftAndMain.STATISTICS',"Statistics",PR_HIGH,'Menu title') => array("statistics", "admin/statistics/", "StatisticsAdmin"),
			_t('LeftAndMain.HELP',"Help",PR_HIGH,'Menu title') => array("help", "http://userhelp.silverstripe.com"),
		);

		if(!$this->hasReports()) unset($menuSrc[_t('LeftAndMain.REPORTS')]);

		// Extra modules
		if($removed = $this->stat('removed_menu_items')) {
			foreach($removed as $remove) {
				foreach($menuSrc as $k => $v) {
					if($v[0] == $remove) {
						unset($menuSrc[$k]);
						break;
					}
				}
			}
		}

		// replace menu items
		if($replaced = $this->stat('replaced_menu_items') ) {

			$newMenu = array();

			reset( $menuSrc );

			for( $i = 0; $i < count( $menuSrc ); $i++ ) {
				$existing = current($menuSrc);

				if( $replacement = $replaced[$existing[0]] ) {
					$newMenu = array_merge( $newMenu, $replacement );
				} else
					$newMenu = array_merge( $newMenu, array( key( $menuSrc) => current( $menuSrc ) ) );

				next( $menuSrc );
			}

			reset( $menuSrc );

			$menuSrc = $newMenu;
		}

		// Extra modules
		if($extra = $this->stat('extra_menu_items')) {
			foreach($extra as $k => $v)  {
				if(!is_array($v)) $extra[$k] = array($k, $v, 'title' => $k);
				else $extra[$k]['title'] = $k;
			}

			array_splice($menuSrc, count($menuSrc)-2, 0, $extra);
		}

		// Encode into DO set
		$menu = new DataObjectSet();
		$itemsWithPermission = 0;
		foreach($menuSrc as $title => $menuItem) {
			if(is_numeric($title) && isset($menuItem['title'])) $title = $menuItem['title'];

			if(isset($menuItem[2])) {
				if($this->hasMethod('alternateMenuDisplayCheck')) $isAllowed = $this->alternateMenuDisplayCheck($menuItem[2]);
				else $isAllowed = Permission::check("CMS_ACCESS_" . $menuItem[2]);
			} else {
				$isAllowed = true;
			}

			if($isAllowed) {
				// Count up the number of items that have specific permission settings
				if(isset($menuItem[2])) $itemsWithPermission++;

				$linkingmode = "";
				if(!(strpos($this->Link(), $menuItem[1]) === false)) {
					if($menuItem[0] == "content") {
						if($this->Link() == "admin/")
							$linkingmode = "current";
					}
					else
						$linkingmode = "current";
				}

				$menu->push(new ArrayData(array(
					"Title" => Convert::raw2xml($title),
					"Code" => $menuItem[0],
					"Link" => $menuItem[1],
					"LinkingMode" => $linkingmode
				)));
			}
		}

		// Only return a menu if there is at least one permission-requiring item.  Otherwise, the menu will just be the "help" icon.
		if($itemsWithPermission > 0) return $menu;
		else return new DataObjectSet();
	}

  /**
   * Return a list of appropriate templates for this class, with the given suffix
   */
  protected function getTemplatesWithSuffix($suffix) {
    $classes = array_reverse(ClassInfo::ancestry($this->class));
    foreach($classes as $class) {
      $templates[] = $class . $suffix;
      if($class == 'LeftAndMain') break;
    }
    return $templates;
  }

	public function Left() {
		return $this->renderWith($this->getTemplatesWithSuffix('_left'));
	}
	public function Right() {
		return $this->renderWith($this->getTemplatesWithSuffix('_right'));
	}
	public function RightBottom() {
		if(SSViewer::hasTemplate($this->getTemplatesWithSuffix('_rightbottom'))) {
			return $this->renderWith($this->getTemplatesWithSuffix('_rightbottom'));
		}
	}


	public function getRecord($id, $className = null) {
		if(!$className) $className = $this->stat('tree_class');
		return DataObject::get_by_id($className, $rootID);
	}

	function getSiteTreeFor($className, $rootID = null) {
		$obj = $rootID ? $this->getRecord($rootID) : singleton($className);
		$obj->markPartialTree();
		if($p = $this->currentPage()) $obj->markToExpose($p);

		// getChildrenAsUL is a flexible and complex way of traversing the tree
		$siteTree = $obj->getChildrenAsUL("", '
					"<li id=\"record-$child->ID\" class=\"" . $child->CMSTreeClasses($extraArg) . "\">" .
					"<a href=\"" . Director::link(substr($extraArg->Link(),0,-1), "show", $child->ID) . "\" " . (($child->canEdit() || $child->canAddChildren()) ? "" : "class=\"disabled\"") . " title=\"' . _t('LeftAndMain.PAGETYPE','Page type: ') . '".$child->class."\" >" .
					($child->TreeTitle()) .
					"</a>"
'
					,$this, true);

		// Wrap the root if needs be.

		if(!$rootID) {
			$rootLink = $this->Link() . '0';
			
			// This lets us override the tree title with an extension
			if($this->hasMethod('getCMSTreeTitle')) $treeTitle = $this->getCMSTreeTitle();
			else $treeTitle =  _t('LeftAndMain.TREESITECONTENT',"Site Content",PR_HIGH,'Root node on left');
			
			$siteTree = "<ul id=\"sitetree\" class=\"tree unformatted\"><li id=\"record-0\" class=\"Root nodelete\"><a href=\"$rootLink\"><strong>$treeTitle</strong></a>"
				. $siteTree . "</li></ul>";
		}

		return $siteTree;
	}

	public function getsubtree() {
		$results = $this->getSiteTreeFor($this->stat('tree_class'), $_REQUEST['ID']);
		return substr(trim($results), 4,-5);
	}

	/**
	 * Allows you to returns a new data object to the tree (subclass of sitetree)
	 * and updates the tree via javascript.
	 */
	public function returnItemToUser($p) {
		if(Director::is_ajax()) {
			// Prepare the object for insertion.
			$parentID = (int)$p->ParentID;
			$id = $p->ID ? $p->ID : "new-$p->class-$p->ParentID";
			$treeTitle = Convert::raw2js($p->TreeTitle());
			$hasChildren = is_numeric( $id ) && $p->AllChildren() ? ' unexpanded' : '';

			// Ensure there is definitly a node avaliable. if not, append to the home tree.
			$response = <<<JS
				var tree = $('sitetree');
				var newNode = tree.createTreeNode("$id", "$treeTitle", "{$p->class}{$hasChildren}");
				node = tree.getTreeNodeByIdx($parentID);
				if(!node){	node = tree.getTreeNodeByIdx(0); }
				node.open();
				node.appendTreeNode(newNode);
				newNode.selectTreeNode();
JS;
			FormResponse::add($response);
		}

		return FormResponse::respond();
	}


	/**
	 * Save and Publish page handler
	 */
	public function save($urlParams, $form) {
		$className = $this->stat('tree_class');
		$result = '';

		$SQL_id = Convert::raw2sql($_REQUEST['ID']);
		if(substr($SQL_id,0,3) != 'new') {
			$record = DataObject::get_one($className, "`$className`.ID = {$SQL_id}");
		} else {
			$record = $this->getNewItem($SQL_id, false);
		}

		// We don't want to save a new version if there are no changes
		$dataFields_new = $form->Fields()->dataFields();
		$dataFields_old = $record->getAllFields();
		$changed = false;
		$hasNonRecordFields = false;
		foreach($dataFields_new as $datafield) {
			// if the form has fields not belonging to the record
			if(!isset($dataFields_old[$datafield->Name()])) {
				$hasNonRecordFields = true;
			}
			// if field-values have changed
			if(!isset($dataFields_old[$datafield->Name()]) || $dataFields_old[$datafield->Name()] != $datafield->dataValue()) {
				$changed = true;
			}
		}

		if(!$changed && !$hasNonRecordFields) {
			// Tell the user we have saved even though we haven't, as not to confuse them
			if(is_a($record, "Page")) {
				$record->Status = "Saved (update)";
			}
			FormResponse::status_message(_t('LeftAndMain.SAVEDUP',"Saved"), "good");
			FormResponse::update_status($record->Status);
			return FormResponse::respond();
		}

		$form->dataFieldByName('ID')->Value = 0;

		if(isset($urlParams['Sort']) && is_numeric($urlParams['Sort'])) {
			$record->Sort = $urlParams['Sort'];
		}

		// HACK: This should be turned into something more general
		$originalClass = $record->ClassName;
		$originalStatus = $record->Status;

		$record->HasBrokenLink = 0;
		$record->HasBrokenFile = 0;

		$record->writeWithoutVersion();

		// HACK: This should be turned into something more general
		$originalURLSegment = $record->URLSegment;

		$form->saveInto($record, true);

		if(is_a($record, "Page")) {
			$record->Status = ($record->Status == "New page" || $record->Status == "Saved (new)") ? "Saved (new)" : "Saved (update)";
		}



		// $record->write();

		if(Director::is_ajax()) {
			if($SQL_id != $record->ID) {
				FormResponse::add("$('sitetree').setNodeIdx(\"{$SQL_id}\", \"$record->ID\");");
				FormResponse::add("$('Form_EditForm').elements.ID.value = \"$record->ID\";");
			}

			if($added = DataObjectLog::getAdded('SiteTree')) {
				foreach($added as $page) {
					if($page->ID != $record->ID) $result .= $this->addTreeNodeJS($page);
				}
			}
			if($deleted = DataObjectLog::getDeleted('SiteTree')) {
				foreach($deleted as $page) {
					if($page->ID != $record->ID) $result .= $this->deleteTreeNodeJS($page);
				}
			}
			if($changed = DataObjectLog::getChanged('SiteTree')) {
				foreach($changed as $page) {
					if($page->ID != $record->ID) {
						$title = Convert::raw2js($page->TreeTitle());
						FormResponse::add("$('sitetree').setNodeTitle($page->ID, \"$title\")");
					}
				}
			}

			$message = _t('LeftAndMain.SAVEDUP');


			// Update the icon if the class has changed
			if($originalClass != $record->ClassName) {
				$record->setClassName( $record->ClassName );
				$newClass = $record->ClassName;
				$record = $record->newClassInstance( $newClass );

				FormResponse::add("if(\$('sitetree').setNodeIcon) \$('sitetree').setNodeIcon($record->ID, '$originalClass', '$record->ClassName');");
			}

			// HACK: This should be turned into somethign more general
			if( ($record->class == 'VirtualPage' && $originalURLSegment != $record->URLSegment) ||
				($originalClass != $record->ClassName) || self::$ForceReload == true) {
				FormResponse::add("$('Form_EditForm').getPageFromServer($record->ID);");
			}

			if( ($record->class != 'VirtualPage') && $originalURLSegment != $record->URLSegment) {
				$message .= sprintf(_t('LeftAndMain.CHANGEDURL',"  Changed URL to '%s'"),$record->URLSegment);
				FormResponse::add("\$('Form_EditForm').elements.URLSegment.value = \"$record->URLSegment\";");
				FormResponse::add("\$('Form_EditForm_StageURLSegment').value = \"{$record->URLSegment}\";");
			}

			// After reloading action
			if($originalStatus != $record->Status) {
				$message .= sprintf(_t('LeftAndMain.STATUSTO',"  Status changed to '%s'"),$record->Status);
			}

			$record->write();

			// If the 'Save & Publish' button was clicked, also publish the page
			if (isset($urlParams['publish']) && $urlParams['publish'] == 1) {
				$this->performPublish($record);
				
				$record->setClassName($record->ClassName);
				$newClass = $record->ClassName;
				$publishedRecord = $record->newClassInstance($newClass);

				return $this->tellBrowserAboutPublicationChange($publishedRecord, "Published '$record->Title' successfully");
			} else {
				// BUGFIX: Changed icon only shows after Save button is clicked twice http://support.silverstripe.com/gsoc/ticket/76
				$title = Convert::raw2js($record->TreeTitle());
				FormResponse::add("$('sitetree').setNodeTitle(\"$record->ID\", \"$title\");");
				$result .= $this->getActionUpdateJS($record);
				FormResponse::status_message($message, "good");
				FormResponse::update_status($record->Status);
				return FormResponse::respond();
			}
		}
	}

	/**
	 * Return a piece of javascript that will update the actions of the main form
	 */
	public function getActionUpdateJS($record) {
		// Get the new action buttons
		
		$tempForm = $this->getEditForm($record->ID);
		$actionList = '';
		foreach($tempForm->Actions() as $action) {
			$actionList .= $action->Field() . ' ';
		}

		FormResponse::add("$('Form_EditForm').loadActionsFromString('" . Convert::raw2js($actionList) . "');");

		return FormResponse::respond();
	}

	/**
	 * Return JavaScript code to generate a tree node for the given page, if visible
	 */
	public function addTreeNodeJS($page, $select = false) {
		$parentID = (int)$page->ParentID;
		$title = Convert::raw2js($page->TreeTitle());
		$response = <<<JS
var newNode = $('sitetree').createTreeNode($page->ID, "$title", "$page->class");
var parentNode = $('sitetree').getTreeNodeByIdx($parentID); 
if(parentNode) parentNode.appendTreeNode(newNode);
JS;
		$response .= ($select ? "newNode.selectTreeNode();\n" : "") ;
		FormResponse::add($response);
		return FormResponse::respond();
	}
	/**
	 * Return JavaScript code to remove a tree node for the given page, if it exists.
	 */
	public function deleteTreeNodeJS($page) {
		$id = $page->ID ? $page->ID : $page->OldID;
		$response = <<<JS
var node = $('sitetree').getTreeNodeByIdx($id);
if(node && node.parentTreeNode) node.parentTreeNode.removeTreeNode(node);
$('Form_EditForm').closeIfSetTo($id);
JS;
		FormResponse::add($response);
		return FormResponse::respond();
	}

	/**
	 * Sets a static variable on this class which means the panel will be reloaded.
	 */
	static function ForceReload(){
		self::$ForceReload = true;
	}

	/**
	 * Ajax handler for updating the parent of a tree node
	 */
	public function ajaxupdateparent() {
		$id = $_REQUEST['ID'];
		$parentID = $_REQUEST['ParentID'];
		if($parentID == 'root'){
			$parentID = 0;
		}
		$_REQUEST['ajax'] = 1;

		if(is_numeric($id) && is_numeric($parentID) && $id != $parentID) {
			$node = DataObject::get_by_id($this->stat('tree_class'), $id);
			if($node){
				$node->ParentID = $parentID;
				$node->Status = "Saved (update)";
				$node->write();

				if(is_numeric($_REQUEST['CurrentlyOpenPageID'])) {
					$currentPage = DataObject::get_by_id($this->stat('tree_class'), $_REQUEST['CurrentlyOpenPageID']);
					if($currentPage) {
						$cleanupJS = $currentPage->cmsCleanup_parentChanged();
					}
				}

				FormResponse::status_message(_t('LeftAndMain.SAVED','saved'), 'good');
				FormResponse::add($cleanupJS);

			}else{
				FormResponse::status_message(_t('LeftAndMain.PLEASESAVE',"Please Save Page: This page could not be upated because it hasn't been saved yet."),"good");
			}


			return FormResponse::respond();
		} else {
			user_error("Error in ajaxupdateparent request; id=$id, parentID=$parentID", E_USER_ERROR);
		}
	}

	/**
	 * Ajax handler for updating the order of a number of tree nodes
	 * $_GET[ID]: An array of node ids in the correct order
	 * $_GET[MovedNodeID]: The node that actually got moved
	 */
	public function ajaxupdatesort() {
		$className = $this->stat('tree_class');
		$counter = 0;
		$js = '';
		$_REQUEST['ajax'] = 1;

		if(is_array($_REQUEST['ID'])) {
			if($_REQUEST['MovedNodeID']==0){ //Sorting root
				$movedNode = DataObject::get($className, "`ParentID`=0");				
			}else{
				$movedNode = DataObject::get_by_id($className, $_REQUEST['MovedNodeID']);
			}
			foreach($_REQUEST['ID'] as $id) {
				if($id == $movedNode->ID) {
					$movedNode->Sort = ++$counter;
					$movedNode->Status = "Saved (update)";
					$movedNode->write();

					$title = Convert::raw2js($movedNode->TreeTitle());
					$js .="$('sitetree').setNodeTitle($movedNode->ID, \"$title\")\n";

				// Nodes that weren't "actually moved" shouldn't be registered as having been edited; do a direct SQL update instead
				} else if(is_numeric($id)) {
					++$counter;
					DB::query("UPDATE `$className` SET `Sort` = $counter WHERE `ID` = '$id'");
				}
			}
			// Virtual pages require selected to be null if the page is the same.
			FormResponse::add(
				"if( $('sitetree').selected && $('sitetree').selected[0]){
					var idx =  $('sitetree').selected[0].getIdx();
					if(idx){
						$('Form_EditForm').getPageFromServer(idx);
					}
				}\n" . $js
			);
			FormResponse::status_message(_t('LeftAndMain.SAVED'), 'good');
		} else {
			FormResponse::error(_t('LeftAndMain.REQUESTERROR',"Error in request"));
		}

		return FormResponse::respond();
	}

	/**
	 * Delete a number of items
	 */
	public function deleteitems() {
		$ids = split(' *, *', $_REQUEST['csvIDs']);

		$script = "st = \$('sitetree'); \n";
		foreach($ids as $id) {
			if(is_numeric($id)) {
				DataObject::delete_by_id($this->stat('tree_class'), $id);
				$script .= "node = st.getTreeNodeByIdx($id); if(node) node.parentTreeNode.removeTreeNode(node); $('Form_EditForm').closeIfSetTo($id); \n";
			}
		}
		FormResponse::add($script);

		return FormResponse::respond();
	}

	public function EditForm() {
		$id = isset($_REQUEST['ID']) ? $_REQUEST['ID'] : $this->currentPageID();
		if($id) return $this->getEditForm($id);
	}
	
	public function myprofile() {
		$form = $this->Member_ProfileForm();
		return $this->customise(array(
			'Form' => $form
		))->renderWith('BlankPage');
	}
	
	public function Member_ProfileForm() {
		return new Member_ProfileForm($this, 'Member_ProfileForm', Member::currentUser());
	}

	public function printable() {
		$id = $_REQUEST['ID'] ? $_REQUEST['ID'] : $this->currentPageID();

		if($id) $form = $this->getEditForm($id);
		$form->transform(new PrintableTransformation());
		$form->actions = null;

		Requirements::clear();
		Requirements::css('cms/css/LeftAndMain_printable.css');
		return array(
			"PrintForm" => $form
		);
	}

	public function currentPageID() {
		if(isset($_REQUEST['ID']) && is_numeric($_REQUEST['ID']))	{
			return $_REQUEST['ID'];
		} elseif (isset($this->urlParams['ID']) && is_numeric($this->urlParams['ID'])) {
			return $this->urlParams['ID'];
		} elseif(Session::get("{$this->class}.currentPage")) {
			return Session::get("{$this->class}.currentPage");
		} else {
			return null;
		}
	}

	public function setCurrentPageID($id) {
		Session::set("{$this->class}.currentPage", $id);
	}

	public function currentPage() {
		$id = $this->currentPageID();
		if($id && is_numeric($id)) {
			return DataObject::get_by_id($this->stat('tree_class'), $id);
		}
	}

	public function isCurrentPage(DataObject $page) {
		return $page->ID == Session::get("{$this->class}.currentPage");
	}

	/**
	 * Return the CMS's HTML-editor toolbar
	 */
	public function EditorToolbar() {
		return new HtmlEditorField_Toolbar($this, "EditorToolbar");
	}

	/**
	 * Return the version number of this application
	 */
	public function CMSVersion() {
		$sapphireVersionFile = file_get_contents('../sapphire/silverstripe_version');
		$jspartyVersionFile = file_get_contents('../jsparty/silverstripe_version');
		$cmsVersionFile = file_get_contents('../cms/silverstripe_version');

		if(strstr($sapphireVersionFile, "/sapphire/trunk")) {
			$sapphireVersion = "trunk";
		} else {
			preg_match("/sapphire\/(?:(?:branches)|(?:tags))(?:\/rc)?\/([A-Za-z0-9._-]+)\/silverstripe_version/", $sapphireVersionFile, $matches);
			$sapphireVersion = $matches[1];
		}

		if(strstr($jspartyVersionFile, "/jsparty/trunk")) {
			$jspartyVersion = "trunk";
		} else {
			preg_match("/jsparty\/(?:(?:branches)|(?:tags))(?:\/rc)?\/([A-Za-z0-9._-]+)\/silverstripe_version/", $jspartyVersionFile, $matches);
			$jspartyVersion = $matches[1];
		}

		if(strstr($cmsVersionFile, "/cms/trunk")) {
			$cmsVersion = "trunk";
		} else {
			preg_match("/cms\/(?:(?:branches)|(?:tags))(?:\/rc)?\/([A-Za-z0-9._-]+)\/silverstripe_version/", $cmsVersionFile, $matches);
			$cmsVersion = $matches[1];
		}

		if($sapphireVersion == $jspartyVersion && $jspartyVersion == $cmsVersion) {
			return $sapphireVersion;
		}	else {
			return "cms: $cmsVersion, sapphire: $sapphireVersion, jsparty: $jspartyVersion";
		}
	}

	/**
	 * The application name is customisable by calling
	 * LeftAndMain::setApplicationName("Something New")
	 */
	static $application_name = "SilverStripe CMS", $application_logo_text = "SilverStripe";
	static function setApplicationName($name, $logoText = null) {
		self::$application_name = $name;
		self::$application_logo_text = $logoText ? $logoText : $name;
	}
	function ApplicationName() {
		return self::$application_name;
	}
	function ApplicationLogoText() {
		return self::$application_logo_text;
	}

	static $application_logo = "cms/images/mainmenu/logo.gif", $application_logo_style = "";

	static function setLogo($logo, $logoStyle) {
		self::$application_logo = $logo;
		self::$application_logo_style = $logoStyle;
		self::$application_logo_text = "";
	}
	function LogoStyle() {
		return "background-image: url(" . self::$application_logo . ") no-repeat; " . self::$application_logo_style;
	}

	/**
	 * Determine if we have reports and need to display the Reports main menu item
	 *
	 * @return boolean
	 */
	function hasReports() {
		$subclasses = ClassInfo::subclassesFor('Report');
		foreach($subclasses as $class){
			if($class != 'Report') {
				return true;
			}
		}
		return false;
	}

	/**
	 * Return the base directory of the tiny_mce codebase
	 */
	function MceRoot() {
		return MCE_ROOT;
	}

	/**
	 * Use this as an action handler for custom CMS buttons.
	 */
	function callPageMethod($data, $form) {
		$methodName = $form->buttonClicked()->extraData();
		$record = $this->CurrentPage();
		return $record->$methodName($data, $form);		
	}
}

?>
