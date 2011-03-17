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

	public static $tree_class = 'Folder';
	
	/**
	 * @see Upload->allowedMaxFileSize
	 * @var int
	 */
	public static $allowed_max_file_size;
	
	static $allowed_actions = array(
		'addfolder',
		'DeleteItemsForm',
		'doUpload',
		'getsubtree',
		'movemarked',
		'removefile',
		'savefile',
		'uploadiframe',
		'UploadForm',
		'deleteUnusedThumbnails' => 'ADMIN',
		'SyncForm',
	);
	
	/**
	 * @var boolean Enables upload of additional textual information
	 * alongside each file (through multifile.js), which makes
	 * batch changes easier.
	 * 
	 * CAUTION: This is an unstable API which might change.
	 */
	public static $metadata_upload_enabled = false;
	
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
		
		// Create base folder if it doesnt exist already
		if(!file_exists(ASSETS_PATH)) Filesystem::makeFolder(ASSETS_PATH);

		Requirements::javascript(CMS_DIR . "/javascript/AssetAdmin.js");
		Requirements::css(CMS_DIR . "/css/AssetAdmin.css");

		Requirements::customScript(<<<JS
			_TREE_ICONS = {};
			_TREE_ICONS['Folder'] = {
					fileIcon: 'sapphire/javascript/tree/images/page-closedfolder.gif',
					openFolderIcon: 'sapphire/javascript/tree/images/page-openfolder.gif',
					closedFolderIcon: 'sapphire/javascript/tree/images/page-closedfolder.gif'
			};
JS
		);
		
		CMSBatchActionHandler::register('delete', 'AssetAdmin_DeleteBatchAction', 'Folder');
	}
		
	/**
	 * Show the content of the upload iframe.  The form is specified by a template.
	 */
	function uploadiframe() {
		Requirements::clear();
		
		Requirements::javascript(SAPPHIRE_DIR . "/thirdparty/prototype/prototype.js");
		Requirements::javascript(SAPPHIRE_DIR . "/thirdparty/behaviour/behaviour.js");
		//Requirements::javascript(CMS_DIR . "/javascript/LeftAndMain.js");
		Requirements::javascript(CMS_DIR . "/thirdparty/multifile/multifile.js");
		Requirements::css(CMS_DIR . "/thirdparty/multifile/multifile.css");
		Requirements::javascript(SAPPHIRE_DIR . "/thirdparty/jquery/jquery.js");
		Requirements::javascript(SAPPHIRE_DIR . "/javascript/jquery_improvements.js");
		Requirements::css(CMS_DIR . "/css/typography.css");
		Requirements::css(CMS_DIR . "/css/layout.css");
		Requirements::css(CMS_DIR . "/css/cms_left.css");
		Requirements::css(CMS_DIR . "/css/cms_right.css");
		
		if(isset($data['ID']) && $data['ID'] != 'root') $folder = DataObject::get_by_id("Folder", $data['ID']);
		else $folder = singleton('Folder');
		
		return array( 'CanUpload' => $folder->canEdit());
	}
	
	/**
	 * Needs to be enabled through {@link AssetAdmin::$metadata_upload_enabled}
	 * 
	 * @return String
	 */
	function UploadMetadataHtml() {
		if(!self::$metadata_upload_enabled) return;
		
		$fields = singleton('File')->uploadMetadataFields();

		// Return HTML with markers for easy replacement
		$fieldHtml = '';
		foreach($fields as $field) $fieldHtml = $fieldHtml . $field->FieldHolder();
		$fieldHtml = preg_replace('/(name|for|id)="(.+?)"/', '$1="$2[__X__]"', $fieldHtml);

		// Icky hax to fix certain elements with fixed ids
		$fieldHtml = preg_replace('/-([a-zA-Z0-9]+?)\[__X__\]/', '[__X__]-$1', $fieldHtml);

		return $fieldHtml;
	}
	
	/**
	 * Return the form object shown in the uploadiframe.
	 */
	function UploadForm() {
		$form = new Form($this,'UploadForm', new FieldSet(
			new HiddenField("ID", "", $this->currentPageID()),
			new HiddenField("FolderID", "", $this->currentPageID()),
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
		$newFiles = array();
		$fileIDs = array();
		$fileNames = array();
		$fileSizeWarnings = '';
		$errorsArr = '';
		$status = '';
		$statusMessage = '';
		$processedFiles = array();

		foreach($data['Files'] as $param => $files) {
			if(!is_array($files)) $files = array($files);
			foreach($files as $key => $value) {
				$processedFiles[$key][$param] = $value;
			}
		}
		
		// Load POST data from arrays in to the correct dohickey.
		$processedData = array();
		foreach($data as $dataKey => $value) {
			if ($dataKey == 'Files') continue;
			if (is_array($value)) {
				$i = 0;
				foreach($value as $fileId => $dataValue) {
					if (!isset($processedData[$i])) $processedData[$i] = array();
					$processedData[$i][$dataKey] = $dataValue;
					$i++;
				}
			}
		}
		$processedData = array_reverse($processedData);
				
		if($data['FolderID'] && $data['FolderID'] != '') $folder = DataObject::get_by_id("Folder", $data['FolderID']);
		else $folder = singleton('Folder');

		foreach($processedFiles as $filePostId => $tmpFile) {
			if($tmpFile['error'] == UPLOAD_ERR_NO_TMP_DIR) {
				$errorsArr[] = _t('AssetAdmin.NOTEMP', 'There is no temporary folder for uploads. Please set upload_tmp_dir in php.ini.');
				break;
			}
		
			if($tmpFile['tmp_name']) {
				
				// validate files (only if not logged in as admin)
				if(!File::$apply_restrictions_to_admin && Permission::check('ADMIN')) {
					$valid = true;
				} else {
					
					// Set up the validator instance with rules
					$validator = new Upload_Validator();
					$validator->setAllowedExtensions(File::$allowed_extensions);
					$validator->setAllowedMaxFileSize(self::$allowed_max_file_size);
					
					// Do the upload validation with the rules
					$upload = new Upload();
					$upload->setValidator($validator);
					$valid = $upload->validate($tmpFile);
					if(!$valid) {
						$errorsArr = $upload->getErrors();
					}
				}
				
				// move file to given folder
				if($valid) {
					$newFile = $folder->addUploadToFolder($tmpFile);
					
					if(self::$metadata_upload_enabled && isset($processedData[$filePostId])) {
						$fileObject = DataObject::get_by_id('File', $newFile);
						$metadataForm = new Form($this, 'MetadataForm', $fileObject->uploadMetadataFields(), new FieldSet());
						$metadataForm->loadDataFrom($processedData[$filePostId]);
						$metadataForm->saveInto($fileObject);
						$fileObject->write();
					}
					
					$newFiles[] = $newFile;
				}
			}
		}

		if($newFiles) {
			$numFiles = sizeof($newFiles);
			$statusMessage = sprintf(_t('AssetAdmin.UPLOADEDX',"Uploaded %s files"),$numFiles);
			$status = "good";
		} else if($errorsArr) {
			$statusMessage = implode('\n', $errorsArr);
			$status = 'bad';
		} else {
			$statusMessage = _t('AssetAdmin.NOTHINGTOUPLOAD','There was nothing to upload');
			$status = "";
		}

		$fileObj = false;
		foreach($newFiles as $newFile) {
			$fileIDs[] = $newFile;
			$fileObj = DataObject::get_one('File', "\"File\".\"ID\"=$newFile");
			// notify file object after uploading
			if (method_exists($fileObj, 'onAfterUpload')) $fileObj->onAfterUpload();
			$fileNames[] = $fileObj->Name;
		}
		
		// workaround for content editors image upload.Passing an extra hidden field
		// in the content editors view of 'UploadMode' @see HtmlEditorField
		// this will be refactored for 2.5
		if(isset($data['UploadMode']) && $data['UploadMode'] == "CMSEditor" && $fileObj) {
			// we can use $fileObj considering that the uploader in the cmseditor can only upload
			// one file at a time. Once refactored to multiple files this is going to have to be changed
			$width = (is_a($fileObj, 'Image')) ? $fileObj->getWidth() : '100';
			$height = (is_a($fileObj, 'Image')) ? $fileObj->getHeight() : '100';
			
			$values = array(
				'Filename' => $fileObj->Filename,
				'Width' => $width,
				'Height' => $height
			);
			
			return Convert::raw2json($values);
		}
		
		$sFileIDs = implode(',', $fileIDs);
		$sFileNames = implode(',', $fileNames);

		// TODO Will be replaced by new upload mechanism, refresh disabled for now
// 		echo <<<HTML
// 			<script type="text/javascript">
// 			var url = parent.document.getElementById('sitetree').getTreeNodeByIdx( "{$folder->ID}" ).getElementsByTagName('a')[0].href;
// 			parent.jQuery('#Form_EditForm').entwine('ss').loadForm(url);
// 			parent.statusMessage("{$statusMessage}","{$status}");
// 			</script>
// HTML;
	}
	
	/**
	 * Add a new group and return its details suitable for ajax.
	 * 
	 * @todo Move logic into Folder class, and use LeftAndMain->doAdd() default implementation.
	 */
	public function doAdd($data, $form) {
		$class = $this->stat('tree_class');
		
		// check create permissions
		if(!singleton($class)->canCreate()) return Security::permissionFailure($this);
		
		// check addchildren permissions
		if(
			singleton($class)->hasDatabaseField('Hierarchy') 
			&& isset($data['ParentID'])
			&& is_numeric($data['ParentID'])
		) {
			$parentRecord = DataObject::get_by_id($class, $data['ParentID']);
			if(
				$parentRecord->hasMethod('canAddChildren') 
				&& !$parentRecord->canAddChildren()
			) return Security::permissionFailure($this);
		}
		
		$parent = (isset($data['ParentID']) && is_numeric($data['ParentID'])) ? (int)$data['ParentID'] : 0;
		$name = (isset($data['Name'])) ? basename($data['Name']) : _t('AssetAdmin.NEWFOLDER',"NewFolder");
		if(!isset($parentRecord) || !$parentRecord->ID) $parent = 0;
		
		// Get the folder to be created		
		if(isset($parentRecord->ID)) $filename = $parentRecord->FullPath . $name;
		else $filename = ASSETS_PATH . '/' . $name;

		// Actually create
		if(!file_exists(ASSETS_PATH)) {
			mkdir(ASSETS_PATH);
		}
		
		$record = new Folder();
		$record->ParentID = $parent;
		
		// Ensure uniqueness		
		$i = 2;
		$baseFilename = substr($record->Filename, 0, -1) . '-';
		while(file_exists($record->FullPath)) {
			$record->Filename = $baseFilename . $i . '/';
			$i++;
		}
		
		$record->Name = $record->Title = basename($record->Filename);
		$record->write();
		
		mkdir($record->FullPath);
		chmod($record->FullPath, Filesystem::$file_create_mask);

		// Used in TinyMCE inline folder creation
		if(isset($data['returnID'])) {
			return $record->ID;
		} else if($this->isAjax()) {
			$form = $this->getEditForm($record->ID);
			return $form->formHtmlContent();
		} else {
			return $this->redirect(Controller::join_links($this->Link('show'), $record->ID));
		}
	}

	/**
	 * Custom currentPage() method to handle opening the 'root' folder
	 */
	public function currentPage() {
		$id = $this->currentPageID();
		if($id && is_numeric($id)) {
			return DataObject::get_by_id('File', $id);
		} else if($id == 'root') {
			return singleton('File');
		}
	}
	
	function getSiteTreeFor($className, $rootID = null, $childrenMethod = null, $numChildrenMethod = null, $filterFunction = null, $minNodeCount = 30) {
		if (!$childrenMethod) $childrenMethod = 'ChildFolders';
		return parent::getSiteTreeFor($className, $rootID, $childrenMethod, $numChildrenMethod, $filterFunction, $minNodeCount);
	}
	
	public function getCMSTreeTitle() {
		return Director::absoluteBaseURL() . "assets";
	}
	
	public function SiteTreeAsUL() {
		return $this->getSiteTreeFor($this->stat('tree_class'), null, 'ChildFolders');
	}

	//------------------------------------------------------------------------------------------//

	// Data saving handlers
	/**
	 * @return Form
	 */
	function SyncForm() {
		$form = new Form(
			$this,
			'SyncForm',
			new FieldSet(
			),
			new FieldSet(
				$btn = new FormAction('doSync', _t('FILESYSTEMSYNC','Look for new files'))
			)
		);
		$form->addExtraClass('actionparams');
		$form->setFormMethod('GET');
		$btn->describe(_t('AssetAdmin_left.ss.FILESYSTEMSYNC_DESC', 'SilverStripe maintains its own database of the files &amp; images stored in your assets/ folder.  Click this button to update that database, if files are added to the assets/ folder from outside SilverStripe, for example, if you have uploaded files via FTP.'));
		
		return $form;
	}
	
	function doSync($data, $form) {
		return Filesystem::sync();
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
	public function deleteunusedthumbnails($request) {
		// Protect against CSRF on destructive action
		if(!SecurityToken::inst()->checkRequest($request)) return $this->httpError(400);
		
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
/**
 * Delete multiple {@link Folder} records (and the associated filesystem nodes).
 * Usually used through the {@link AssetAdmin} interface.
 * 
 * @package cms
 * @subpackage batchactions
 */
class AssetAdmin_DeleteBatchAction extends CMSBatchAction {
	function getActionTitle() {
		// _t('AssetAdmin_left.ss.SELECTTODEL','Select the folders that you want to delete and then click the button below')
		return _t('AssetAdmin_DeleteBatchAction.TITLE', 'Delete folders');
	}

	function run(DataObjectSet $records) {
		$status = array(
			'modified'=>array(),
			'deleted'=>array()
		);
		
		foreach($records as $record) {
			$id = $record->ID;
			
			// Perform the action
			if($record->canDelete()) $record->delete();

			$status['deleted'][$id] = array();

			$record->destroy();
			unset($record);
		}

		return Convert::raw2json($status);
	}
}
?>
