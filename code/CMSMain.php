<?php
/**
 * The main "content" area of the CMS.
 * This class creates a 2-frame layout - left-tree and right-form - to sit beneath the main
 * admin menu.
 * @todo Create some base classes to contain the generic functionality that will be replicated.
 */
class CMSMain extends LeftAndMain implements CurrentPageIdentifier, PermissionProvider {
	
	static $tree_class = "SiteTree";
	
	static $subitem_class = "Member";
	
	/**
	 * SiteTree Columns that can be filtered using the the Site Tree Search button
	 */
	static $site_tree_filter_options = array(
		'ClassName' => 'Page Type', 
		'Status' => 'Status', 
		'MetaDescription' => 'Description', 
		'MetaKeywords' => 'Keywords'
	);

	public function init() {
		parent::init();

		// We don't want this showing up in every ajax-response, it should always be present in a CMS-environment
		if(!Director::is_ajax()) {
			Requirements::javascriptTemplate("cms/javascript/tinymce.template.js", array(
				"ContentCSS" => (SSViewer::current_theme() ? "themes/" . SSViewer::current_theme() : project()) . "/css/editor.css",
				"BaseURL" => Director::absoluteBaseURL(),
				"Lang" => i18n::get_tinymce_lang()
			));
		}
		
		Requirements::javascript('cms/javascript/CMSMain.js');
		Requirements::javascript('cms/javascript/CMSMain_left.js');
		Requirements::javascript('cms/javascript/CMSMain_right.js');
		Requirements::javascript('sapphire/javascript/UpdateURL.js');
		
		/**
		 * HACK ALERT: Project-specific requirements
		 * 
		 * We need a better way of including all of the CSS that *might* be used by this application.
		 * Perhaps the ajax responses can include some instructions to go get more CSS / JavaScript?
		 */
		Requirements::css("mot/css/WorkflowWidget.css");
		Requirements::css("survey/css/SurveyFilter.css");
		Requirements::javascript("survey/javascript/SurveyResponses.js");
		Requirements::javascript("survey/javascript/FormResponses.js");
		Requirements::javascript("parents/javascript/NotifyMembers.js");
		Requirements::css("tourism/css/SurveyCMSMain.css");
		Requirements::javascript("tourism/javascript/QuotasReport.js");
		Requirements::javascript("sapphire/javascript/ReportField.js");
		Requirements::javascript("ptraining/javascript/BookingList.js");
		Requirements::javascript("forum/javascript/ForumAccess.js");
		Requirements::javascript('gallery/javascript/GalleryPage_CMS.js');
	}

	//------------------------------------------------------------------------------------------//
	// Main controllers

	//------------------------------------------------------------------------------------------//
	// Main UI components

	/**
	 * Return the entire site tree as a nested set of ULs
	 */
	public function SiteTreeAsUL() {
		$this->generateDataTreeHints();
		$this->generateTreeStylingJS();

		return $this->getSiteTreeFor("SiteTree");
	}

	/**
	 * Returns the SiteTree columns that can be filtered using the the Site Tree Search button as a DataObjectSet
	 */
	public function SiteTreeFilterOptions() {
		$filter_options = new DataObjectSet();
		foreach(self::$site_tree_filter_options as $key => $value) {
   			$record = array(
				'Column' => $key,
				'Title' => $value,
			);
			$filter_options->push(new ArrayData($record));
		}
		return $filter_options;
	}
		public function SiteTreeFilterDateField() {
			$dateField = new CalendarDateField('SiteTreeFilterDate');
			return $dateField->Field();
		}

	/**
	 * Returns a filtered Site Tree
	 */
	public function filterSiteTree() {
		$className = 'SiteTree';
		$rootID = null;
		$obj = $rootID ? $this->getRecord($rootID) : singleton($className);
		$obj->setMarkingFilterFunction('cmsMainMarkingFilterFunction');
		$obj->markPartialTree();

		if($p = $this->currentPage()) $obj->markToExpose($p);

		// getChildrenAsUL is a flexible and complex way of traversing the tree
		$siteTree = $obj->getChildrenAsUL("", '
					"<li id=\"record-$child->ID\" class=\"" . $child->CMSTreeClasses($extraArg) . "\">" .
					"<a href=\"" . Director::link(substr($extraArg->Link(),0,-1), "show", $child->ID) . "\" " . (($child->canEdit() || $child->canAddChildren()) ? "" : "class=\"disabled\"") . " title=\"' . _t('LeftAndMain.PAGETYPE') . '".$child->class."\" >" .
					($child->TreeTitle()) .
					"</a>"
'
					,$this, true);

		// Wrap the root if needs be.

		if(!$rootID) {
			$rootLink = $this->Link() . '0';
			$siteTree = "<ul id=\"sitetree\" class=\"tree unformatted\"><li id=\"record-0\" class=\"Root nodelete\"><a href=\"$rootLink\">" .
				 _t('LeftAndMain.TREESITECONTENT',"Site Content",PR_HIGH,'Root node on left') . "</a>"
				. $siteTree . "</li></ul>";
		}

		return $siteTree;

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
			$def[$class]['defaultParent'] = isset(DataObject::get_by_url($obj->defaultParent())->ID) ? DataObject::get_by_url($obj->defaultParent())->ID : null;

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
	 * Populates an array of classes in the CMS which allows the
	 * user to change the page type.
	 */
	public function PageTypes() {
		$classes = ClassInfo::getValidSubClasses();
		array_shift($classes);
		$result = new DataObjectSet();
		$kill_ancestors[] = null;

		// figure out if there are any classes we don't want to appear
		foreach($classes as $class) {
		    $instance = singleton($class);

		    // do any of the progeny want to hide an ancestor?
            if ($ancestor_to_hide = $instance->stat('hide_ancestor')){
    		    // note for killing later
    		    $kill_ancestors[] = $ancestor_to_hide;
			}
		}

        // If any of the descendents don't want any of the elders to show up, cruelly render the elders surplus to requirements.
        if ($kill_ancestors) {
            foreach ($kill_ancestors as $mark) {
    		    // unset from $classes
			    unset($classes[$mark]);
            }
        }

		foreach($classes as $class) {
		    $instance = singleton($class);
            if($instance instanceof HiddenClass) continue;

			if( !$instance->canCreate() ) continue;

			// skip this type if it is restricted
			if($instance->stat('need_permission') && !$this->can( singleton($class)->stat('need_permission') ) ) continue;

			$addAction = $instance->uninherited('add_action', true);
			if($addAction) {
				// backwards compatibility for actions like "a page" (instead of "page")
				$addAction = preg_replace('/^a /','',$addAction);				
				$addAction = ucfirst($addAction);
			} else {
				$addAction = $class;
			}

			$result->push(new ArrayData(array(
				"ClassName" => $class,
				"AddAction" => $addAction,
			)));
		}
		return $result;
	}

	/**
	 * Get a databsae record to be managed by the CMS
	 */
	public function getRecord($id) {

		$treeClass = $this->stat('tree_class');

		if($id && is_numeric($id)) {
			$record = DataObject::get_one( $treeClass, "`$treeClass`.ID = $id");

			if(!$record) {
				// $record = Versioned::get_one_by_stage($treeClass, "Live", "`$treeClass`.ID = $id");
				Versioned::reading_stage('Live');
				singleton($treeClass)->flushCache();
				$record = DataObject::get_one( $treeClass, "`$treeClass`.ID = $id");
				if($record) {
					$record->DeletedFromStage = true;
				} else {
					Versioned::reading_stage(null);
				} 
			}
			return $record;

		} else if(substr($id,0,3) == 'new') {
			return $this->getNewItem($id);
		}
	}

	public function getEditForm($id) {
		$record = $this->getRecord($id);

		if($record) {
			if($record->DeletedFromStage) $record->Status = _t('CMSMain.REMOVEDFD',"Removed from the draft site");

			$fields = $record->getCMSFields($this);
			if ($fields == null) {
				user_error("getCMSFields returned null on a 'Page' object - it should return a FieldSet object. Perhaps you forgot to put a return statement at the end of your method?", E_USER_ERROR);
			}
			$fields->push($idField = new HiddenField("ID"));
			$fields->push($liveURLField = new HiddenField("LiveURLSegment"));
			$fields->push($stageURLField = new HiddenField("StageURLSegment"));

			/*if( substr($record->ID, 0, 3 ) == 'new' )*/
			$fields->push(new HiddenField('Sort','', $record->Sort ));

			$idField->setValue($id);
			
			if($record->ID && is_numeric( $record->ID ) ) {
				$liveRecord = Versioned::get_one_by_stage('SiteTree', 'Live', "`SiteTree`.ID = $record->ID");
				if($liveRecord) $liveURLField->setValue($liveRecord->AbsoluteLink());
			}
			
			if(!$record->DeletedFromStage) {
				$stageURLField->setValue($record->AbsoluteLink());
			}
			
			// getAllCMSActions can be used to completely redefine the action list
			if($record->hasMethod('getAllCMSActions')) {
				$actions = $record->getAllCMSActions();
			} else {
				$actions = new FieldSet();

				if($record->DeletedFromStage) {
					if($record->can('CMSEdit')) {
						$actions->push(new FormAction('revert',_t('CMSMain.RESTORE','Restore')));
						$actions->push(new FormAction('deletefromlive',_t('CMSMain.DELETEFP','Delete from the published site')));
					}
				} else {
					if($record->hasMethod('getCMSActions')) {
						$extraActions = $record->getCMSActions();
						if($extraActions) foreach($extraActions as $action) $actions->push($action);
					}

					if($record->canEdit()) {
						$actions->push(new FormAction('save',_t('CMSMain.SAVE','Save')));
					}
				}
			}

			$form = new Form($this, "EditForm", $fields, $actions);
			$form->loadDataFrom($record);
			$form->disableDefaultAction();

			if(!$record->canEdit() || $record->DeletedFromStage) $form->makeReadonly();

			return $form;
		} else if($id) {
			return new Form($this, "EditForm", new FieldSet(
				new LabelField(_t('CMSMain.PAGENOTEXISTS',"This page doesn't exist"))), new FieldSet());

		}
	}



	//------------------------------------------------------------------------------------------//
	// Data saving handlers


	public function addpage() {
		$className = $_REQUEST['PageType'] ? $_REQUEST['PageType'] : "Page";
		$parent = $_REQUEST['ParentID'] ? $_REQUEST['ParentID'] : 0;
		$suffix = $_REQUEST['Suffix'] ? "-" . $_REQUEST['Suffix'] : null;


		if(is_numeric($parent)) $parentObj = DataObject::get_by_id("SiteTree", $parent);
		if(!$parentObj || !$parentObj->ID) $parent = 0;

		$p = $this->getNewItem("new-$className-$parent".$suffix, false);
		$p->write();

		$p->CheckedPublicationDifferences = $p->AddedToStage = true;
		return $this->returnItemToUser($p);
	}

	public function getNewItem($id, $setID = true) {
		list($dummy, $className, $parentID, $suffix) = explode('-',$id);
		if (!Translatable::is_default_lang()) {
			$originalItem = Translatable::get_original($className,Session::get("{$id}_originalLangID"));
			if ($setID) $originalItem->ID = $id;
			else {
				$originalItem->ID = null;
				Translatable::creating_from(Session::get($id.'_originalLangID'));
			}
			return $originalItem;
		}
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

		if($newItem->fieldExists('Sort')) {
			$newItem->Sort = DB::query("SELECT MAX(Sort)  FROM SiteTree WHERE ParentID = '" . Convert::raw2sql($parentID) . "'")->value() + 1;
		}

		if( Member::currentUser() )
			$newItem->OwnerID = Member::currentUser()->ID;

		if($setID) $newItem->ID = $id;

		return $newItem;
	}

	public function Link($action = null) {
		return "admin/$action";
	}

	public function deletefromlive($urlParams, $form) {
		$id = $_REQUEST['ID'];
		Versioned::reading_stage('Live');
		$record = DataObject::get_by_id("SiteTree", $id);
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
		} else {
			$descRemoved = '';
		}

		$title = Convert::raw2js($record->Title);
		FormResponse::add($this->deleteTreeNodeJS($record));
		FormResponse::status_message("Deleted '$title'$descRemoved from live site", 'good');

		return FormResponse::respond();
	}

	/**
	 * Actually perform the publication step
	 */
	public function performPublish($record) {
		$record->AssignedToID = 0;
		$record->RequestedByID = 0;
		$record->Status = "Published";
		//$record->PublishedByID = Member::currentUser()->ID;
		$record->write();
		$record->publish("Stage", "Live");

		Sitemap::ping();

		// Fix the sort order for this page's siblings
		DB::query("UPDATE SiteTree_Live
			INNER JOIN SiteTree ON SiteTree_Live.ID = SiteTree.ID
			SET SiteTree_Live.Sort = SiteTree.Sort
			WHERE SiteTree_Live.ParentID = " . sprintf('%d', $record->ParentID));
	}

	public function revert($urlParams, $form) {
		$id = $_REQUEST['ID'];

		Versioned::reading_stage('Live');
		$obj = DataObject::get_by_id("SiteTree", $id);
		Versioned::reading_stage('Stage');
		$obj->publish("Live", "Stage");

		$title = Convert::raw2js($obj->Title);
		FormResponse::get_page($id);
		FormResponse::add("$('sitetree').setNodeTitle($id, '$title');");
		FormResponse::status_message(sprintf(_t('CMSMain.RESTORED',"Restored '%s' successfully",PR_MEDIUM,'Param %s is a title'),$title),'good');

		return FormResponse::respond();
	}

	public function delete($urlParams, $form) {
		$id = $_REQUEST['ID'];
		$record = DataObject::get_one("SiteTree", "SiteTree.ID = $id");
		$record->delete();
		Director::redirectBack();
	}

	//------------------------------------------------------------------------------------------//
	// Workflow handlers

	/**
	 * Send this page on to another user for review
	 */
	function submit() {

		$page = DataObject::get_by_id("SiteTree", $_REQUEST['ID']);
		$recipient = DataObject::get_by_id("Member", $_REQUEST['RecipientID']);
		if(!$recipient) user_error("CMSMain::submit() Can't find recipient #$_REQUEST[RecipientID]", E_USER_ERROR);

		$comment = new WorkflowPageComment();
		$comment->Comment = $_REQUEST['Message'];
		$comment->PageID = $page->ID;
		$comment->AuthorID = Member::currentUserID();
		$comment->Recipient = $recipient;
		$comment->Action = $_REQUEST['Status'];
		$comment->write();

		$emailData = $page->customise(array(
			"Message" => $_REQUEST['Message'],
			"Recipient" => $recipient,
			"Sender" => Member::currentUser(),
			"ApproveLink" => "admin/approve/$page->ID",
			"EditLink" => "admin/show/$page->ID",
			"StageLink" => "$page->URLSegment/?stage=Stage",
		));

		$email = new Page_WorkflowSubmitEmail();
		$email->populateTemplate($emailData);
		$email->send();

		$page->AssignedToID = $recipient->ID;
		$page->RequestedByID = Member::currentUserID();
		$page->Status = $_REQUEST['Status'];
		$page->writeWithoutVersion();

		FormResponse::status_message(sprintf(_t('CMSMain.SENTTO',"Sent to %s %s for approval.",PR_LOW,"First param is first name, and second is surname"),
											 $recipient->FirstName, $recipient->Surname), "good");

		return FormResponse::respond();
	}

	function getpagemembers() {
		$relationName = $_REQUEST['SecurityLevel'];
		$pageID = $this->urlParams['ID'];
		$page = DataObject::get_by_id('SiteTree',$pageID);
		if($page) {
			foreach($page->$relationName() as $editorGroup) $groupIDs[] = $editorGroup->ID;
			if($groupIDs) {
				$groupList = implode(", ", $groupIDs);
				$members = DataObject::get("Member","","",
					"INNER JOIN `Group_Members` ON `Group_Members`.MemberID = `Member`.ID AND `Group_Members`.GroupID IN ($groupList)");
			}

			if($members) {

				if( $page->RequestedByID )
					$members->shift( $page->RequestedBy() );

				foreach($members as $editor) {
					$options .= "<option value=\"$editor->ID\">$editor->FirstName $editor->Surname ($editor->Email)</option>";
				}
			} else {
				$options = "<option>(no-one available)</option>";
			}

			return <<<HTML
			<label class="left">Send to</label>
			<select name="RecipientID">$options</select>
HTML;
		} else {
			user_error("CMSMain::getpagemembers() Cannot find page #$pageID", E_USER_ERROR);
		}
	}

	function getMembersByGroup() {

		$group = DataObject::get_by_id("Group", $this->urlParams['ID']);
		if($group){
			$memberList = new MemberList('Users', $group);
			$memberList->setController($this);
			return $memberList->renderWith('MemberList');
		}else{
			return user_error("CMSMain::getpagemembers() Cannot find Group #$group->ID", E_USER_ERROR);
		}

	}

	function addmember() {
		SecurityAdmin::addmember($this->stat('subitem_class'));
	}

	function tasklist() {
		$tasks = DataObject::get("Page", "AssignedToID = " . Member::currentUserID(), "Created DESC");
		if($tasks) {
			$data = new ArrayData(array(
				"Tasks" => $tasks,
				"Message" => sprintf(_t('CMSMain.WORKTODO',"You have work to do on these <b>%d</b> pages."),$tasks->Count()),
			));
		} else {
			$data = new ArrayData(array(
				"Message" => _t('CMSMain.NOTHINGASSIGNED',"You have nothing assigned to you."),
			));
		}
		return $data->renderWith("TaskList");
	}

	function waitingon() {
		$tasks = DataObject::get("Page", "RequestedByID = " . Member::currentUserID(), "Created DESC");
		if($tasks) {
			$data = new ArrayData(array(
				"Tasks" => $tasks,
				"Message" => sprintf(_t('CMSMain.WAITINGON',"You are waiting on other people to work on these <b>%d</b> pages."),$tasks->Count()),
			));
		} else {
			$data = new ArrayData(array(
				"Message" => _t('CMSMain.NOWAITINGON','You aren\'t waiting on anybody.'),
			));
		}
		return $data->renderWith("WaitingOn");
	}

	function comments() {
		if($this->urlParams['ID']) {
			$comments = DataObject::get("WorkflowPageComment", "PageID = " . $this->urlParams['ID'], "Created DESC");
			$data = new ArrayData(array(
				"Comments" => $comments,
			));
			return $data->renderWith("CommentList");
		}
	}

	/**
	 * Return a dropdown for selecting reports
	 */
	function ReportSelector() {
		$reports = ClassInfo::subclassesFor("SideReport");

		$options[""] = _t('CMSMain.CHOOSEREPORT',"(Choose a report)");
		foreach($reports as $report) {
			if($report != 'SideReport') $options[$report] = singleton($report)->title();
		}
		return new DropdownField("ReportSelector","Report",$options);
	}
	/**
	 * Get the content for a side report
	 */
	function sidereport() {
		$reportClass = $this->urlParams['ID'];
		$report = new $reportClass();
		return $report->getHTML();
	}
	/**
	 * Get the versions of the current page
	 */
	function versions() {
		$pageID = $this->urlParams['ID'];
		$page = $this->getRecord($pageID);
		if($page) {
			$versions = $page->allVersions($_REQUEST['unpublished'] ? "" : "`SiteTree`.WasPublished = 1");
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
	function rollback() {
		if(isset($_REQUEST['Version']) && (bool)$_REQUEST['Version']) {
			$record = $this->performRollback($_REQUEST['ID'], $_REQUEST['Version']);
			echo sprintf(_t('CMSMain.ROLLEDBACKVERSION',"Rolled back to version #%d.  New version number is #%d"),$_REQUEST['Version'],$record->Version);
		} else {
			$record = $this->performRollback($_REQUEST['ID'], "Live");
			echo sprintf(_t('CMSMain.ROLLEDBACKPUB',"Rolled back to published version. New version number is #%d"),$record->Version);
		}
	}

	function unpublish() {
		$SQL_id = Convert::raw2sql($_REQUEST['ID']);

		$page = DataObject::get_by_id("SiteTree", $SQL_id);
		$page->deleteFromStage('Live');
		$page->flushCache();

		$page = DataObject::get_by_id("SiteTree", $SQL_id);
		$page->Status = "Unpublished";
		$page->write();

		Sitemap::ping();

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

		$JS_stageURL = Convert::raw2js(DB::query("SELECT URLSegment FROM SiteTree WHERE ID = $page->ID")->value());
		$JS_liveURL = Convert::raw2js(DB::query("SELECT URLSegment FROM SiteTree_Live WHERE ID = $page->ID")->value());
		FormResponse::add($this->getActionUpdateJS($page));
		FormResponse::update_status($page->Status);
		FormResponse::add("\$('sitetree').setNodeTitle($page->ID, '$JS_title')");
		FormResponse::status_message($statusMessage, 'good');
		FormResponse::add("$('Form_EditForm').elements.StageURLSegment.value = '$JS_stageURL'");
		FormResponse::add("$('Form_EditForm').elements.LiveURLSegment.value = '$JS_liveURL'");
		FormResponse::add("$('Form_EditForm').notify('PagePublished', $('Form_EditForm').elements.ID.value);");

		return FormResponse::respond();
	}

	function performRollback($id, $version) {
		$record = DataObject::get_by_id($this->stat('tree_class'), $id);
		$record->publish($version, "Stage", true);
		$record->AssignedToID = 0;
		$record->RequestedByID = 0;
		$record->Status = "Saved (update)";
		$record->writeWithoutVersion();
		return $record;
	}

	function getversion() {
		$id = $this->urlParams['ID'];
		$version = str_replace('&ajax=1','',$this->urlParams['OtherID']);
		$record = Versioned::get_version("SiteTree", $id, $version);

		if($record) {
			$fields = $record->getCMSFields($this);
			$fields->removeByName("Status");

			$fields->push(new HiddenField("ID"));
			$fields->push(new HiddenField("Version"));
			$fields->push(new HeaderField(sprintf(_t('CMSMain.VIEWING',"You are viewing version #%d, created %s"),
														  $version, $record->obj('LastEdited')->Ago())));

			$actions = new FieldSet(
				new FormAction("email", _t('CMSMain.EMAIL',"Email")),
				new FormAction("print", _t('CMSMain.PRINT',"Print")),
				new FormAction("rollback", _t('CMSMain.ROLLBACK',"Roll back to this version"))
			);

			// encode the message to appear in the body of the email
			$archiveURL = Director::absoluteBaseURL() . $record->URLSegment . '?archiveDate=' . $record->obj('LastEdited')->URLDatetime();
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
			$form->makeReadonly();

			$templateData = $this->customise(array(
				"EditForm" => $form
			));

			SSViewer::setOption('rewriteHashlinks', false);
			$result = $templateData->renderWith($this->class . '_right');
			$parts = split('</?form[^>]*>', $result);
			return $parts[sizeof($parts)-2];
		}
	}

	function compareversions() {
		$id = $this->urlParams['ID'];
		$version1 = $_REQUEST['From'];
		$version2 = $_REQUEST['To'];

		if( $version1 > $version2 ) {
			$toVersion = $version1;
			$fromVersion = $version2;
		} else {
			$toVersion = $version2;
			$fromVersion = $version1;
		}

		$page = DataObject::get_by_id("SiteTree", $id);
		$record = $page->compareVersions($fromVersion, $toVersion);
		if($record) {
			$fields = $record->getCMSFields($this);
			$fields->push(new HiddenField("ID"));
			$fields->push(new HiddenField("Version"));
			$fields->insertBefore(new HeaderField(sprintf(_t('CMSMain.COMPARINGV',"You are comparing versions #%d and #%d"),$fromVersion,$toVersion)), "Root");

			$actions = new FieldSet();

			$form = new Form($this, "EditForm", $fields, $actions);
			$form->loadDataFrom($record);
			$form->loadDataFrom(array(
				"ID" => $id,
				"Version" => $version,
			));
			$form->makeReadonly();
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
		Requirements::css('cms/css/dialog.css');
		Requirements::javascript('jsparty/prototype.js');
		Requirements::javascript('jsparty/behaviour.js');
		Requirements::javascript('jsparty/prototype_improvements.js');
		Requirements::javascript('cms/javascript/dialog.js');

		$message = _t('CMSMain.COPYPUBTOSTAGE',"Do you really want to copy the published content to the stage site?");
		$buttons = "<button name=\"OK\">" . _t('CMSMain.OK','OK') ."</button><button name=\"Cancel\">" . _t('CMSMain.CANCEL',"Cancel") . "</button>";

		return $this->customise( array(
			'Message' => $message,
			'Buttons' => $buttons,
			'DialogType' => 'alert'
		))->renderWith('Dialog');
	}

	/**
	 * Publishes a number of items.
	 * Called by AJAX
	 */
	public function publishitems() {
		// This method can't be called without ajax.
		if(!Director::is_ajax()) {
			Director::redirectBack();
			return;
		}

		$ids = split(' *, *', $_REQUEST['csvIDs']);

		$notifications = array();

		$idList = array();

		// make sure all the ids are numeric.
		// Add all the children to the list of IDs if they are missing
		foreach($ids as $id) {
			$brokenPageList = '';
			if(is_numeric($id)) {
				$record = DataObject::get_by_id($this->stat('tree_class'), $id);

				if($record) {
					// Publish this page
					$this->performPublish($record);

					// Now make sure the 'changed' icon is removed
					$publishedRecord = DataObject::get_by_id($this->stat('tree_class'), $id);
					$JS_title = Convert::raw2js($publishedRecord->TreeTitle());
					FormResponse::add("\$('sitetree').setNodeTitle($id, '$JS_title');");
					FormResponse::add("$('Form_EditForm').reloadIfSetTo($record->ID);");
					$record->destroy();
					unset($record);
				}
			}
		}

		if (sizeof($ids) > 1) $message = sprintf(_t('CMSMain.PAGESPUB', "%d pages published "), sizeof($ids));
		else $message = sprintf(_t('CMSMain.PAGEPUB', "%d page published "), sizeof($ids));

		FormResponse::add('statusMessage("'.$message.'","good");');

		return FormResponse::respond();
	}

	/**
	 * Delete a number of items.
	 * This code supports notification
	 */
	public function deleteitems() {
		// This method can't be called without ajax.
		if(!Director::is_ajax()) {
			Director::redirectBack();
			return;
		}

		$ids = split(' *, *', $_REQUEST['csvIDs']);

		$notifications = array();

		$idList = array();

		// make sure all the ids are numeric.
		// Add all the children to the list of IDs if they are missing
		foreach($ids as $id) {
			$brokenPageList = '';
			if(is_numeric($id)) {
				$record = DataObject::get_by_id($this->stat('tree_class'), $id);

				// if(!$record) Debug::message( "Can't find record #$id" );

				if($record) {

					// add all the children for this record if they are not already in the list
					// this check is a little slower but will prevent circular dependencies
					// (should they exist, which they probably shouldn't) from causing
					// the function to not terminate
					$children = $record->AllChildren();

					if( $children )
						foreach( $children as $child )
							if( array_search( $child->ID, $ids ) !== FALSE )
								$ids[] = $child->ID;

					if($record->hasMethod('BackLinkTracking')) {
						$brokenPages = $record->BackLinkTracking();
						foreach($brokenPages as $brokenPage) {
							$brokenPageList .= "<li style=\"font-size: 65%\">" . $brokenPage->Breadcrumbs(3, true) . "</li>";
							$brokenPage->HasBrokenLink = true;
							$notifications[$brokenPage->OwnerID][] = $brokenPage;
							$brokenPage->writeWithoutVersion();
						}
					}

					$record->delete();
					$record->destroy();

					// DataObject::delete_by_id($this->stat('tree_class'), $id);
					$record->CheckedPublicationDifferences = $record->DeletedFromStage = true;

					// check to see if the record exists on the live site, if it doesn't remove the tree node
					// $_REQUEST['showqueries'] = 1 ;
					$liveRecord = Versioned::get_one_by_stage( $this->stat('tree_class'), 'Live', "`{$this->stat('tree_class')}`.`ID`={$id}");

					if($liveRecord) {
						$title = Convert::raw2js($record->TreeTitle());
						FormResponse::add("$('sitetree').setNodeTitle($record->OldID, '$title');");
						FormResponse::add("$('Form_EditForm').reloadIfSetTo($record->OldID);");
					} else {
						FormResponse::add("var node = $('sitetree').getTreeNodeByIdx('$id');");
						FormResponse::add("if(node.parentTreeNode)	node.parentTreeNode.removeTreeNode(node);");
						FormResponse::add("$('Form_EditForm').reloadIfSetTo($record->OldID);");
					}
				}
			}
		}

		if($notifications) foreach($notifications as $memberID => $pages) {
			if(class_exists('Page_BrokenLinkEmail')) {
				$email = new Page_BrokenLinkEmail();
				$email->populateTemplate(new ArrayData(array(
					"Recipient" => DataObject::get_by_id("Member", $memberID),
					"BrokenPages" => new DataObjectSet($pages),
				)));
				$email->debug();
				$email->send();
			}
		}

		if (sizeof($ids) > 1) $message = sprintf(_t('CMSMain.PAGESDEL', "%d pages deleted "), sizeof($ids));
		else $message = sprintf(_t('CMSMain.PAGEDEL', "%d page deleted "), sizeof($ids));
		if(isset($brokenPageList) && $brokenPageList != '') {
			$message .= _t('CMSMain.NOWBROKEN',"  The following pages now have broken links:")."<ul>" . addslashes($brokenPageList) . "</ul>" . _t('CMSMain.NOWBROKEN2',"Their owners have been emailed and they will fix up those pages.");
		}

		FormResponse::add('statusMessage("'.$message.'","good");');

		return FormResponse::respond();
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

		return new Form($this, "AddPageOptionsForm", new FieldSet(
			new HiddenField("ParentID"),
			new DropdownField("PageType", "", $pageTypes)
			// "Page to copy" => new TreeDropdownField("DuplicateSection", "", "SiteTree"),
		),
		new FieldSet(
			new FormAction("addpage", _t('CMSMain.GO',"Go"))
		));
	}

	/**
	 * Helper function to get page count
	 */
	function getpagecount() {
		ini_set('max_execution_time',300);
		$excludePages = split(" *, *", $_GET['exclude']);

		$pages = DataObject::get("SiteTree", "ParentID = 0");
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
		ini_set("memory_limit","100M");
		ini_set('max_execution_time', 300);

		if(isset($_POST['confirm'])) {
			$pages = DataObject::get("SiteTree");
			$count = 0;
			foreach($pages as $page) {
				$this->performPublish($page);
				$page->destroy();
				unset($page);
				$count++;
				echo "<li>$count";
			}

			echo sprintf(_t('CMSMain.PUBPAGES',"Done: Published %d pages"), $count);

		} else {
			echo '<h1>' . _t('CMSMain.PUBALLFUN','"Publish All" functionality') . '</h1>
				<p>' . _t('CMSMain.PUBALLFUN2', 'Pressing this button will do the equivalent of going to every page and pressing "publish".  It\'s
				intended to be used after there have been massive edits of the content, such as when the site was
				first built.') . '</p>
				<form method="post" action="publishall">
					<input type="submit" name="confirm" value="'
					. _t('CMSMain.PUBALLCONFIRM',"Please publish every page in the site, copying content stage to live",PR_LOW,'Confirmation button') .'" />
				</form>';
		}
	}

	function restorepage() {
		if($id = $this->urlParams['ID']) {
			$restoredPage = Versioned::get_latest_version("SiteTree", $id);
			$restoredPage->ID = $restoredPage->RecordID;
			if(!DB::query("SELECT ID FROM SiteTree WHERE ID = $restoredPage->ID")->value()) {
				DB::query("INSERT INTO SiteTree SET ID = $restoredPage->ID");
			}
			$restoredPage->forceChange();
			$restoredPage->writeWithoutVersion();
			Debug::show($restoredPage);
		}	else {
			echo _t('CMSMain.VISITRESTORE',"visit restorepage/(ID)",PR_LOW,'restorepage/(ID) should not be translated (is an URL)');
		}
	}

	function duplicate() {
		if(($id = $this->urlParams['ID']) && is_numeric($id)) {
			$page = DataObject::get_by_id("SiteTree", $id);

			$newPage = $page->duplicate();

			return $this->returnItemToUser($newPage);
		} else {
			user_error("CMSMain::duplicate() Bad ID: '$id'", E_USER_WARNING);
		}
	}
	
	/**
	 * Switch the cms language and reload the site tree
	 *
	 */
	function switchlanguage($lang, $donotcreate = null) {
		//is it's a clean switch (to an existing language deselect the current page)
		if (is_string($lang)) $dontunloadPage = true;
		$lang = (is_string($lang) ? $lang : urldecode($this->urlParams['ID']));
		if ($lang != Translatable::default_lang()) {
			Translatable::set_reading_lang(Translatable::default_lang());
			$tree_class = $this->stat('tree_class');
			$obj = new $tree_class;
			$allIDs = $obj->getDescendantIDList();
			$allChildren = $obj->AllChildren();
			$classesMap = $allChildren->map('ID','ClassName');
			$titlesMap = $allChildren->map();
			Translatable::set_reading_lang($lang);
			$obj = new $tree_class;
			$languageIDs = $obj->getDescendantIDList();
			$notcreatedlist = array_diff($allIDs,$languageIDs);
			FormResponse::add("$('addpage').getElementsByTagName('button')[0].disabled=true;");
			FormResponse::add("$('Form_AddPageOptionsForm').getElementsByTagName('div')[1].getElementsByTagName('input')[0].disabled=true;");
			FormResponse::add("$('Translating_Message').innerHTML = 'Translating mode - ".i18n::get_language_name($lang)."';");
			FormResponse::add("Element.removeClassName('Translating_Message','nonTranslating');");
		} else {
			Translatable::set_reading_lang($lang);
			FormResponse::add("$('addpage').getElementsByTagName('button')[0].disabled=false;");
			FormResponse::add("$('Form_AddPageOptionsForm').getElementsByTagName('div')[1].getElementsByTagName('input')[0].disabled=false;");
			FormResponse::add("Element.addClassName('Translating_Message','nonTranslating');");
		}
		$obj = singleton($this->stat('tree_class'));
		$obj->markPartialTree();
		$siteTree = $obj->getChildrenAsUL("", '
					"<li id=\"record-$child->ID\" class=\"" . $child->CMSTreeClasses($extraArg) . "\">" .
					"<a href=\"" . Director::link(substr($extraArg->Link(),0,-1), "show", $child->ID) . "\" " . (($child->canEdit() || $child->canAddChildren()) ? "" : "class=\"disabled\"") . " title=\"' . _t('LeftAndMain.PAGETYPE') . '".$child->class."\" >" .
					($child->TreeTitle()) .
					"</a>"
'
					,$this, true);

		$rootLink = $this->Link() . '0';
		$siteTree = "<li id=\"record-0\" class=\"Root nodelete\"><a href=\"$rootLink\">" .
			 _t('LeftAndMain.SITECONTENT') . "</a>"
			. $siteTree . "</li></ul>";
		FormResponse::add("$('sitetree').innerHTML ='". ereg_replace("[\n]","\\\n",$siteTree) ."';");
		FormResponse::add("SiteTree.applyTo('#sitetree');");
		if (isset($notcreatedlist)) {
			foreach ($notcreatedlist as $notcreated) {
				if ($notcreated == $donotcreate) continue;
				$id = "new-{$classesMap[$notcreated]}-0-$notcreated";
				Session::set($id . '_originalLangID',$notcreated);
				$treeTitle = Convert::raw2js($titlesMap[$notcreated]);	
				$response = <<<JS
					var tree = $('sitetree');
					var newNode = tree.createTreeNode("$id", "$treeTitle", "$classesMap[$notcreated] (untranslated)");
					addClass(newNode, 'untranslated');
					node = tree.getTreeNodeByIdx(0);
					node.open();
					node.appendTreeNode(newNode);
JS;
				FormResponse::add($response);
			}
		}
		if (!isset($dontunloadPage)) FormResponse::add("node = $('sitetree').getTreeNodeByIdx(0); node.selectTreeNode();");
		return FormResponse::respond();
	}
	
	/**
	 * Create a new translation from an existing item, switch to this language and reload the tree.
	 */
	function createtranslation () {
		if(!Director::is_ajax()) {
			Director::redirectBack();
			return;
		}
		$langCode = $_REQUEST['newlang'];
		$langName = i18n::get_language_name($langCode);
		$originalLangID = $_REQUEST['ID'];

		$record = $this->getRecord($originalLangID);
	    $temporalID = "new-$record->RecordClassName-$record->ParentID-$originalLangID";
		Session::set($temporalID . '_originalLangID',$originalLangID);
		$tree = $this->switchlanguage($langCode, $originalLangID);
		FormResponse::add(<<<JS
		if (Element.hasClassName('LangSelector_holder','onelang')) {
			Element.removeClassName('LangSelector_holder','onelang');
			$('treepanes').resize();
		}
		if ($('LangSelector').options['$langCode'] == undefined) {
			var option = document.createElement("option");
			option.text = '$langName';
			option.value = '$langCode';
			$('LangSelector').options.add(option);
		}
JS
		);
		FormResponse::add("$('LangSelector').selectValue('$langCode');");
		$newrecord = clone $record;
		$newrecord->ID = $temporalID;

		$newrecord->CheckedPublicationDifferences = $newrecord->AddedToStage = true;
		return $this->returnItemToUser($newrecord);
	}

	// HACK HACK HACK - Dont remove without telling simon ;-)

	/**
	 * This is only used by parents inc.
	 * TODO Work out a better way of handling control to the individual page objects.
	 */
	function sethottip($data,$form) {
		$page = DataObject::get_by_id("SiteTree", $_REQUEST['ID']);
		return $page->sethottip($data,$form);
	}
	/**
	 * This is only used by parents inc.
	 * TODO Work out a better way of handling control to the individual page objects.
	 */
	function notifyInvitation($data,$form) {
		$page = DataObject::get_by_id("SiteTree", $_REQUEST['ID']);
		return $page->notifyInvitation($data,$form);
	}
	function testInvitation($data,$form) {
		$page = DataObject::get_by_id("SiteTree", $_REQUEST['ID']);
		return $page->testInvitation($data,$form);
	}

	/**
	 * Provide the permission codes used by LeftAndMain.
	 * Can't put it on LeftAndMain since that's an abstract base class.
	 */
	function providePermissions() {
		$classes = ClassInfo::subclassesFor('LeftAndMain');

		foreach($classes as $class) {
			$perms["CMS_ACCESS_" . $class] = "Access to $class in CMS";
		}
		return $perms;
	}
	/**
	 * Return a dropdown with existing languages
	 */
	function LangSelector() {
		$langs = i18n::get_existing_content_languages('SiteTree');
				
		return new DropdownField("LangSelector","Language",$langs,Translatable::current_lang());
	}

	/**
	 * Determine if there are more than one languages in our site tree
	 */
	function MultipleLanguages() {
		$langs = i18n::get_existing_content_languages('SiteTree');

		return (count($langs) > 1);
	}
	
	/**
	 * Get the name of the language that we are translating in
	 */
	function EditingLang() {
		if(!Translatable::is_default_lang()) {
			return i18n::get_language_name(Translatable::current_lang());
		} else {
			return false;
		}
	}
}

// TODO: Find way to put this in a class
function cmsMainMarkingFilterFunction($node) {
	// Expand all nodes
	// $node->markingFinished();
	// Don't ever hide nodes with children, because otherwise if one of their children matches the search, it wouldn't be shown.
	if($node->AllChildrenIncludingDeleted()->count() > 0) {
		// Open all nodes with children so it is easy to see any children that match the search.
 		$node->markOpened();
		return true;
	} else {
		$failed_filter = false;
		// First check for the generic search term in the URLSegment, Title, MenuTitle, & Content
		if (!empty($_REQUEST['SiteTreeSearchTerm'])) {
			// For childless nodes, show only those matching the filter
			$filter = strtolower($_REQUEST['SiteTreeSearchTerm']);
			if ( strpos( strtolower($node->URLSegment) , $filter) === false
				&& strpos( strtolower($node->Title) , $filter) === false
				&& strpos( strtolower($node->MenuTitle) , $filter) === false
				&& strpos( strtolower($node->Content) , $filter) === false) {
				$failed_filter = true;
			}
		}
		// Check the 'Edited Since' date
		if (!empty($_REQUEST['SiteTreeFilterDate'])) {
			$edited_since =  mktime(0, 0, 0, substr($_REQUEST['SiteTreeFilterDate'], 3, 2), 
						substr($_REQUEST['SiteTreeFilterDate'], 0, 2), substr($_REQUEST['SiteTreeFilterDate'], 6, 4));
			if ( strtotime($node->LastEdited) < $edited_since ) {
				$failed_filter = true;
			}
		}
		// Now check if a specified Criteria attribute matches
		foreach (CMSMain::$site_tree_filter_options as $key => $value)
		{
			if (!empty($_REQUEST[$key])) {
				$parameterName = $key;
				$filter = strtolower($_REQUEST[$key]);
				// Show node only if the filter string exists anywere in the filter paramater (ignoring case)
				if (strpos( strtolower($node->$parameterName) , $filter) === false) {
					$failed_filter = true;
				}
			}
		}
		// Each filter must match or it fails
		if (true == $failed_filter) {
			return false;
		} else {
			return true;
		}
	}
}

?>