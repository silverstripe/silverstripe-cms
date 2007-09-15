<?php

/**
 * AssetAdmin is the 'file store' section of the CMS.
 * It provides an interface for maniupating the File and Folder objects in the system.
 */
class AssetAdmin extends LeftAndMain {
	static $tree_class = "File";

	public function Link($action=null) {
		if(!$action) $action = "index";
		return "admin/assets/$action/" . $this->currentPageID();
	}
	
	/**
	 * Return fake-ID "root" if no ID is found (needed to upload files into the root-folder)
	 */
	public function currentPageID() {
		if(isset($_REQUEST['ID']) && is_numeric($_REQUEST['ID']))	{
			return $_REQUEST['ID'];
		} elseif (is_numeric($this->urlParams['ID'])) {
			return $this->urlParams['ID'];
		} elseif(is_numeric(Session::get("{$this->class}.currentPage"))) {
			return Session::get("{$this->class}.currentPage");
		} else {
			return "root";
		}
	}

	/**
	 * Set up the controller, in particular, re-sync the File database with the assets folder./
	 */
	function init() {
		parent::init();

		// needed for MemberTableField (Requirements not determined before Ajax-Call)
		Requirements::javascript("sapphire/javascript/ComplexTableField.js");
		Requirements::css("jsparty/greybox/greybox.css");
		Requirements::css("sapphire/css/ComplexTableField.css");

		Requirements::javascript("cms/javascript/AssetAdmin.js");
		Requirements::javascript("cms/javascript/AssetAdmin_left.js");
		Requirements::javascript("cms/javascript/AssetAdmin_right.js");
		
		// Requirements::javascript('sapphire/javascript/TableListField.js');

		// Include the right JS]
		// Hayden: This didn't appear to be used at all
		/*$fileList = new FileList("Form_EditForm_Files", null);
		$fileList->setClick_AjaxLoad('admin/assets/getfile/', 'Form_SubForm');
		$fileList->FieldHolder();*/
		
		Requirements::javascript("jsparty/greybox/AmiJS.js");
		Requirements::javascript("jsparty/greybox/greybox.js");
		Requirements::css("jsparty/greybox/greybox.css");
	}
	
	/**
	 * Display the upload form.  Returns an iframe tag that will show admin/assets/uploadiframe.
	 */
	function getUploadIframe() {
		return <<<HTML
		<iframe name="AssetAdmin_upload" src="admin/assets/uploadiframe/{$this->urlParams['ID']}" id="AssetAdmin_upload" border="0" style="border-style: none; width: 100%; height: 200px">
		</iframe>
HTML;
	}

	function index() {
		File::sync();
		return array();		
	}

	/**
	 * Show the content of the upload iframe.  The form is specified by a template.
	 */
	function uploadiframe() {
		Requirements::clear();
		
		Requirements::javascript("jsparty/prototype.js");
		Requirements::javascript("jsparty/loader.js");
		Requirements::javascript("jsparty/behaviour.js");
		Requirements::javascript("jsparty/prototype_improvements.js");
		Requirements::javascript("jsparty/layout_helpers.js");
		Requirements::javascript("cms/javascript/LeftAndMain.js");
		Requirements::javascript("jsparty/multifile/multifile.js");
		Requirements::css("jsparty/multifile/multifile.css");
		Requirements::css("cms/css/typography.css");
		Requirements::css("cms/css/layout.css");
		Requirements::css("cms/css/cms_left.css");
		Requirements::css("cms/css/cms_right.css");
		
		if(isset($data['ID']) && $data['ID'] != 'root') $folder = DataObject::get_by_id("Folder", $data['ID']);
		else $folder = singleton('Folder');
		
		$canUpload = $folder->userCanEdit();
		
		return array( 'CanUpload' => $canUpload );
	}
	
	/**
	 * Return the form object shown in the uploadiframe.
	 */
	function UploadForm() {

		return new Form($this,'UploadForm', new FieldSet(
			new HiddenField("ID", "", $this->currentPageID()),
			// needed because the button-action is triggered outside the iframe
			new HiddenField("action_doUpload", "", "1"), 
			new FileField("Files[0]" , "Choose file "),
			new LiteralField('UploadButton',"
				<input type='submit' value='Upload Files Listed Below' name='action_upload' id='Form_UploadForm_action_upload' class='action' />
			"),
			new LiteralField('MultifileCode',"
				<p>Files ready to upload:</p>
				<div id='Form_UploadForm_FilesList'></div>
				<script>
					var multi_selector = new MultiSelector($('Form_UploadForm_FilesList'), null, $('Form_UploadForm_action_upload'));
					multi_selector.addElement($('Form_UploadForm_Files-0'));
				</script>
			")
		), new FieldSet(
		));

	}
	
	/**
	 * This method processes the results of the UploadForm.
	 * It will save the uploaded files to /assets/ and create new File objects as required.
	 */
	function doUpload($data, $form) {
		foreach($data['Files'] as $param => $files) {
			foreach($files as $key => $value) {
				$processedFiles[$key][$param] = $value;
			}
		}
		
		if($data['ID'] && $data['ID'] != 'root') $folder = DataObject::get_by_id("Folder", $data['ID']);
		else $folder = singleton('Folder');

		$newFiles = array();
		$fileSizeWarnings = '';
		$uploadErrors = '';
		
		foreach($processedFiles as $file) {
			if($file['error'] == UPLOAD_ERR_NO_TMP_DIR) {
				$status = 'bad';
				$statusMessage = 'There is no temporary folder for uploads. Please set upload_tmp_dir in php.ini.';
				break;
			}
		
			if($file['tmp_name']) {
				// Workaround open_basedir problems
				if(ini_get("open_basedir")) {
					$newtmp = TEMP_FOLDER . '/' . $file['name'];
					move_uploaded_file($file['tmp_name'], $newtmp);
					$file['tmp_name'] = $newtmp;
				}
			
				// check that the file can be uploaded and isn't too large
				
				$extensionIndex = strripos( $file['name'], '.' );
				$extension = strtolower( substr( $file['name'], $extensionIndex + 1 ) );
				
				if( $extensionIndex !== FALSE )
					list( $maxSize, $warnSize ) = File::getMaxFileSize( $extension );
				else
					list( $maxSize, $warnSize ) = File::getMaxFileSize();
				
				// check that the file is not too large or that the current user is an administrator
				if( $this->can('AdminCMS') || ( File::allowedFileType( $extension ) && (!isset($maxsize) || $file['size'] < $maxSize)))
					$newFiles[] = $folder->addUploadToFolder($file);
				elseif( !File::allowedFileType( $extension ) ) {
					$fileSizeWarnings .= "alert( 'Only administrators can upload $extension files.' );";
				} else {
					if( $file['size'] > 1048576 )
						$fileSize = "" . ceil( $file['size'] / 1048576 ) . "MB";
					elseif( $file['size'] > 1024 )
						$fileSize = "" . ceil( $file['size'] / 1024 ) . "KB";
					else
						$fileSize = "" . ceil( $file['size'] ) . "B";
											
								
					$fileSizeWarnings .= "alert( '\\'" . $file['name'] . "\\' is too large ($fileSize). Files of this type cannot be larger than $warnSize ' );";
				}
			}
		}
		
		if($newFiles) {
			$numFiles = sizeof($newFiles);
			$statusMessage = "Uploaded $numFiles files";
			$status = "good";
		} else if($status != 'bad') {
			$statusMessage = "There was nothing to upload";
			$status = "";
		}
		echo <<<HTML
			<script type="text/javascript">
			var form = parent.document.getElementById('Form_EditForm');
			form.getPageFromServer(form.elements.ID.value);
			parent.statusMessage("{$statusMessage}","{$status}");
			$fileSizeWarnings
			parent.document.getElementById('sitetree').getTreeNodeByIdx( "{$folder->ID}" ).getElementsByTagName('a')[0].className += ' contents';
			</script>
HTML;
	}
	
	/**
	 * Needs to be overridden to make sure an ID with value "0" is still valid (rootfolder)
	 */
	
	
	/**
	 * Return the form that displays the details of a folder, including a file list and fields for editing the folder name.
	 */
	function getEditForm($id) {
		if($id && $id != "root") {
			$record = DataObject::get_by_id("File", $id);
		} else {
			$record = singleton("Folder");
		}
		
		$fileList = new AssetTableField(
			$this,
			"Files",
			"File", 
			array("Title" => "Title", "LinkedURL" => "Filename"), 
			""
		);
		$fileList->setFolder($record);
		$fileList->setPopupCaption("View/Edit Asset");

		if($record) {
			$nameField = ($id != "root") ? new TextField("Name", "Folder Name") : new HiddenField("Name");
			$fields = new FieldSet(
				new HiddenField("Title"),
				new TabSet("Root", 
					new Tab("Files",
						$nameField,
						$fileList
					),
					new Tab("Details", 
						new ReadonlyField("URL"),
						new ReadonlyField("ClassName", "Type"),
						new ReadonlyField("Created", "First Uploaded"),
						new ReadonlyField("LastEdited", "Last Updated")
					),
					new Tab("Upload",
						new LiteralField("UploadIframe",
							$this->getUploadIframe()
						)
					)
				),
				new HiddenField("ID")
			);
			
			$actions = new FieldSet();
			
			if( $record->userCanEdit() ) {
				$actions = new FieldSet(
					new FormAction('deletemarked',"Delete files"),
					new FormAction('movemarked',"Move files..."),
					new FormAction('save',"Save")
				);
			}
			
			$form = new Form($this, "EditForm", $fields, $actions);
			if($record->ID) {
				$form->loadDataFrom($record);
			} else {
				$form->loadDataFrom(array(
					"ID" => "root",
					"URL" => Director::absoluteBaseURL() . 'assets/',
				));
			}
			
			// @todo: These workflow features aren't really appropriate for all projects
			if( Member::currentUser()->_isAdmin() && project() == 'mot' ) {
				$fields->addFieldsToTab( 'Root.Workflow', new DropdownField("Owner", "Owner", Member::map() ) );
				$fields->addFieldsToTab( 'Root.Workflow', new TreeMultiselectField("CanUse", "Content usable by") );
				$fields->addFieldsToTab( 'Root.Workflow', new TreeMultiselectField("CanEdit", "Content modifiable by") );
			}

			if( !$record->userCanEdit() )
				$form->makeReadonly();

			return $form;

		}
	}
	
	/**
	 * Returns the form used to specify options for the "move marked" action.
	 */
	public function MoveMarkedOptionsForm() {
		$folderDropdown = new TreeDropdownField("DestFolderID", "Move files to", "Folder");
		$folderDropdown->setFilterFunction(create_function('$obj', 'return $obj->class == "Folder";'));
		
		return new CMSActionOptionsForm($this, "MoveMarkedOptionsForm", new FieldSet(
			new HiddenField("ID"),
			new HiddenField("FileIDs"),
			$folderDropdown
		),
		new FieldSet(
			new FormAction("movemarked", "Move marked files")
		));
	}
	
	/**
	 * Perform the "move marked" action.
	 * Called by ajax, with a JavaScript return.
	 */
	public function movemarked() {
		if($_REQUEST['DestFolderID'] && is_numeric($_REQUEST['DestFolderID'])) {
			$destFolderID = $_REQUEST['DestFolderID'];
			$fileList = "'" . ereg_replace(' *, *',"','",trim(addslashes($_REQUEST['FileIDs']))) . "'";
			$numFiles = 0;
	
			if($fileList != "''") {
				$files = DataObject::get("File", "`File`.ID IN ($fileList)");
				if($files) {
					foreach($files as $file) {
						$file->ParentID = $destFolderID;
						$file->write();
						$numFiles++;
					}
				} else {
					user_error("No files in $fileList could be found!", E_USER_ERROR);
				}
			}
		
			echo <<<JS
				statusMessage("Moved $numFiles files");
JS;
		} else {
			user_error("Bad data: $_REQUEST[DestFolderID]", E_USER_ERROR);
		}
	}

	/**
	 * Returns the form used to specify options for the "delete marked" action.
	 * In actual fact, this form only has hidden fields and the button is auto-clickd without the
	 * form being displayed; it's just the most consistent way of providing this information to the
	 * CMS.
	 */
	public function DeleteMarkedOptionsForm() {
		return new CMSActionOptionsForm($this, "DeleteMarkedOptionsForm", new FieldSet(
			new HiddenField("ID"),
			new HiddenField("FileIDs")
		),
		new FieldSet(
			new FormAction("deletemarked", "Delete marked files")
		));
	}
	
	/**
	 * Perform the "delete marked" action.
	 * Called by ajax, with a JavaScript return.
	 */
	public function deletemarked() {
			$fileList = "'" . ereg_replace(' *, *',"','",trim(addslashes($_REQUEST['FileIDs']))) . "'";
			$numFiles = 0;
			$folderID = 0;
			$deleteList = '';
			$brokenPageList = '';
	
			if($fileList != "''") {
				$files = DataObject::get("File", "`File`.ID IN ($fileList)");
				if($files) {
					foreach($files as $file) {
						if($file instanceof Image) {
							$file->deleteFormattedImages();
						}
						if( !$folderID )
							$folderID = $file->ParentID;
						
						// $deleteList .= "\$('Form_EditForm_Files').removeById($file->ID);\n";
						$file->delete();
						$numFiles++;
					}
					if($brokenPages = Notifications::getItems("BrokenLink")) {
						$brokenPageList = "  These pages now have broken links:</ul>";
						foreach($brokenPages as $brokenPage) {
							$brokenPageList .= "<li style=&quot;font-size: 65%&quot;>" . $brokenPage->Breadcrumbs(3, true) . "</li>";
						}
						$brokenPageList .= "</ul>";
						Notifications::notifyByEmail("BrokenLink", "Page_BrokenLinkEmail");
					} else {
						$brokenPageList = '';
					}
					
					$deleteList = '';
					if( $folderID ) {
						$remaining = DB::query("SELECT COUNT(*) FROM `File` WHERE `ParentID`=$folderID")->value();
						
						if( !$remaining )
							$deleteList = "Element.removeClassName(\$('sitetree').getTreeNodeByIdx( '$folderID' ).getElementsByTagName('a')[0],'contents');";
					}
					
				} else {
					user_error("No files in $fileList could be found!", E_USER_ERROR);
				}
			}
		
			echo <<<JS
				$deleteList
				$('Form_EditForm').getPageFromServer($('Form_EditForm_ID').value);
				statusMessage("Deleted $numFiles files.$brokenPageList");
JS;
	}
	
	
	/**
	 * Returns the content to be placed in Form_SubForm when editing a file.
	 * Called using ajax.
	 */
	public function getfile() {
		SSViewer::setOption('rewriteHashlinks', false);

		// bdc: only try to return something if user clicked on an object
		if (is_object($this->getSubForm($this->urlParams['ID']))) {
			return $this->getSubForm($this->urlParams['ID'])->formHtmlContent();
		}
		else return null;
	}
	
	/**
	 * Action handler for the save button on the file subform.
	 * Saves the file
	 */
	public function savefile($data, $form) {
		$record = DataObject::get_by_id("File", $data['ID']);
		$form->saveInto($record);
		$record->write();
		$title = Convert::raw2js($record->Title);
		$name = Convert::raw2js($record->Name);
		echo <<<JS
			statusMessage('Saved file #$data[ID]');
			$('record-$data[ID]').getElementsByTagName('td')[1].innerHTML = "$title";
			$('record-$data[ID]').getElementsByTagName('td')[2].innerHTML = "$name";
JS;
	}
	
	/**
	 * Return the entire site tree as a nested set of ULs
	
*/
	public function SiteTreeAsUL() {
		$obj = singleton('Folder');
		$obj->setMarkingFilter("ClassName", "Folder");
		$obj->markPartialTree();

		if($p = $this->currentPage()) $obj->markToExpose($p);

		// getChildrenAsUL is a flexible and complex way of traversing the tree

		$siteTree = $obj->getChildrenAsUL("",

					' "<li id=\"record-$child->ID\" class=\"$child->class" . $child->markingClasses() .  ($extraArg->isCurrentPage($child) ? " current" : "") . "\">" . ' .

					' "<a href=\"" . Director::link(substr($extraArg->Link(),0,-1), "show", $child->ID) . "\" class=\"" . ($child->hasChildren() ? " contents" : "") . "\" >" . $child->Title . "</a>" ',

					$this, true);
					

		// Wrap the root if needs be.

		$rootLink = $this->Link() . 'show/root';

		if(!isset($rootID)) $siteTree = "<ul id=\"sitetree\" class=\"tree unformatted\"><li id=\"record-root\" class=\"Root\"><a href=\"$rootLink\">http://www.yoursite.com/assets</a>"

					. $siteTree . "</li></ul>";


		return $siteTree;

	}

	/**
	 * Returns a subtree of items underneat the given folder.
	 */
	public function getsubtree() {
		$obj = DataObject::get_by_id("Folder", $_REQUEST['ID']);
		$obj->setMarkingFilter("ClassName", "Folder");
		$obj->markPartialTree();

		$results = $obj->getChildrenAsUL("",

					' "<li id=\"record-$child->ID\" class=\"$child->class" . $child->markingClasses() .  ($extraArg->isCurrentPage($child) ? " current" : "") . "\">" . ' .

					' "<a href=\"" . Director::link(substr($extraArg->Link(),0,-1), "show", $child->ID) . "\" >" . $child->Title . "</a>" ',

					$this, true);

		return substr(trim($results), 4,-5);

	}
	

	//------------------------------------------------------------------------------------------//

	// Data saving handlers

	/**
	 * Add a new folder and return its details suitable for ajax.
	 */
	public function addfolder() {
		$parent = ($_REQUEST['ParentID'] && is_numeric($_REQUEST['ParentID'])) ? $_REQUEST['ParentID'] : 0;
		
		if($parent) {
			$parentObj = DataObject::get_by_id("File", $parent);
			if(!$parentObj || !$parentObj->ID) $parent = 0;
		}
		
		$p = new Folder();
		$p->ParentID = $parent;
		$p->Title = "NewFolder";

		$p->Name = "NewFolder";

		// Get the folder to be created		
		if(isset($parentObj->ID)) $filename = $parentObj->FullPath . $p->Name;
		else $filename = '../assets/' . $p->Name;
		
		// Ensure uniqueness		
		$i = 2;
		$baseFilename = $filename . '-';
		while(file_exists($filename)) {
			$filename = $baseFilename . $i;
			$p->Name = $p->Title = basename($filename);
			$i++;
		}
		
		// Actually create
		mkdir($filename);
		chmod($filename, 02775);

		$p->write();
	
	
		return $this->returnItemToUser($p);

	}
	
	/**
	 * Return the given tree item to the client.
	 * If called by ajax, this will be some javascript commands.
	 * Otherwise, it will redirect back.
	 */
	public function returnItemToUser($p) {
		if($_REQUEST['ajax']) {
			$parentID = (int)$p->ParentID;
			return <<<JS
				tree = $('sitetree');

				var newNode = tree.createTreeNode($p->ID, "$p->Title", "$p->class");

				tree.getTreeNodeByIdx($parentID).appendTreeNode(newNode);

				newNode.selectTreeNode();
JS;

		} else {

			Director::redirectBack();

		}
	}
	
	/**
	 * Delete a folder
	 */
	public function deletefolder() {
		$script = '';
		$ids = split(' *, *', $_REQUEST['csvIDs']);
		$script = '';
		foreach($ids as $id) {

			if(is_numeric($id)) {

				$record = DataObject::get_by_id($this->stat('tree_class'), $id);
				
if(!$record)

					Debug::message( "Record appears to be null" );

				

				/*if($record->hasMethod('BackLinkTracking')) {
					$brokenPages = $record->BackLinkTracking();

					foreach($brokenPages as $brokenPage) {

						$brokenPageList .= "<li style=\"font-size: 65%\">" . $brokenPage->Breadcrumbs(3, true) . "</li>";

						$brokenPage->HasBrokenLink = true;

						$notifications[$brokenPage->OwnerID][] = $brokenPage;

						$brokenPage->write();

					}

				}*/
				
				$record->delete();
				$record->destroy();



				// DataObject::delete_by_id($this->stat('tree_class'), $id);

				$script .= $this->deleteTreeNodeJS($record);

			}

		}


		
/*if($notifications) foreach($notifications as $memberID => $pages) {

			$email = new Page_BrokenLinkEmail();

			$email->populateTemplate(new ArrayData(array(

				"Recipient" => DataObject::get_by_id("Member", $memberID),

				"BrokenPages" => new DataObjectSet($pages),

			)));

			$email->debug();

			$email->send();

		}*/

		

		$s = (sizeof($ids) > 1) ? "s" :"";
		
		$message = sizeof($ids) . " folder$s deleted.";
		//
		if(isset($brokenPageList)) $message .= "  The following pages now have broken links:<ul>" . addslashes($brokenPageList) . "</ul>Their owners have been emailed and they will fix up those pages.";
		$script .= "statusMessage('$message');";
		echo $script;
	}
	
	public function removefile(){
		if($fileID = $this->urlParams['ID']){
			$file = DataObject::get_by_id('File', $fileID);
			$file->delete();
			$file->destroy();
			
			if(Director::is_ajax()) {
				echo <<<JS
				$('Form_EditForm_Files').removeFile($fileID);
				statusMessage('removed file', 'good');
JS;
			}else{
				Director::redirectBack();
			}
		}else{
			user_error("AssetAdmin::removefile: Bad parameters: File=$fileID", E_USER_ERROR);
		}
	}
	
	public function save($urlParams, $form) {
		// Don't save the root folder - there's no database record
		if($_REQUEST['ID'] == 'root') {
			FormResponse::status_message("Saved", "good");
			return FormResponse::respond();
		}
		
		
		$form->dataFieldByName('Title')->value = $form->dataFieldByName('Name')->value;
		
		return parent::save($urlParams, $form);
	}
}