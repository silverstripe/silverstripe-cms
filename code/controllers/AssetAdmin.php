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
	
	static $menu_title = 'Files';

	public static $tree_class = 'Folder';
	
	/**
	 * @see Upload->allowedMaxFileSize
	 * @var int
	 */
	public static $allowed_max_file_size;
	
	public static $allowed_actions = array(
		'addfolder',
		'DeleteItemsForm',
		'getsubtree',
		'movemarked',
		'removefile',
		'savefile',
		'deleteUnusedThumbnails' => 'ADMIN',
		'SyncForm',
		'filter',
	);
	
	/**
	 * Return fake-ID "root" if no ID is found (needed to upload files into the root-folder)
	 */
	public function currentPageID() {
		if($this->request->requestVar('ID'))	{
			return $this->request->requestVar('ID');
		} elseif (is_numeric($this->urlParams['ID'])) {
			return $this->urlParams['ID'];
		} elseif(Session::get("{$this->class}.currentPage")) {
			return Session::get("{$this->class}.currentPage");
		} else {
			return "root";
		}
	}

	/**
	 * Set up the controller, in particular, re-sync the File database with the assets folder./
	 */
	public function init() {
		parent::init();
		
		// Create base folder if it doesnt exist already
		if(!file_exists(ASSETS_PATH)) Filesystem::makeFolder(ASSETS_PATH);

		Requirements::javascript(CMS_DIR . "/javascript/AssetAdmin.js");
		Requirements::javascript(CMS_DIR . '/javascript/CMSMain.GridField.js');
		Requirements::add_i18n_javascript(CMS_DIR . '/javascript/lang', false, true);
		Requirements::css(CMS_DIR . "/css/screen.css");

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
	 * Returns the files and subfolders contained in the currently selected folder,
	 * defaulting to the root node. Doubles as search results, if any search parameters
	 * are set through {@link SearchForm()}.
	 * 
	 * @return SS_List
	 */
	public function getList() {
		$folder = $this->currentPage();
		$context = $this->getSearchContext();
		$params = $this->request->requestVar('q');
		$list = $context->getResults($params);

		// Always show folders at the top		
		$list->sort('(CASE WHEN "File"."ClassName" = \'Folder\' THEN 0 ELSE 1 END)');

		// If a search is conducted, check for the "current folder" limitation.
		// Otherwise limit by the current folder as denoted by the URL.
		if(!$params || @$params['CurrentFolderOnly']) {
			$list->filter('ParentID', $folder->ID);
		}

		// Category filter
		if(isset($params['AppCategory'])) {
			$exts = File::$app_categories[$params['AppCategory']];
			$categorySQLs = array();
			foreach($exts as $ext) $categorySQLs[] = '"File"."Name" LIKE \'%.' . $ext . '\'';
			// TODO Use DataList->filterAny() once OR connectives are implemented properly
			$list->where('(' . implode(' OR ', $categorySQLs) . ')');
		}

		return $list;
	}

	public function getEditForm($id = null, $fields = null) {
		$form = parent::getEditForm($id, $fields);
		$folder = ($id && is_numeric($id)) ? DataObject::get_by_id('Folder', $id, false) : $this->currentPage();
		$fields = $form->Fields();

		$fields->push(new HiddenField('ID', false, $folder->ID));

		// File listing
		$gridFieldConfig = GridFieldConfig::create()->addComponents(
			new GridFieldSortableHeader(),
			new GridFieldDefaultColumns(),
			new GridFieldPaginator(15),
			new GridFieldAction_Edit(),
			new GridFieldAction_Delete(),
			new GridFieldPopupForms()
		);
		$gridField = new GridField('File','Files', $this->getList(), $gridFieldConfig);
		$gridField->setDisplayFields(array(
			'StripThumbnail' => '',
			// 'Parent.FileName' => 'Folder',
			'Title' => _t('File.Name'),
			'Created' => _t('AssetAdmin.CREATED', 'Date'),
			'Size' => _t('AssetAdmin.SIZE', 'Size'),
		));
		$gridField->setFieldCasting(array(
			'Created' => 'Date->Nice'
		));
		$gridField->setAttribute(
			'data-url-folder-template', 
			Controller::join_links($this->Link('show'), '%s')
		);

		if($folder->canCreate()) {
			$uploadBtn = new LiteralField(
				'UploadButton', 
				sprintf(
					'<a class="ss-ui-button ss-ui-action-constructive cms-panel-link" data-target-panel=".cms-content" data-icon="drive-upload" href="%s">%s</a>',
					Controller::join_links(singleton('CMSFileAddController')->Link(), '?ID=' . $folder->ID),
					_t('Folder.UploadFilesButton', 'Upload')
				)
			);	
		} else {
			$uploadBtn = null;
		}

		if(!$folder->hasMethod('canAddChildren') || ($folder->hasMethod('canAddChildren') && $folder->canAddChildren())) {
			// TODO Will most likely be replaced by GridField logic
			$addFolderBtn = new LiteralField(
				'AddFolderButton', 
				sprintf(
					'<a class="ss-ui-button ss-ui-action-constructive cms-panel-link cms-page-add-button" data-icon="add" href="%s">%s</a>',
					Controller::join_links($this->Link('addfolder'), '?ParentID=' . $folder->ID),
					_t('Folder.AddFolderButton', 'Add folder')
				)
			);
		} else {
			$addFolderBtn = '';
		}
		
		// Move existing fields to a "details" tab, unless they've already been tabbed out through extensions.
		// Required to keep Folder->getCMSFields() simple and reuseable,
		// without any dependencies into AssetAdmin (e.g. useful for "add folder" views).
		if(!$fields->hasTabset()) {
			$tabs = new TabSet('Root', 
				$tabList = new Tab('ListView', _t('AssetAdmin.ListView', 'List View')),
				$tabTree = new Tab('TreeView', _t('AssetAdmin.TreeView', 'Tree View'))
			);
			if($fields->Count() && $folder->exists()) {
				$tabs->push($tabDetails = new Tab('DetailsView', _t('AssetAdmin.DetailsView', 'Details')));
				foreach($fields as $field) {
					$fields->removeByName($field->Name());
					$tabDetails->push($field);
				}
			}
			$fields->push($tabs);
		}
		
		// List view
		$fields->addFieldsToTab('Root.ListView', array(
			$actionsComposite = Object::create('CompositeField',
				Object::create('CompositeField',
					$uploadBtn,
					$addFolderBtn
				)->addExtraClass('cms-actions-row')
			)->addExtraClass('cms-content-toolbar field'),
			$gridField
		));
		
		// Tree view
		$fields->addFieldsToTab('Root.TreeView', array(
			clone $actionsComposite,
			// TODO Replace with lazy loading on client to avoid performance hit of rendering potentially unused views
			new LiteralField(
				'Tree',
				FormField::createTag(
					'div', 
					array(
						'class' => 'cms-tree', 
						'data-url-tree' => $this->Link('getsubtree'), 
						'data-url-savetreenode' => $this->Link('savetreenode')
					),
					$this->SiteTreeAsUL()
				)
			)
		));

		$fields->setForm($form);
		$form->addExtraClass('cms-edit-form');
		$form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
		// TODO Can't merge $FormAttributes in template at the moment
		$form->addExtraClass('center ss-tabset ' . $this->BaseCSSClasses());
		$form->Fields()->findOrMakeTab('Root')->setTemplate('CMSTabSet');

		$this->extend('updateEditForm', $form);

		return $form;
	}

	public function addfolder($request) {
		$obj = $this->customise(array(
			'EditForm' => $this->AddForm()
		));

		if($this->isAjax()) {
			// Rendering is handled by template, which will call EditForm() eventually
			$content = $obj->renderWith($this->getTemplatesWithSuffix('_Content'));
		} else {
			$content = $obj->renderWith($this->getViewer('show'));
		}

		return $content;
	}

	public function getSearchContext() {
		$context = singleton('File')->getDefaultSearchContext();
		
		// Namespace fields, for easier detection if a search is present
		foreach($context->getFields() as $field) $field->setName(sprintf('q[%s]', $field->getName()));
		foreach($context->getFilters() as $filter) $filter->setFullName(sprintf('q[%s]', $filter->getFullName()));

		// Customize fields		
		$appCategories = array(
			'image' => _t('AssetAdmin.AppCategoryImage', 'Image'),
			'audio' => _t('AssetAdmin.AppCategoryAudio', 'Audio'),
			'mov' => _t('AssetAdmin.AppCategoryVideo', 'Video'),
			'flash' => _t('AssetAdmin.AppCategoryFlash', 'Flash', PR_MEDIUM, 'The fileformat'),
			'zip' => _t('AssetAdmin.AppCategoryArchive', 'Archive', PR_MEDIUM, 'A collection of files'),
		);
		$context->addField(
			new DropdownField(
				'q[AppCategory]',
				_t('AssetAdmin.Filetype', 'File type'),
				$appCategories,
				null,
				null,
				' '
			)
		);
		$context->addField(
			new CheckboxField('q[CurrentFolderOnly]' ,_t('AssetAdmin.CurrentFolderOnly', 'Limit to current folder?'))
		);
		$context->getFields()->removeByName('q[Title]');

		return $context;
	}
	
	/**
	 * Returns a form for filtering of files and assets gridfield.
	 * Result filtering takes place in {@link getList()}.
	 *
	 * @return Form
	 * @see AssetAdmin.js
	 */
	public function SearchForm() {
		$folder = $this->currentPage();
		$context = $this->getSearchContext();

		$fields = $context->getSearchFields();
		$actions = new FieldList(
			Object::create('ResetFormAction', 'clear', _t('CMSMain_left.ss.CLEAR', 'Clear'))
				->addExtraClass('ss-ui-action-minor'),
			FormAction::create('doSearch',  _t('CMSMain_left.ss.SEARCH', 'Search'))
		);
		
		$form = new Form($this, 'filter', $fields, $actions);
		$form->setFormMethod('GET');
		$form->setFormAction(Controller::join_links($this->Link('show'), $folder->ID ? $folder->ID : 'root'));
		$form->addExtraClass('cms-search-form');
		$form->loadDataFrom($this->request->getVars());
		$form->disableSecurityToken();
		// This have to match data-name attribute on the gridfield so that the javascript selectors work
		$form->setAttribute('data-gridfield', 'File');
		return $form;
	}
	
	public function AddForm() {
		$form = parent::AddForm();
		$folder = singleton('Folder');

		$form->Actions()->fieldByName('action_doAdd')
			->setTitle(_t('AssetAdmin.ActionAdd', 'Add folder'))
			->setAttribute('data-icon', 'accept');
		
		$fields = $folder->getCMSFields();
		$fields->replaceField('Name', new TextField("Name", _t('File.Name')));
		$fields->dataFieldByName('ParentID')->setValue($this->request->getVar('ParentID'));
		$form->setFields($fields);

		$form->addExtraClass('cms-edit-form');
		$form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
		$form->addExtraClass('center ' . $this->BaseCSSClasses());
		
		return $form;
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
			singleton($class)->hasExtension('Hierarchy') 
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
		$record->Name = $record->Title = basename($filename);

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

		if($this->isAjax()) {
			$link = Controller::join_links($this->Link('show'), $record->ID);
			$this->getResponse()->addHeader('X-ControllerURL', $link);
			$form = $this->getEditForm($record->ID);
			return $form->forTemplate();
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
			return DataObject::get_by_id('Folder', $id);
		} else {
			// ID is either '0' or 'root'
			return singleton('Folder');
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
	public function SyncForm() {
		$form = new Form(
			$this,
			'SyncForm',
			new FieldList(
			),
			new FieldList(
				FormAction::create('doSync', _t('FILESYSTEMSYNC','Look for new files'))
					->describe(_t('AssetAdmin_left.ss.FILESYSTEMSYNC_DESC', 'SilverStripe maintains its own database of the files &amp; images stored in your assets/ folder.  Click this button to update that database, if files are added to the assets/ folder from outside SilverStripe, for example, if you have uploaded files via FTP.'))
					->setUseButtonTag(true)
			)
		);
		$form->setFormMethod('GET');
		
		return $form;
	}
	
	public function doSync($data, $form) {
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

	/**
	 * @return ArrayList
	 */
	public function Breadcrumbs($unlinked = false) {
		$items = parent::Breadcrumbs($unlinked);

		// The root element should explicitly point to the root node
		$items[0]->Link = Controller::join_links($this->Link('show'), 'root');

		// If a search is in progress, don't show the path
		if($this->request->requestVar('q')) {
			$items = $items->getRange(0, 1);
			$items->push(new ArrayData(array(
				'Title' => _t('LeftAndMain.SearchResults', 'Search Results'),
				'Link' => Controller::join_links($this->Link(), '?' . http_build_query(array('q' => $this->request->requestVar('q'))))
			)));
		}

		return $items;
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
	public function getActionTitle() {
		// _t('AssetAdmin_left.ss.SELECTTODEL','Select the folders that you want to delete and then click the button below')
		return _t('AssetAdmin_DeleteBatchAction.TITLE', 'Delete folders');
	}

	public function run(SS_List $records) {
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

