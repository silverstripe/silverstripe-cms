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
		
		if(!file_exists('../assets')) {
			mkdir('../assets');
		}

		// needed for MemberTableField (Requirements not determined before Ajax-Call)
		Requirements::javascript("sapphire/javascript/ComplexTableField.js");
		Requirements::css("jsparty/greybox/greybox.css");
		Requirements::css("sapphire/css/ComplexTableField.css");

		Requirements::javascript("cms/javascript/AssetAdmin.js");
		Requirements::javascript("cms/javascript/AssetAdmin_left.js");
		Requirements::javascript("cms/javascript/AssetAdmin_right.js");

		Requirements::javascript("cms/javascript/CMSMain_upload.js");
		Requirements::javascript("cms/javascript/Upload.js");
		Requirements::javascript("sapphire/javascript/Security_login.js");
		Requirements::javascript("jsparty/SWFUpload/SWFUpload.js");
		
		// Include the right JS]
		// Hayden: This didn't appear to be used at all
		/*$fileList = new FileList("Form_EditForm_Files", null);
		$fileList->setClick_AjaxLoad('admin/assets/getfile/', 'Form_SubForm');
		$fileList->FieldHolder();*/
		
		Requirements::javascript("jsparty/greybox/AmiJS.js");
		Requirements::javascript("jsparty/greybox/greybox.js");
		Requirements::css("jsparty/greybox/greybox.css");
		
		Requirements::css("cms/css/AssetAdmin.css");
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
			new FileField("Files[0]" , _t('AssetAdmin.CHOOSEFILE','Choose file ')),
			new LiteralField('UploadButton',"
				<input type='submit' value='". _t('AssetAdmin.UPLOAD', 'Upload Files Listed Below'). "' name='action_upload' id='Form_UploadForm_action_upload' class='action' />
			"),
			new LiteralField('MultifileCode',"
				<p>" . _t('AssetAdmin.FILESREADY','Files ready to upload:') ."</p>
				<div id='Form_UploadForm_FilesList'></div>
				<script>
					var multi_selector = new MultiSelector($('Form_UploadForm_FilesList'), null, $('Form_UploadForm_action_upload'));
					multi_selector.addElement($('Form_UploadForm_Files-0'));
                    new window.top.document.CMSMain_upload();
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
			if(!is_array($files)) $files = array($files);
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
					$fileSizeWarnings .= "alert( '". sprintf(_t('AssetAdmin.ONLYADMINS','Only administrators can upload %s files.'),$extension)."' );";
				} else {
					if( $file['size'] > 1048576 )
						$fileSize = "" . ceil( $file['size'] / 1048576 ) . "MB";
					elseif( $file['size'] > 1024 )
						$fileSize = "" . ceil( $file['size'] / 1024 ) . "KB";
					else
						$fileSize = "" . ceil( $file['size'] ) . "B";
											
								
					$fileSizeWarnings .= "alert( '". sprintf(_t('AssetAdmin.TOOLARGE', "%s is too large (%s). Files of this type cannot be larger than %s"),"\\'" . $file['name'] . "\\'", $fileSize, $warnSize ) ."' );";
				}
			}
		}
		
		if($newFiles) {
			$numFiles = sizeof($newFiles);
			$statusMessage = sprintf(_t('AssetAdmin.UPLOADEDX',"Uploaded %s files"),$numFiles) ;
			$status = "good";
		} else if($status != 'bad') {
			$statusMessage = _t('AssetAdmin.NOTHINGTOUPLOAD','There was nothing to upload');
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
			array("Title" => _t('AssetAdmin.TITLE', "Title"), "LinkedURL" => _t('AssetAdmin.FILENAME', "Filename")), 
			""
		);
		$fileList->setFolder($record);
		$fileList->setPopupCaption(_t('AssetAdmin.VIEWEDITASSET', "View/Edit Asset"));
        
	    if($record) {
			$nameField = ($id != "root") ? new TextField("Name", "Folder Name") : new HiddenField("Name");
			if( $record->userCanEdit() ) {
				$deleteButton = new InlineFormAction('deletemarked',_t('AssetAdmin.DELSELECTED','Delete selected files'), 'delete');
				$deleteButton->includeDefaultJS(false);
			} else {
				$deleteButton = new HiddenField('deletemarked');
			}

			$fields = new FieldSet(
				new HiddenField("Title"),
				new TabSet("Root", 
					new Tab(_t('AssetAdmin.FILESTAB', "Files"),
						$nameField,
						$fileList,
						$deleteButton,
						new HiddenField("FileIDs"),
						new HiddenField("DestFolderID")
					),
					new Tab(_t('AssetAdmin.DETAILSTAB', "Details"), 
						new ReadonlyField("URL"),
						new ReadonlyField("ClassName", _t('AssetAdmin.TYPE','Type')),
						new ReadonlyField("Created", _t('AssetAdmin.CREATED','First Uploaded')),
						new ReadonlyField("LastEdited", _t('AssetAdmin.LASTEDITED','Last Updated'))
					),
					new Tab(_t('AssetAdmin.UPLOADTAB', "Upload"),
						new LiteralField("UploadIframe",
							$this->getUploadIframe()
						)
					),
					new Tab(_t('AssetAdmin.UNUSEDFILESTAB', "Unused files"),
					    new LiteralField("UnusedAssets",
                            "<div id=\"UnusedAssets\"><h2>"._t('AssetAdmin.UNUSEDFILESTITLE', 'Unused files')."</h2>"
                        ),
					    $this->getAssetList(),
					    new LiteralField("UnusedThumbnails",
                           "</div>
                                <div id=\"UnusedThumbnails\">
                                    <h2>"._t('AssetAdmin.UNUSEDTHUMBNAILSTITLE', 'Unused thumbnails')."</h2>
                                    <button>"._t('AssetAdmin.DELETEUNUSEDTHUMBNAILS', 'Delete unused thumbnails')."</button>
                                </div>"
                        )     
                    )
			    ),
				new HiddenField("ID")
			);
			
			$actions = new FieldSet();
			
			// Only show save button if not 'assets' folder
			if( $record->userCanEdit() && $id != "root") {
				$actions = new FieldSet(
					new FormAction('save',_t('AssetAdmin.SAVEFOLDERNAME','Save folder name'))
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
				$fields->addFieldsToTab( 'Root.Workflow', new DropdownField("Owner", _t('AssetAdmin.OWNER','Owner'), Member::map() ) );
				$fields->addFieldsToTab( 'Root.Workflow', new TreeMultiselectField("CanUse", _t('AssetAdmin.CONTENTUSABLEBY','Content usable by')) );
				$fields->addFieldsToTab( 'Root.Workflow', new TreeMultiselectField("CanEdit", _t('AssetAdmin.CONTENTMODBY','Content modifiable by')) );
			}

			if( !$record->userCanEdit() )
				$form->makeReadonly();

			return $form;

		}
	}
	
	/**
	 * Perform the "move marked" action.
	 * Called and returns in same way as 'save' function
	 */
	public function movemarked($urlParams, $form) {
		if($_REQUEST['DestFolderID'] && is_numeric($_REQUEST['DestFolderID'])) {
			$destFolderID = $_REQUEST['DestFolderID'];
			$fileList = "'" . ereg_replace(' *, *',"','",trim(addslashes($_REQUEST['FileIDs']))) . "'";
			$numFiles = 0;
	
			if($fileList != "''") {
				$files = DataObject::get("File", "`File`.ID IN ($fileList)");
				if($files) {
					foreach($files as $file) {
						if($file instanceof Image) {
                            $file->deleteFormattedImages();
                        }
						$file->ParentID = $destFolderID;
						$file->write();
						$numFiles++;
					}
				} else {
					user_error("No files in $fileList could be found!", E_USER_ERROR);
				}
			}

			$message = sprintf(_t('AssetAdmin.MOVEDX','Moved %s files'),$numFiles);
			FormResponse::status_message($message, "good");
			FormResponse::add("$('Form_EditForm').getPageFromServer($('Form_EditForm_ID').value)");
			return FormResponse::respond();	
		} else {
			user_error("Bad data: $_REQUEST[DestFolderID]", E_USER_ERROR);
		}
	}

	/**
	 * Perform the "delete marked" action.
	 * Called and returns in same way as 'save' function
	 */
	public function deletemarked($urlParams, $form) {
			$fileList = "'" . ereg_replace(' *, *',"','",trim(addslashes($_REQUEST['FileIDs']))) . "'";
			$numFiles = 0;
			$folderID = 0;
			$deleteList = '';
			$brokenPageList = '';
	
			if($fileList != "''") {
				$files = DataObject::get("File", "ID IN ($fileList)");
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
						$brokenPageList = "  ". _t('AssetAdmin.NOWBROKEN',"These pages now have broken links:")."</ul>";
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
							$deleteList .= "Element.removeClassName(\$('sitetree').getTreeNodeByIdx( '$folderID' ).getElementsByTagName('a')[0],'contents');";
					}
					
				} else {
					user_error("No files in $fileList could be found!", E_USER_ERROR);
				}
			}
			$message = sprintf(_t('AssetAdmin.DELETEDX',"Deleted %s files.%s"),$numFiles,$brokenPageList) ;
			FormResponse::add($deleteList);
			FormResponse::status_message($message, "good");
			FormResponse::add("$('Form_EditForm').getPageFromServer($('Form_EditForm_ID').value)");
			return FormResponse::respond();	
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
		$saved = sprintf(_t('AssetAdmin.SAVEDFILE','Saved file %s'),"#$data[ID]");
		echo <<<JS
			statusMessage('$saved');
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

		if(!isset($rootID)) $siteTree = "<ul id=\"sitetree\" class=\"tree unformatted\"><li id=\"record-root\" class=\"Root\"><strong><a href=\"$rootLink\">http://www.yoursite.com/assets</strong></a>"

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
		$p->Title = _t('AssetAdmin.NEWFOLDER',"NewFolder");

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
		if(!file_exists('../assets')) {
			mkdir('../assets');
		}
		mkdir($filename);
		chmod($filename, Filesystem::$file_create_mask);

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
			// Delete the temp verions of this file in assets/_resampled
			if($file instanceof Image) {
				$file->deleteFormattedImages();
			}
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
	
	/**
     * #################################
     *        Garbage collection.
     * #################################
    */
	
	/**
     * Removes all unused thumbnails, and echos status message to user.
     *
     * @returns null
    */
	
	public function deleteUnusedThumbnails() {
	    foreach($this->getUnusedThumbnailsArray() as $file) {
	    	unlink("../assets/" . $file); 	
	    }
	    echo "statusMessage('All unused thumbnails have been deleted','good')";
	}
	
	/**
     * Looks for files used in system and create where clause which contains all ID's of files.
     * 
     * @returns String where clause which will work as filter.
    */
	
	private function getUsedFilesList() {
	    $result = DB::query("SELECT DISTINCT FileID FROM SiteTree_ImageTracking");
        $usedFiles = array();
	    $where = "";
        if($result->numRecords() > 0) {
            while($nextResult = $result->next()){
                $where .= $nextResult['FileID'] . ','; 
            }        
        }
        $classes = ClassInfo::subclassesFor('SiteTree');
        foreach($classes as $className) {
            $sng = singleton($className);
            $objects = DataObject::get($className);
            if($objects !== NULL) {
	            foreach($sng->has_one() as $fieldName => $joinClass) {
	                if($joinClass == 'Image' || $joinClass == 'File')  {
	                	foreach($objects as $object) {
	                		if($object->$fieldName != NULL) $usedFiles[] = $object->$fieldName;
	                    }
	                } elseif($joinClass == 'Folder') {
	                    /*foreach($objects as $object) {
	                    	var_dump($object->$fieldName);   	
	                    }*/
	                }
	            }
            }
        }
        foreach($usedFiles as $file) {
            $where .= $file->ID . ',';     
        }
        if($where == "") return "(ClassName = 'File' OR ClassName =  'Image')";
        $where = substr($where,0,strlen($where)-1);
        $where = "ID NOT IN (" . $where . ") AND (ClassName = 'File' OR ClassName =  'Image')";
        return $where;
	}
	
	/**
     * Creates table for displaying unused files.
     *
     * @returns AssetTableField
    */
	
	private function getAssetList() {
		$where = $this->getUsedFilesList();
        $assetList = new AssetTableField(
            $this,
            "AssetList",
            "File", 
			array("Title" => _t('AssetAdmin.TITLE', "Title"), "LinkedURL" => _t('AssetAdmin.FILENAME', "Filename")), 
            "",
            $where
        );
		$assetList->setPopupCaption(_t('AssetAdmin.VIEWASSET', "View Asset"));
        $assetList->setPermissions(array("show","delete"));
        $assetList->Markable = false;
        return $assetList;
        
	}
	
	/**
     * Creates array containg all unused thumbnails.
     * Array is created in three steps:
     *     1.Scan assets folder and retrieve all thumbnails
     *     2.Scan all HTMLField in system and retrieve thumbnails from them.
     *     3.Count difference between two sets (array_diff)
     *
     * @returns Array 
    */

    private function getUnusedThumbnailsArray() {
    	$allThumbnails = array();
    	$dirIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('../assets'));
        foreach ($dirIterator as $file) {
            if($file->isFile()) {
            	if(strpos($file->getPathname(),"_resampled") !== false) {
            		$pathInfo = pathinfo($file->getPathname());
            		if(in_array(strtolower($pathInfo['extension']),array('jpeg','jpg','jpe','png','gif'))) {
                		$path = str_replace('\\','/',$file->getPathname());
            			$allThumbnails[] = substr($path,strpos($path,'/assets/')+8);
            		}
            	}
            }
        }
    	$classes = ClassInfo::subclassesFor('SiteTree');
        $usedThumbnails = array();
    	foreach($classes as $className) {
            $sng = singleton($className);
            $objects = DataObject::get($className);
            if($objects !== NULL) {
                foreach($objects as $object) {
            	    foreach($sng->db() as $fieldName => $fieldType) {
                        if($fieldType == 'HTMLText')  {
            	            $url1 = HTTP::findByTagAndAttribute($object->$fieldName,array("img" => "src"));
            	            if($url1 != NULL) $usedThumbnails[] = substr($url1[0],strpos($url1[0],'/assets/')+8);
            	            if($object->latestPublished > 0) {
            	                $object = Versioned::get_latest_version($className, $object->ID);
            	                $url2 = HTTP::findByTagAndAttribute($object->$fieldName,array("img" => "src"));
            	                if($url2 != NULL) $usedThumbnails[] = substr($url2[0],strpos($url2[0],'/assets/')+8);
            	            }
                        }
            	    }
                }
            }
        }
        return array_diff($allThumbnails,$usedThumbnails);
    }
}
