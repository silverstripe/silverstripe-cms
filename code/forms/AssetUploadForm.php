<?php
/**
 * Form interface to upload one or more files to a predefined folder on the filesystem,
 * and batch edit them afterwards through {@link File->getCMSFields()}.
 * Its a two step process, as files need to be present as {@link File} records and on the filesystem
 * before we can allow editing them - so the form typically gets submitted twice.
 * 
 * Replaces most of the functionality formerly contained in {@link AssetAdmin}
 * in a reuseable format (e.g. useful for inline batch file uploads in other areas like {@link ModelAdmin}).
 * The form can be enhanced with clientside uploading libraries to streamline the upload process.
 * 
 * Caution: Relies on parent controller to restrict access.
 */
class AssetUploadForm extends Form {
	
	protected $template = 'AssetUploadForm';
	
	/**
	 * @var Folder
	 */
	protected $folder = null;
	
	/**
	 * @var DataList
	 */
	protected $files;
	
	function __construct($controller, $name) {
		$fields = new FieldList(
			new FileField('Files', 'Choose files to upload')
		);
		$actions = new FieldList(
			// TODO Only display applicable button based on $files 
			new FormAction('doUpload', 'Upload'),
			new FormAction('doSave', 'Save')
		);
		$validator = new RequiredFields(array('Files'));
		if(!$this->folder) $this->folder = Folder::findOrMake(ASSETS_PATH);
		
		$this->addExtraClass('asset-upload');
		
		parent::__construct($controller, $name, $fields, $actions, $validator);
	}
	
	/**
	 * Overload routing to allow a whitelist of direct URL actions which
	 * are not form submission handlers
	 */
	public function handleAction($request) {
		$action = $request->param('Action');
		if($action == 'viewfieldlistforfile') return $this->viewfieldlistforfile($request);
		
		return parent::handleAction($request);
	}
	
	function FormAction() {
		// Not an ideal place for includes, but there's no index() or init() method
		Requirements::javascript(CMS_DIR . '/javascript/AssetUploadForm.js');
		return parent::FormAction();
	}
	
	/**
	 * Upload one or more files,
	 * create {@link File} records for them and store them on the fileystem.
	 */
	function doUpload($data, $form) {
		$newFiles = array();
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
		
		// TODO Add file to folder
		// if($data['FolderID'] && $data['FolderID'] != '') $folder = DataObject::get_by_id("Folder", $data['FolderID']);
		// else $folder = singleton('Folder');
		$folder = $this->folder;

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
					$validator->setAllowedMaxFileSize(AssetAdmin::$allowed_max_file_size);
					
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
					if($newFile) $newFiles[] = $newFile;
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
			$fileObj = DataObject::get_by_id('File', $newFile);
			// notify file object after uploading
			if (method_exists($fileObj, 'onAfterUpload')) $fileObj->onAfterUpload();
		}
		
		// TODO Integrate form with inline image upload
		
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
		
		// TODO Reduce coupling with AssetAdmin->upload()
		$url = Controller::join_links($this->controller->Link('upload'), '?FileIDs=' . implode(',', $newFiles));
		return Director::redirect($url);
	}
	
	/**
	 * Save data on existing (already uploaded) files
	 */
	function doSave($data, $form) {
		// Save data for each file
		if(@$data['FilesData']) foreach($data['FilesData'] as $fileID => $fileData) {
			$file = DataObject::get_by_id("File", $request->getVar('ID'));
			// TODO Should really use Form->saveInto() with a custom fieldlist just for this namespace.
			// TODO Validation for required fields etc.
			$file->update($fileData);
			try {
				$file->write(); // might cause model validation errors
			} catch(ValidationException $e) {
				// TODO Render validation errors on individual namespaced form elements
				continue;
			}
		}
	}
	
	/**
	 * @param String Absolute folder path
	 */
	function setFolder($folder) {
		$this->folder = $folder;
	}
	
	/**
	 * @return String
	 */
	function getFolder() {
		return $this->folder;
	}
	
	/**
	 * @param DataLIst
	 */
	function setFiles($files) {
		$this->files = $files;
	}
	
	/**
	 * @todo Either change to presenter pattern, or fix <% $Up.FileFields(<file>) %> notation in template
	 * 
	 * @return DataList
	 */
	function getFiles() {
		if($this->files) {
			$files = new ArrayList($this->files->toArray());
			if($files) foreach($files as $file) {
				$file->Fields = $this->getFieldListForFile($file);
			}
			return $files;
		}
	}
	
	/**
	 * Returns a list of HTML fields to be inserted into an existing form.
	 * Invoked for potentially dozens of uploaded files.
	 * 
	 * @todo Allow for retrieval of multiple file interfaces (more efficient for batch upload).
	 * 
	 * @param SS_HTTPRequest
	 * @return String HTML
	 */
	function viewfieldlistforfile($request) {
		$html = '';
		$file = DataObject::get_by_id("File", $request->getVar('ID'));
		$file->Fields = $this->getFieldListForFile($file);
		return $file->renderWith('AssetUploadForm_File');
	}
	
	/**
	 * Similar to {@link AssetTableField->getCustomFieldsFor()}.
	 * Will return different fields based on the file in question.
	 * 
	 * Note that a similar UI will be required for editing files individually
	 * outside of the scope of this form, so keep customizations here to a minimum.
	 * 
	 * @param File
	 * @return FieldSet
	 */
	function getFieldListForFile($file) {
		// TODO Change to presenter pattern? Otherwise we duplicate this logic in AssetTableField (or whatever comes after that)
		// TODO Get fields from a variation on getCMSFields (no tabs, custom layout)
		// TODO Hook fields up to actual rather than fake form so elements like TreeDropdownField work
		$fields = new FieldSet(
			new TextField("Title", _t('AssetTableField.TITLE','Title')),
			new TextField("Name", _t('AssetTableField.FILENAME','Filename')),
			// new LiteralField("AbsoluteURL", $urlLink),
			new ReadonlyField("FileType", _t('AssetTableField.TYPE','Type')),
			new ReadonlyField("Size", _t('AssetTableField.SIZE','Size'), $file->getSize())
			// new DropdownField("OwnerID", _t('AssetTableField.OWNER','Owner'), Member::mapInCMSGroups())
		);
		
		// Create fake form in order to use loadDataFrom()
		$form = new Form($this->controller, 'UploadForm', $fields, new FieldList());
		$form->loadDataFrom($file);
		
		// Namespace fields by ID so the form can handle submission of multiple files
		foreach($fields->dataFields() as $field) {
			$field->setName(sprintf('FilesData[%s][%s]', $file->ID, $field->getName()));
		}
		
		return $fields;
	}
	
}