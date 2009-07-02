<?php
/**
 * AssetAdmin is the 'file store' section of the CMS.
 * It provides an interface for maniupating the File and Folder objects in the system.
 * 
 * @package cms
 * @subpackage assets
 */
class AssetAdmin extends LeftAndMain {

	static $url_segment = 'assets';
	
	static $url_rule = '/$Action/$ID';
	
	static $menu_title = 'Files & Images';

	public static $tree_class = 'File';
	
	/**
	 * @see Upload->allowedMaxFileSize
	 * @var int
	 */
	public static $allowed_max_file_size;
	
	/**
	 * @see Upload->allowedExtensions
	 * @var array
	 */
	public static $allowed_extensions = array();
	
	static $allowed_actions = array(
		'addfolder',
		'deletefolder',
		'deletemarked',
		'deleteUnusedThumbnails',
		'doUpload',
		'getfile',
		'getsubtree',
		'movemarked',
		'removefile',
		'save',
		'savefile',
		'uploadiframe',
		'UploadForm',
		'deleteUnusedThumbnails' => 'ADMIN'
	);
	
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
		
		if(!file_exists(ASSETS_PATH)) {
			mkdir(ASSETS_PATH);
		}

		// needed for MemberTableField (Requirements not determined before Ajax-Call)
		Requirements::css(THIRDPARTY_DIR . "/greybox/greybox.css");
		Requirements::css(SAPPHIRE_DIR . "/css/ComplexTableField.css");

		Requirements::javascript(CMS_DIR . "/javascript/AssetAdmin.js");

		Requirements::javascript(CMS_DIR . "/javascript/CMSMain_upload.js");
		Requirements::javascript(CMS_DIR . "/javascript/Upload.js");
		Requirements::javascript(THIRDPARTY_DIR . "/SWFUpload/SWFUpload.js");
		
		Requirements::javascript(THIRDPARTY_DIR . "/greybox/AmiJS.js");
		Requirements::javascript(THIRDPARTY_DIR . "/greybox/greybox.js");
		Requirements::css(THIRDPARTY_DIR . "/greybox/greybox.css");
		
		Requirements::css(CMS_DIR . "/css/AssetAdmin.css");

		Requirements::customScript(<<<JS
			_TREE_ICONS = {};
			_TREE_ICONS['Folder'] = {
					fileIcon: 'jsparty/tree/images/page-closedfolder.gif',
					openFolderIcon: 'jsparty/tree/images/page-openfolder.gif',
					closedFolderIcon: 'jsparty/tree/images/page-closedfolder.gif'
			};
JS
		);
	}
	

	function index() {
		Filesystem::sync();
		return array();		
	}

	/**
	 * Show the content of the upload iframe.  The form is specified by a template.
	 */
	function uploadiframe() {
		Requirements::clear();
		
		Requirements::javascript(THIRDPARTY_DIR . "/prototype.js");
		Requirements::javascript(THIRDPARTY_DIR . "/loader.js");
		Requirements::javascript(THIRDPARTY_DIR . "/behaviour.js");
		Requirements::javascript(THIRDPARTY_DIR . "/prototype_improvements.js");
		Requirements::javascript(THIRDPARTY_DIR . "/layout_helpers.js");
		Requirements::javascript(CMS_DIR . "/javascript/LeftAndMain.js");
		Requirements::javascript(THIRDPARTY_DIR . "/multifile/multifile.js");
		Requirements::css(THIRDPARTY_DIR . "/multifile/multifile.css");
		Requirements::css(CMS_DIR . "/css/typography.css");
		Requirements::css(CMS_DIR . "/css/layout.css");
		Requirements::css(CMS_DIR . "/css/cms_left.css");
		Requirements::css(CMS_DIR . "/css/cms_right.css");
		
		if(isset($data['ID']) && $data['ID'] != 'root') $folder = DataObject::get_by_id("Folder", $data['ID']);
		else $folder = singleton('Folder');
		
		// Don't modify the output of the template, or it will become invalid
		ContentNegotiator::disable();
		
		return array( 'CanUpload' => $folder->canEdit());
	}
	
	/**
	 * Return the form object shown in the uploadiframe.
	 */
	function UploadForm() {
		// disabled flash upload javascript (CMSMain_upload()) below,
		// see r54952 (originally committed in r42014)
		$form = new Form($this,'UploadForm', new FieldSet(
			new HiddenField("ID", "", $this->currentPageID()),
			// needed because the button-action is triggered outside the iframe
			new HiddenField("action_doUpload", "", "1"), 
			new FileField("Files[0]" , _t('AssetAdmin.CHOOSEFILE','Choose file: ')),
			new LiteralField('UploadButton',"
				<input type=\"submit\" value=\"". _t('AssetAdmin.UPLOAD', 'Upload Files Listed Below'). "\" name=\"action_upload\" id=\"Form_UploadForm_action_upload\" class=\"action\" />
			"),
			new LiteralField('MultifileCode',"
				<p>" . _t('AssetAdmin.FILESREADY','Files ready to upload:') ."</p>
				<div id=\"Form_UploadForm_FilesList\"></div>
			")
		), new FieldSet(
		));
		
		// Makes ajax easier
		$form->disableSecurityToken();
		
		return $form;
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
		$jsErrors = '';
		$status = '';
		$statusMessage = '';
		
		foreach($processedFiles as $tmpFile) {
			if($tmpFile['error'] == UPLOAD_ERR_NO_TMP_DIR) {
				$status = 'bad';
				$statusMessage = _t('AssetAdmin.NOTEMP', 'There is no temporary folder for uploads. Please set upload_tmp_dir in php.ini.');
				break;
			}
		
			if($tmpFile['tmp_name']) {
				// Workaround open_basedir problems
				if(ini_get("open_basedir")) {
					$newtmp = TEMP_FOLDER . '/' . $tmpFile['name'];
					move_uploaded_file($tmpFile['tmp_name'], $newtmp);
					$tmpFile['tmp_name'] = $newtmp;
				}
				
				// validate files (only if not logged in as admin)
				if(Permission::check('ADMIN')) {
					$valid = true;
				} else {
					$upload = new Upload();
					$upload->setAllowedExtensions(self::$allowed_extensions);
					$upload->setAllowedMaxFileSize(self::$allowed_max_file_size);
					$valid = $upload->validate($tmpFile);
					if(!$valid) {
						$errors = $upload->getErrors();
						if($errors) foreach($errors as $error) {
							$jsErrors .= "alert('" . Convert::raw2js($error) . "');";
						}
					}
				}
				
				// move file to given folder
				if($valid) $newFiles[] = $folder->addUploadToFolder($tmpFile);
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
		
		$fileIDs = array();
		$fileNames = array();
		foreach($newFiles as $newFile) {
			$fileIDs[] = $newFile;
			$fileObj = DataObject::get_one('File', "`File`.ID=$newFile");
			// notify file object after uploading
			if (method_exists($fileObj, 'onAfterUpload')) $fileObj->onAfterUpload();
			$fileNames[] = $fileObj->Name;
		}
		
		$sFileIDs = implode(',', $fileIDs);
		$sFileNames = implode(',', $fileNames);

		echo <<<HTML
			<script type="text/javascript">
			/* IDs: $sFileIDs */
			/* Names: $sFileNames */
			
			var form = parent.document.getElementById('Form_EditForm');
			form.getPageFromServer(form.elements.ID.value);
			parent.statusMessage("{$statusMessage}","{$status}");
			$jsErrors
			parent.document.getElementById('sitetree').getTreeNodeByIdx( "{$folder->ID}" ).getElementsByTagName('a')[0].className += ' contents';
			</script>
HTML;
	}

	/**
	 * Custom currentPage() method to handle opening the 'root' folder
	 */
	public function currentPage() {
		$id = $this->currentPageID();
		if($id && is_numeric($id)) {
			return DataObject::get_by_id($this->stat('tree_class'), $id);
		} else if($id == 'root') {
			return singleton($this->stat('tree_class'));
		}
	}
	
	/**
	 * Return the form that displays the details of a folder, including a file list and fields for editing the folder name.
	 */
	function getEditForm($id) {
		if($id && $id != "root") {
			$record = DataObject::get_by_id("File", $id);
		} else {
			$record = singleton("Folder");
		}
		
		if($record) {
			$fields = $record->getCMSFields();
			$actions = new FieldSet();
			
			// Only show save button if not 'assets' folder
			if($record->canEdit() && $id != 'root') {
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
			
			if(!$record->canEdit()) {
				$form->makeReadonly();
			}

			return $form;
		}
	}
	
	/**
	 * Perform the "move marked" action.
	 * Called and returns in same way as 'save' function
	 */
	public function movemarked($urlParams, $form) {
		if($_REQUEST['DestFolderID'] && (is_numeric($_REQUEST['DestFolderID']) || ($_REQUEST['DestFolderID']) == 'root')) {
			$destFolderID = ($_REQUEST['DestFolderID'] == 'root') ? 0 : $_REQUEST['DestFolderID'];
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
			user_error('Bad data:' . $_REQUEST['DestFolderID'], E_USER_ERROR);
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
			$files = DataObject::get('File', "`File`.ID IN ($fileList)");
			if($files) {
				foreach($files as $file) {
					if($file instanceof Image) {
						$file->deleteFormattedImages();
					}
					if(!$folderID) {
						$folderID = $file->ParentID;
					}
					$file->delete();
					$numFiles++;
				}
				if($brokenPages = Notifications::getItems('BrokenLink')) {
					$brokenPageList = "  ". _t('AssetAdmin.NOWBROKEN', 'These pages now have broken links:') . '</ul>';
					foreach($brokenPages as $brokenPage) {
						$brokenPageList .= "<li style=&quot;font-size: 65%&quot;>" . $brokenPage->Breadcrumbs(3, true) . '</li>';
					}
					$brokenPageList .= '</ul>';
					Notifications::notifyByEmail("BrokenLink", "Page_BrokenLinkEmail");
				} else {
					$brokenPageList = '';
				}
				
				$deleteList = '';
				if($folderID) {
					$remaining = DB::query("SELECT COUNT(*) FROM `File` WHERE `ParentID`=$folderID")->value();
					if(!$remaining) $deleteList .= "Element.removeClassName(\$('sitetree').getTreeNodeByIdx('$folderID').getElementsByTagName('a')[0],'contents');";
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
	 * Return the entire site tree as a nested UL.
	 * @return string HTML for site tree
	 */
	public function SiteTreeAsUL() {
		$obj = singleton('Folder');
		$obj->setMarkingFilter('ClassName', ClassInfo::subclassesFor('Folder'));
		$obj->markPartialTree();

		if($p = $this->currentPage()) $obj->markToExpose($p);

		// getChildrenAsUL is a flexible and complex way of traversing the tree
		$siteTreeList = $obj->getChildrenAsUL(
			'',
			'"<li id=\"record-$child->ID\" class=\"$child->class" . $child->markingClasses() .  ($extraArg->isCurrentPage($child) ? " current" : "") . "\">" . ' .
			'"<a href=\"" . Director::link(substr($extraArg->Link(),0,-1), "show", $child->ID) . "\" class=\"" . ($child->hasChildren() ? " contents" : "") . "\" >" . $child->TreeTitle() . "</a>" ',
			$this,
			true
		);	

		// Wrap the root if needs be
		$rootLink = $this->Link() . 'show/root';
		$baseUrl = Director::absoluteBaseURL() . "assets";
		if(!isset($rootID)) {
			$siteTree = "<ul id=\"sitetree\" class=\"tree unformatted\"><li id=\"record-root\" class=\"Root\"><a href=\"$rootLink\"><strong>{$baseUrl}</strong></a>"
			. $siteTreeList . "</li></ul>";
		}

		return $siteTree;
	}

	/**
	 * Returns a subtree of items underneat the given folder.
	 */
	public function getsubtree() {
		$obj = DataObject::get_by_id('Folder', $_REQUEST['ID']);
		$obj->setMarkingFilter('ClassName', ClassInfo::subclassesFor('Folder'));
		$obj->markPartialTree();

		$results = $obj->getChildrenAsUL(
			'',
			'"<li id=\"record-$child->ID\" class=\"$child->class" . $child->markingClasses() .  ($extraArg->isCurrentPage($child) ? " current" : "") . "\">" . ' .
			'"<a href=\"" . Director::link(substr($extraArg->Link(),0,-1), "show", $child->ID) . "\" >" . $child->TreeTitle() . "</a>" ',
			$this,
			true
		);

		return substr(trim($results), 4, -5);
	}
	

	//------------------------------------------------------------------------------------------//

	// Data saving handlers

	/**
	 * Add a new folder and return its details suitable for ajax.
	 */
	public function addfolder() {
		$parent = ($_REQUEST['ParentID'] && is_numeric($_REQUEST['ParentID'])) ? (int)$_REQUEST['ParentID'] : 0;
		$name = (isset($_REQUEST['Name'])) ? basename($_REQUEST['Name']) : _t('AssetAdmin.NEWFOLDER',"NewFolder");
		
		if($parent) {
			$parentObj = DataObject::get_by_id('File', $parent);
			if(!$parentObj || !$parentObj->ID) $parent = 0;
		}
		
		// Get the folder to be created		
		if(isset($parentObj->ID)) $filename = $parentObj->FullPath . $name;
		else $filename = ASSETS_PATH . '/' . $name;

		// Ensure uniqueness		
		$i = 2;
		$baseFilename = $filename . '-';
		while(file_exists($filename)) {
			$filename = $baseFilename . $i;
			$i++;
		}

		// Actually create
		if(!file_exists(ASSETS_PATH)) {
			mkdir(ASSETS_PATH);
		}
		mkdir($filename);
		chmod($filename, Filesystem::$file_create_mask);

		// Create object
		$p = new Folder();
		$p->ParentID = $parent;
		$p->Name = $p->Title = basename($filename);		
		$p->write();

		if(isset($_REQUEST['returnID'])) {
			return $p->ID;
		} else {
			return $this->returnItemToUser($p);
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
				if($record) {
					$script .= $this->deleteTreeNodeJS($record);
					$record->delete();
					$record->destroy();
				}
			}
		}
		
		$size = sizeof($ids);
		if($size > 1) {
		  $message = $size.' '._t('AssetAdmin.FOLDERSDELETED', 'folders deleted.');
		} else {
		  $message = $size.' '._t('AssetAdmin.FOLDERDELETED', 'folder deleted.');
		}

		if(isset($brokenPageList)) {
		  $message .= '  '._t('AssetAdmin.NOWBROKEN', 'The following pages now have broken links:').'<ul>'.addslashes($brokenPageList).'</ul>'.
		    _t('AssetAdmin.NOWBROKEN2', 'Their owners have been emailed and they will fix up those pages.');
		}

		$script .= "statusMessage('$message');";
		echo $script;
	}
	
	public function removefile(){
		if($fileID = $this->urlParams['ID']) {
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
			} else {
				Director::redirectBack();
			}
		} else {
			user_error("AssetAdmin::removefile: Bad parameters: File=$fileID", E_USER_ERROR);
		}
	}
	
	public function save($urlParams, $form) {
		// Don't save the root folder - there's no database record
		if($_REQUEST['ID'] == 'root') {
			FormResponse::status_message('Saved', 'good');
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
	 * Removes all unused thumbnails from the file store
	 * and returns the status of the process to the user.
	 */
	public function deleteunusedthumbnails() {
		$count = 0;
		$thumbnails = $this->getUnusedThumbnails();
		
		if($thumbnails) {
			foreach($thumbnails as $thumbnail) {
				unlink(ASSETS_PATH . "/" . $thumbnail);
				$count++;
			}
		}
		
		$message = sprintf(_t('AssetAdmin.THUMBSDELETED', '%s unused thumbnails have been deleted'), $count);
		FormResponse::status_message($message, 'good');
		echo FormResponse::respond();
	}
	
	/**
	 * Creates array containg all unused thumbnails.
	 * 
	 * Array is created in three steps:
	 *     1. Scan assets folder and retrieve all thumbnails
	 *     2. Scan all HTMLField in system and retrieve thumbnails from them.
	 *     3. Count difference between two sets (array_diff)
	 *
	 * @return array 
	 */
	private function getUnusedThumbnails() {
		$allThumbnails = array();
		$usedThumbnails = array();
		$dirIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(ASSETS_PATH));
		$classes = ClassInfo::subclassesFor('SiteTree');
		
		if($dirIterator) {
			foreach($dirIterator as $file) {
				if($file->isFile()) {
					if(strpos($file->getPathname(), '_resampled') !== false) {
						$pathInfo = pathinfo($file->getPathname());
						if(in_array(strtolower($pathInfo['extension']), array('jpeg', 'jpg', 'jpe', 'png', 'gif'))) {
							$path = str_replace('\\','/', $file->getPathname());
							$allThumbnails[] = substr($path, strpos($path, '/assets/') + 8);
						}
					}
				}
			}
		}
		
		if($classes) {
			foreach($classes as $className) {
				$SNG_class = singleton($className);
				$objects = DataObject::get($className);
				
				if($objects !== NULL) {
					foreach($objects as $object) {
						foreach($SNG_class->db() as $fieldName => $fieldType) {
							if($fieldType == 'HTMLText') {
								$url1 = HTTP::findByTagAndAttribute($object->$fieldName,array('img' => 'src'));
								
								if($url1 != NULL) {
									$usedThumbnails[] = substr($url1[0], strpos($url1[0], '/assets/') + 8);
								}
								
								if($object->latestPublished > 0) {
									$object = Versioned::get_latest_version($className, $object->ID);
									$url2 = HTTP::findByTagAndAttribute($object->$fieldName, array('img' => 'src'));
									
									if($url2 != NULL) {
										$usedThumbnails[] = substr($url2[0], strpos($url2[0], '/assets/') + 8);
									}
								}
							}
						}
					}
				}
			}
		}
		
		return array_diff($allThumbnails, $usedThumbnails);
	}
	
}
?>