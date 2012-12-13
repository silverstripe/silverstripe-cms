<?php
/**
 * AssetAdmin is the 'file store' section of the CMS.
 * It provides an interface for maniupating the File and Folder objects in the system.
 * 
 * @package cms
 * @subpackage assets
 */
class AssetAdmin extends LeftAndMain implements PermissionProvider{

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
		'delete',
		'DeleteItemsForm',
		'getsubtree',
		'movemarked',
		'removefile',
		'savefile',
		'deleteUnusedThumbnails' => 'ADMIN',
		'doSync',
		'filter',
	);
	
	/**
	 * Return fake-ID "root" if no ID is found (needed to upload files into the root-folder)
	 */
	public function currentPageID() {
		if(is_numeric($this->request->requestVar('ID')))	{
			return $this->request->requestVar('ID');
		} elseif (is_numeric($this->urlParams['ID'])) {
			return $this->urlParams['ID'];
		} elseif(Session::get("{$this->class}.currentPage")) {
			return Session::get("{$this->class}.currentPage");
		} else {
			return 0;
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
		$frameworkDir = FRAMEWORK_DIR;
		Requirements::customScript(<<<JS
			_TREE_ICONS = {};
			_TREE_ICONS['Folder'] = {
					fileIcon: '$frameworkDir/javascript/tree/images/page-closedfolder.gif',
					openFolderIcon: '$frameworkDir/javascript/tree/images/page-openfolder.gif',
					closedFolderIcon: '$frameworkDir/javascript/tree/images/page-closedfolder.gif'
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
		// Overwrite name filter to search both Name and Title attributes
		$context->removeFilterByName('Name');
		$params = $this->request->requestVar('q');
		$list = $context->getResults($params);

		// Don't filter list when a detail view is requested,
		// to avoid edge cases where the filtered list wouldn't contain the requested
		// record due to faulty session state (current folder not always encoded in URL, see #7408).
		if(!$folder->ID && $this->request->requestVar('ID') === null && ($this->request->param('ID') == 'field')) {
			return $list;
		}

		// Re-add previously removed "Name" filter as combined filter
		// TODO Replace with composite SearchFilter once that API exists
		if(isset($params['Name'])) {
			$list = $list->where(sprintf(
				'"Name" LIKE \'%%%s%%\' OR "Title" LIKE \'%%%s%%\'',
				Convert::raw2sql($params['Name']),
				Convert::raw2sql($params['Name'])
			));
		}

		// Always show folders at the top		
		$list = $list->sort('(CASE WHEN "File"."ClassName" = \'Folder\' THEN 0 ELSE 1 END), "Name"');

		// If a search is conducted, check for the "current folder" limitation.
		// Otherwise limit by the current folder as denoted by the URL.
		if(!$params || @$params['CurrentFolderOnly']) {
			$list = $list->filter('ParentID', $folder->ID);
		}

		// Category filter
		if(isset($params['AppCategory'])) {
			if(isset(File::$app_categories[$params['AppCategory']])) {
				$exts = File::$app_categories[$params['AppCategory']];
			} else {
				$exts = array();
			}
			$categorySQLs = array();
			foreach($exts as $ext) $categorySQLs[] = '"File"."Name" LIKE \'%.' . $ext . '\'';
			// TODO Use DataList->filterAny() once OR connectives are implemented properly
			if (count($categorySQLs) > 0) {
				$list = $list->where('(' . implode(' OR ', $categorySQLs) . ')');
			}
		}

		return $list;
	}

	public function getEditForm($id = null, $fields = null) {
		$form = parent::getEditForm($id, $fields);
		$folder = ($id && is_numeric($id)) ? DataObject::get_by_id('Folder', $id, false) : $this->currentPage();
		$fields = $form->Fields();
		$title = ($folder && $folder->exists()) ? $folder->Title : _t('AssetAdmin.FILES', 'Files');
		$fields->push(new HiddenField('ID', false, $folder ? $folder->ID : null));

		// File listing
		$gridFieldConfig = GridFieldConfig::create()->addComponents(
			new GridFieldToolbarHeader(),
			new GridFieldFilterHeader(),
			new GridFieldSortableHeader(),
			new GridFieldDataColumns(),
			new GridFieldPaginator(15),
			new GridFieldEditButton(),
			new GridFieldDeleteAction(),
			new GridFieldDetailForm(),
			GridFieldLevelup::create($folder->ID)->setLinkSpec('admin/assets/show/%d')
		);

		$gridField = new GridField('File', $title, $this->getList(), $gridFieldConfig);
		$columns = $gridField->getConfig()->getComponentByType('GridFieldDataColumns');
		$columns->setDisplayFields(array(
			'StripThumbnail' => '',
			// 'Parent.FileName' => 'Folder',
			'Title' => _t('File.Name'),
			'Created' => _t('AssetAdmin.CREATED', 'Date'),
			'Size' => _t('AssetAdmin.SIZE', 'Size'),
		));
		$columns->setFieldCasting(array(
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
					'<a class="ss-ui-button ss-ui-action-constructive cms-panel-link" data-pjax-target="Content" data-icon="drive-upload" href="%s">%s</a>',
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
					'<a class="ss-ui-button ss-ui-action-constructive cms-add-folder-link" data-icon="add" data-url="%s" href="%s">%s</a>',
					Controller::join_links($this->Link('AddForm'), '?' . http_build_query(array(
						'action_doAdd' => 1,
						'ParentID' => $folder->ID,
						'SecurityID' => $form->getSecurityToken()->getValue()
					))),
					Controller::join_links($this->Link('addfolder'), '?ParentID=' . $folder->ID),
					_t('Folder.AddFolderButton', 'Add folder')
				)
			);
		} else {
			$addFolderBtn = '';
		}

		if($folder->canEdit()) {
			$syncButton = new LiteralField(
				'SyncButton',
				sprintf(
					'<a class="ss-ui-button ss-ui-action ui-button-text-icon-primary ss-ui-button-ajax" data-icon="arrow-circle-double" title="%s" href="%s">%s</a>',
					_t('AssetAdmin.FILESYSTEMSYNCTITLE', 'Update the CMS database entries of files on the filesystem. Useful when new files have been uploaded outside of the CMS, e.g. through FTP.'),
					$this->Link('doSync'),
					_t('AssetAdmin.FILESYSTEMSYNC','Sync files')
				)
			);
		} else {
			$syncButton = null;
		}
		
		// Move existing fields to a "details" tab, unless they've already been tabbed out through extensions.
		// Required to keep Folder->getCMSFields() simple and reuseable,
		// without any dependencies into AssetAdmin (e.g. useful for "add folder" views).
		if(!$fields->hasTabset()) {
			$tabs = new TabSet('Root', 
				$tabList = new Tab('ListView', _t('AssetAdmin.ListView', 'List View')),
				$tabTree = new Tab('TreeView', _t('AssetAdmin.TreeView', 'Tree View'))
			);
			$tabList->addExtraClass("content-listview");
			$tabTree->addExtraClass("content-treeview");
			if($fields->Count() && $folder->exists()) {
				$tabs->push($tabDetails = new Tab('DetailsView', _t('AssetAdmin.DetailsView', 'Details')));
				$tabDetails->addExtraClass("content-galleryview");
				foreach($fields as $field) {
					$fields->removeByName($field->getName());
					$tabDetails->push($field);
				}
			}
			$fields->push($tabs);
		}

		// List view
		$fields->addFieldsToTab('Root.ListView', array(
			$actionsComposite = CompositeField::create(
				CompositeField::create(
					$uploadBtn,
					$addFolderBtn,
					$syncButton //TODO: add this into a batch actions menu as in https://github.com/silverstripe/silverstripe-design/raw/master/Design/ss3-ui_files-manager-list-view.jpg
				)->addExtraClass('cms-actions-row')
			)->addExtraClass('cms-content-toolbar field'),
			$gridField
		));
		
		$treeField = new LiteralField('Tree', '');
		// Tree view
		$fields->addFieldsToTab('Root.TreeView', array(
			clone $actionsComposite,
			// TODO Replace with lazy loading on client to avoid performance hit of rendering potentially unused views
			new LiteralField(
				'Tree',
				$treeField->createTag(
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

		// Move actions to "details" tab (they don't make sense on list/tree view)
		$actions = $form->Actions();
		$saveBtn = $actions->fieldByName('action_save');
		$deleteBtn = $actions->fieldByName('action_delete');
		if(($saveBtn || $deleteBtn) && $fields->fieldByName('Root.DetailsView')) {
			$actions->removeByName('action_save');
			$actions->removeByName('action_delete');
			$fields->addFieldToTab(
				'Root.DetailsView', 
				CompositeField::create($saveBtn,$deleteBtn)->addExtraClass('Actions')
			);
		}
		


		$fields->setForm($form);
		$form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
		// TODO Can't merge $FormAttributes in template at the moment
		$form->addExtraClass('cms-edit-form cms-panel-padded center ' . $this->BaseCSSClasses());
		$form->setAttribute('data-pjax-fragment', 'CurrentForm');
		$form->Fields()->findOrMakeTab('Root')->setTemplate('CMSTabSet');

		$this->extend('updateEditForm', $form);

		return $form;
	}

	public function addfolder($request) {
		$obj = $this->customise(array(
			'EditForm' => $this->AddForm()
		));

		if($request->isAjax()) {
			// Rendering is handled by template, which will call EditForm() eventually
			$content = $obj->renderWith($this->getTemplatesWithSuffix('_Content'));
		} else {
			$content = $obj->renderWith($this->getViewer('show'));
		}

		return $content;
	}

	public function delete($data, $form) {
		$className = $this->stat('tree_class');
		
		$record = DataObject::get_by_id($className, Convert::raw2sql($data['ID']));
		if($record && !$record->canDelete()) return Security::permissionFailure();
		if(!$record || !$record->ID) throw new HTTPResponse_Exception("Bad record ID #" . (int)$data['ID'], 404);
		$parentID = $record->ParentID;
		$record->delete();
		$this->setCurrentPageID(null);

		$this->response->addHeader('X-Status', rawurlencode(_t('LeftAndMain.DELETED', 'Deleted.')));
		$this->response->addHeader('X-Pjax', 'Content');
		return $this->redirect(Controller::join_links($this->Link('show'), $parentID ? $parentID : 0));
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
			'flash' => _t('AssetAdmin.AppCategoryFlash', 'Flash', 'The fileformat'),
			'zip' => _t('AssetAdmin.AppCategoryArchive', 'Archive', 'A collection of files'),
			'doc' => _t('AssetAdmin.AppCategoryDocument', 'Document')
		);
		$context->addField(
			$typeDropdown = new DropdownField(
				'q[AppCategory]',
				_t('AssetAdmin.Filetype', 'File type'),
				$appCategories
			)
		);

		$typeDropdown->setEmptyString(' ');

		$context->addField(
			new CheckboxField('q[CurrentFolderOnly]', _t('AssetAdmin.CurrentFolderOnly', 'Limit to current folder?'))
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
			FormAction::create('doSearch',  _t('CMSMain_left.ss.APPLY FILTER', 'Apply Filter'))
			->addExtraClass('ss-ui-action-constructive'),
			Object::create('ResetFormAction', 'clear', _t('CMSMain_left.ss.RESET', 'Reset'))
		);
		
		$form = new Form($this, 'filter', $fields, $actions);
		$form->setFormMethod('GET');
		$form->setFormAction(Controller::join_links($this->Link('show'), $folder->ID));
		$form->addExtraClass('cms-search-form');
		$form->loadDataFrom($this->request->getVars());
		$form->disableSecurityToken();
		// This have to match data-name attribute on the gridfield so that the javascript selectors work
		$form->setAttribute('data-gridfield', 'File');
		return $form;
	}
	
	public function AddForm() {
		$folder = singleton('Folder');
		$form = new Form(
			$this,
			'AddForm',
			new FieldList(
				new TextField("Name", _t('File.Name')),
				new HiddenField('ParentID', false, $this->request->getVar('ParentID'))
			),
			new FieldList(
				FormAction::create('doAdd', _t('AssetAdmin_left.ss.GO','Go'))
					->addExtraClass('ss-ui-action-constructive')->setAttribute('data-icon', 'accept')
					->setTitle(_t('AssetAdmin.ActionAdd', 'Add folder'))
			)
		);
		$form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
		// TODO Can't merge $FormAttributes in template at the moment
		$form->addExtraClass('add-form cms-add-form cms-edit-form cms-panel-padded center ' . $this->BaseCSSClasses());
		
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
			&& $data['ParentID']
		) {
			$parentRecord = DataObject::get_by_id($class, $data['ParentID']);
			if(
				$parentRecord->hasMethod('canAddChildren') 
				&& !$parentRecord->canAddChildren()
			) return Security::permissionFailure($this);
		} else {
			$parentRecord = null;
		}

		$parent = (isset($data['ParentID']) && is_numeric($data['ParentID'])) ? (int)$data['ParentID'] : 0;
		$name = (isset($data['Name'])) ? basename($data['Name']) : _t('AssetAdmin.NEWFOLDER',"NewFolder");
		if(!$parentRecord || !$parentRecord->ID) $parent = 0;
		
		// Get the folder to be created		
		if($parentRecord && $parentRecord->ID) $filename = $parentRecord->FullPath . $name;
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

		if($parentRecord) {
			return $this->redirect(Controller::join_links($this->Link('show'), $parentRecord->ID));
		} else {
			return $this->redirect($this->Link());
		}
	}

	/**
	 * Custom currentPage() method to handle opening the 'root' folder
	 */
	public function currentPage() {
		$id = $this->currentPageID();
		if($id && is_numeric($id) && $id > 0) {
			$folder = DataObject::get_by_id('Folder', $id);
			if($folder && $folder->exists()) {
				return $folder;
			}
		}
		return new Folder();
	}
	
	public function getSiteTreeFor($className, $rootID = null, $childrenMethod = null, $numChildrenMethod = null, $filterFunction = null, $minNodeCount = 30) {
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
	 * Can be queried with an ajax request to trigger the filesystem sync. It returns a FormResponse status message
	 * to display in the CMS
	 */
	public function doSync() {
		$message = Filesystem::sync();
		$this->response->addHeader('X-Status', rawurlencode($message));
		
		return;
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
		
		$message = _t(
			'AssetAdmin.THUMBSDELETED', 
			'{count} unused thumbnails have been deleted', 
			array('count' => $count)
		);
		$this->response->addHeader('X-Status', rawurlencode($message));
		return;
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

		// The root element should explicitly point to the root node.
		// Uses session state for current record otherwise.
		$items[0]->Link = Controller::join_links(singleton('AssetAdmin')->Link('show'), 0);

		// If a search is in progress, don't show the path
		if($this->request->requestVar('q')) {
			$items = $items->limit(1);
			$items->push(new ArrayData(array(
				'Title' => _t('LeftAndMain.SearchResults', 'Search Results'),
				'Link' => Controller::join_links($this->Link(), '?' . http_build_query(array('q' => $this->request->requestVar('q'))))
			)));
		}

		// If we're adding a folder, note that in breadcrumbs as well
		if($this->request->param('Action') == 'addfolder') {
			$items->push(new ArrayData(array(
				'Title' => _t('Folder.AddFolderButton', 'Add folder'),
				'Link' => false
			)));
		}

		return $items;
	}

	public function providePermissions() {
		$title = _t("AssetAdmin.MENUTITLE", LeftAndMain::menu_title_for_class($this->class));
		return array(
			"CMS_ACCESS_AssetAdmin" => array(
				'name' => _t('CMSMain.ACCESS', "Access to '{title}' section", array('title' => $title)),
				'category' => _t('Permission.CMS_ACCESS_CATEGORY', 'CMS Access')
			)
		);
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

