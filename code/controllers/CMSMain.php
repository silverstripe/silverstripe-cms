<?php
/**
 * The main "content" area of the CMS.
 *
 * This class creates a 2-frame layout - left-tree and right-form - to sit beneath the main
 * admin menu.
 *
 * @package cms
 * @subpackage controller
 * @todo Create some base classes to contain the generic functionality that will be replicated.
 */
class CMSMain extends LeftAndMain implements CurrentPageIdentifier, PermissionProvider {

	private static $url_segment = 'pages';

	private static $url_rule = '/$Action/$ID/$OtherID';

	// Maintain a lower priority than other administration sections
	// so that Director does not think they are actions of CMSMain
	private static $url_priority = 39;

	private static $menu_title = 'Edit Page';

	private static $menu_priority = 10;

	private static $tree_class = "SiteTree";

	private static $subitem_class = "Member";

	/**
	 * Amount of results showing on a single page.
	 *
	 * @config
	 * @var int
	 */
	private static $page_length = 15;

	private static $allowed_actions = array(
		'archive',
		'buildbrokenlinks',
		'deleteitems',
		'DeleteItemsForm',
		'dialog',
		'duplicate',
		'duplicatewithchildren',
		'publishall',
		'publishitems',
		'PublishItemsForm',
		'submit',
		'EditForm',
		'SearchForm',
		'SiteTreeAsUL',
		'getshowdeletedsubtree',
		'batchactions',
		'treeview',
		'listview',
		'ListViewForm',
		'childfilter',
	);

	/**
	 * Enable legacy batch actions.
	 * @deprecated since version 4.0
	 * @var array
	 * @config
	 */
	private static $enabled_legacy_actions = array();

	public function init() {
		// set reading lang
		if(SiteTree::has_extension('Translatable') && !$this->getRequest()->isAjax()) {
			Translatable::choose_site_locale(array_keys(Translatable::get_existing_content_languages('SiteTree')));
		}

		parent::init();

		Requirements::css(CMS_DIR . '/css/screen.css');
		Requirements::customCSS($this->generatePageIconsCss());

		Requirements::combine_files(
			'cmsmain.js',
			array_merge(
				array(
					CMS_DIR . '/javascript/CMSMain.js',
					CMS_DIR . '/javascript/CMSMain.EditForm.js',
					CMS_DIR . '/javascript/CMSMain.AddForm.js',
					CMS_DIR . '/javascript/CMSPageHistoryController.js',
					CMS_DIR . '/javascript/CMSMain.Tree.js',
					CMS_DIR . '/javascript/SilverStripeNavigator.js',
					CMS_DIR . '/javascript/SiteTreeURLSegmentField.js'
				),
				Requirements::add_i18n_javascript(CMS_DIR . '/javascript/lang', true, true)
			)
		);

		CMSBatchActionHandler::register('publish', 'CMSBatchAction_Publish');
		CMSBatchActionHandler::register('unpublish', 'CMSBatchAction_Unpublish');


		// Check legacy actions
		$legacy = $this->config()->enabled_legacy_actions;

		// Delete from live is unnecessary since we have unpublish which does the same thing
		if(in_array('CMSBatchAction_DeleteFromLive', $legacy)) {
			Deprecation::notice('4.0', 'Delete From Live is deprecated. Use Un-publish instead');
			CMSBatchActionHandler::register('deletefromlive', 'CMSBatchAction_DeleteFromLive');
		}

		// Delete action
		if(in_array('CMSBatchAction_Delete', $legacy)) {
			Deprecation::notice('4.0', 'Delete from Stage is deprecated. Use Archive instead.');
			CMSBatchActionHandler::register('delete', 'CMSBatchAction_Delete');
		} else {
			CMSBatchActionHandler::register('archive', 'CMSBatchAction_Archive');
			CMSBatchActionHandler::register('restore', 'CMSBatchAction_Restore');
		}
	}

	public function index($request) {
		// In case we're not showing a specific record, explicitly remove any session state,
		// to avoid it being highlighted in the tree, and causing an edit form to show.
		if(!$request->param('Action')) $this->setCurrentPageId(null);

		return parent::index($request);
	}

	public function getResponseNegotiator() {
		$negotiator = parent::getResponseNegotiator();
		$controller = $this;
		$negotiator->setCallback('ListViewForm', function() use(&$controller) {
			return $controller->ListViewForm()->forTemplate();
		});
		return $negotiator;
	}

	/**
	 * If this is set to true, the "switchView" context in the
	 * template is shown, with links to the staging and publish site.
	 *
	 * @return boolean
	 */
	public function ShowSwitchView() {
		return true;
	}

	/**
	 * Overloads the LeftAndMain::ShowView. Allows to pass a page as a parameter, so we are able
	 * to switch view also for archived versions.
	 */
	public function SwitchView($page = null) {
		if(!$page) {
			$page = $this->currentPage();
		}

		if($page) {
			$nav = SilverStripeNavigator::get_for_record($page);
			return $nav['items'];
		}
	}

	//------------------------------------------------------------------------------------------//
	// Main controllers

	//------------------------------------------------------------------------------------------//
	// Main UI components

	/**
	 * Override {@link LeftAndMain} Link to allow blank URL segment for CMSMain.
	 *
	 * @param string|null $action Action to link to.
	 * @return string
	 */
	public function Link($action = null) {
		$link = Controller::join_links(
			$this->stat('url_base', true),
			$this->stat('url_segment', true), // in case we want to change the segment
			'/', // trailing slash needed if $action is null!
			"$action"
		);
		$this->extend('updateLink', $link);
		return $link;
	}

	public function LinkPages() {
		return singleton('CMSPagesController')->Link();
	}

	public function LinkPagesWithSearch() {
		return $this->LinkWithSearch($this->LinkPages());
	}

	public function LinkTreeView() {
		return $this->LinkWithSearch(singleton('CMSMain')->Link('treeview'));
	}

	public function LinkListView() {
		return $this->LinkWithSearch(singleton('CMSMain')->Link('listview'));
	}

	public function LinkGalleryView() {
		return $this->LinkWithSearch(singleton('CMSMain')->Link('galleryview'));
	}

	public function LinkPageEdit($id = null) {
		if(!$id) $id = $this->currentPageID();
		return $this->LinkWithSearch(
			Controller::join_links(singleton('CMSPageEditController')->Link('show'), $id)
		);
	}

	public function LinkPageSettings() {
		if($id = $this->currentPageID()) {
			return $this->LinkWithSearch(
				Controller::join_links(singleton('CMSPageSettingsController')->Link('show'), $id)
			);
		}
	}

	public function LinkPageHistory() {
		if($id = $this->currentPageID()) {
			return $this->LinkWithSearch(
				Controller::join_links(singleton('CMSPageHistoryController')->Link('show'), $id)
			);
		}
	}

	public function LinkWithSearch($link) {
		// Whitelist to avoid side effects
		$params = array(
			'q' => (array)$this->getRequest()->getVar('q'),
			'ParentID' => $this->getRequest()->getVar('ParentID')
		);
		$link = Controller::join_links(
			$link,
			array_filter(array_values($params)) ? '?' . http_build_query($params) : null
		);
		$this->extend('updateLinkWithSearch', $link);
		return $link;
	}

	public function LinkPageAdd($extra = null, $placeholders = null) {
		$link = singleton("CMSPageAddController")->Link();
		$this->extend('updateLinkPageAdd', $link);

		if($extra) {
			$link = Controller::join_links ($link, $extra);
		}

		if($placeholders) {
			$link .= (strpos($link, '?') === false ? "?$placeholders" : "&amp;$placeholders");
		}

		return $link;
	}

	/**
	 * @return string
	 */
	public function LinkPreview() {
		$record = $this->getRecord($this->currentPageID());
		$baseLink = Director::absoluteBaseURL();
		if ($record && $record instanceof Page) {
			// if we are an external redirector don't show a link
			if ($record instanceof RedirectorPage && $record->RedirectionType == 'External') {
				$baseLink = false;
			}
			else {
				$baseLink = $record->Link('?stage=Stage');
			}
		}
		return $baseLink;
	}

	/**
	 * Return the entire site tree as a nested set of ULs
	 */
	public function SiteTreeAsUL() {
		// Pre-cache sitetree version numbers for querying efficiency
		Versioned::prepopulate_versionnumber_cache("SiteTree", "Stage");
		Versioned::prepopulate_versionnumber_cache("SiteTree", "Live");
		$html = $this->getSiteTreeFor($this->stat('tree_class'));

		$this->extend('updateSiteTreeAsUL', $html);

		return $html;
	}

	/**
	 * @return boolean
	 */
	public function TreeIsFiltered() {
		$query = $this->getRequest()->getVar('q');

		if (!$query || (count($query) === 1 && isset($query['FilterClass']) && $query['FilterClass'] === 'CMSSiteTreeFilter_Search')) {
			return false;
		}

		return true;
	}

	public function ExtraTreeTools() {
		$html = '';
		$this->extend('updateExtraTreeTools', $html);
		return $html;
	}

	/**
	 * Returns a Form for page searching for use in templates.
	 *
	 * Can be modified from a decorator by a 'updateSearchForm' method
	 *
	 * @return Form
	 */
	public function SearchForm() {
		// Create the fields
		$content = new TextField('q[Term]', _t('CMSSearch.FILTERLABELTEXT', 'Search'));
		$dateHeader = new HeaderField('q[Date]', _t('CMSSearch.PAGEFILTERDATEHEADING', 'Last edited'), 4);
		$dateFrom = new DateField(
			'q[LastEditedFrom]',
			_t('CMSSearch.FILTERDATEFROM', 'From')
		);
		$dateFrom->setConfig('showcalendar', true);
		$dateTo = new DateField(
			'q[LastEditedTo]',
			_t('CMSSearch.FILTERDATETO', 'To')
		);
		$dateTo->setConfig('showcalendar', true);
		$pageFilter = new DropdownField(
			'q[FilterClass]',
			_t('CMSMain.PAGES', 'Page status'),
			CMSSiteTreeFilter::get_all_filters()
		);
		$pageClasses = new DropdownField(
			'q[ClassName]',
			_t('CMSMain.PAGETYPEOPT', 'Page type', 'Dropdown for limiting search to a page type'),
			$this->getPageTypes()
		);
		$pageClasses->setEmptyString(_t('CMSMain.PAGETYPEANYOPT','Any'));

		// Group the Datefields
		$dateGroup = new FieldGroup(
			$dateHeader,
			$dateFrom,
			$dateTo
		);
		$dateGroup->setFieldHolderTemplate('FieldGroup_DefaultFieldHolder')->addExtraClass('stacked');

		// Create the Field list
		$fields = new FieldList(
			$content,
			$dateGroup,
			$pageFilter,
			$pageClasses
		);

		// Create the Search and Reset action
		$actions = new FieldList(
			FormAction::create('doSearch',  _t('CMSMain_left_ss.APPLY_FILTER', 'Search'))
				->addExtraClass('ss-ui-action-constructive'),
			Object::create('ResetFormAction', 'clear', _t('CMSMain_left_ss.CLEAR_FILTER', 'Clear'))
		);

		// Use <button> to allow full jQuery UI styling on the all of the Actions
		foreach($actions->dataFields() as $action) {
			$action->setUseButtonTag(true);
		}

		// Create the form
		$form = Form::create($this, 'SearchForm', $fields, $actions)
			->addExtraClass('cms-search-form')
			->setFormMethod('GET')
			->setFormAction($this->Link())
			->disableSecurityToken()
			->unsetValidator();

		// Load the form with previously sent search data
		$form->loadDataFrom($this->getRequest()->getVars());

		// Allow decorators to modify the form
		$this->extend('updateSearchForm', $form);

		return $form;
	}

	/**
	 * Returns a sorted array suitable for a dropdown with pagetypes and their translated name
	 *
	 * @return array
	 */
	protected function getPageTypes() {
		$pageTypes = array();
		foreach(SiteTree::page_type_classes() as $pageTypeClass) {
			$pageTypes[$pageTypeClass] = _t($pageTypeClass.'.SINGULARNAME', $pageTypeClass);
		}
		asort($pageTypes);
		return $pageTypes;
	}

	public function doSearch($data, $form) {
		return $this->getsubtree($this->getRequest());
	}

	/**
	 * @param bool $unlinked
	 * @return ArrayList
	 */
	public function Breadcrumbs($unlinked = false) {
		$items = parent::Breadcrumbs($unlinked);

		if($items->count() > 1) {
			// Specific to the SiteTree admin section, we never show the cms section and current
			// page in the same breadcrumbs block.
			$items->shift();
		}

		return $items;
	}

	/**
	 * Create serialized JSON string with site tree hints data to be injected into
	 * 'data-hints' attribute of root node of jsTree.
	 *
	 * @return string Serialized JSON
	 */
	public function SiteTreeHints() {
		$json = '';
		$classes = SiteTree::page_type_classes();

	 	$cacheCanCreate = array();
	 	foreach($classes as $class) $cacheCanCreate[$class] = singleton($class)->canCreate();

	 	// Generate basic cache key. Too complex to encompass all variations
	 	$cache = SS_Cache::factory('CMSMain_SiteTreeHints');
	 	$cacheKey = md5(implode('_', array(Member::currentUserID(), implode(',', $cacheCanCreate), implode(',', $classes))));
	 	if($this->getRequest()->getVar('flush')) $cache->clean(Zend_Cache::CLEANING_MODE_ALL);
	 	$json = $cache->load($cacheKey);
	 	if(!$json) {
			$def['Root'] = array();
			$def['Root']['disallowedChildren'] = array();

			// Contains all possible classes to support UI controls listing them all,
			// such as the "add page here" context menu.
			$def['All'] = array();

			// Identify disallows and set globals
			foreach($classes as $class) {
				$obj = singleton($class);
				if($obj instanceof HiddenClass) continue;

				// Name item
				$def['All'][$class] = array(
					'title' => $obj->i18n_singular_name()
				);

				// Check if can be created at the root
				$needsPerm = $obj->stat('need_permission');
				if(
					!$obj->stat('can_be_root')
					|| (!array_key_exists($class, $cacheCanCreate) || !$cacheCanCreate[$class])
					|| ($needsPerm && !$this->can($needsPerm))
				) {
					$def['Root']['disallowedChildren'][] = $class;
				}

				// Hint data specific to the class
				$def[$class] = array();

				$defaultChild = $obj->defaultChild();
				if($defaultChild !== 'Page' && $defaultChild !== null) {
					$def[$class]['defaultChild'] = $defaultChild;
				}

				$defaultParent = $obj->defaultParent();
				if ($defaultParent !== 1 && $defaultParent !== null) {
					$def[$class]['defaultParent'] = $defaultParent;
				}
			}

			$this->extend('updateSiteTreeHints', $def);

			$json = Convert::raw2json($def);
			$cache->save($json, $cacheKey);
		}
		return $json;
	}

	/**
	 * Populates an array of classes in the CMS
	 * which allows the user to change the page type.
	 *
	 * @return SS_List
	 */
	public function PageTypes() {
		$classes = SiteTree::page_type_classes();

		$result = new ArrayList();

		foreach($classes as $class) {
			$instance = singleton($class);

			if($instance instanceof HiddenClass) continue;

			// skip this type if it is restricted
			if($instance->stat('need_permission') && !$this->can(singleton($class)->stat('need_permission'))) continue;

			$addAction = $instance->i18n_singular_name();

			// Get description (convert 'Page' to 'SiteTree' for correct localization lookups)
			$description = _t((($class == 'Page') ? 'SiteTree' : $class) . '.DESCRIPTION');

			if(!$description) {
				$description = $instance->uninherited('description');
			}

			if($class == 'Page' && !$description) {
				$description = singleton('SiteTree')->uninherited('description');
			}

			$result->push(new ArrayData(array(
				'ClassName' => $class,
				'AddAction' => $addAction,
				'Description' => $description,
				// TODO Sprite support
				'IconURL' => $instance->stat('icon'),
				'Title' => singleton($class)->i18n_singular_name(),
			)));
		}

		$result = $result->sort('AddAction');

		return $result;
	}

	/**
	 * Get a database record to be managed by the CMS.
	 *
	 * @param int $id Record ID
	 * @param int $versionID optional Version id of the given record
	 * @return DataObject
	 */
 	public function getRecord($id, $versionID = null) {
		$treeClass = $this->stat('tree_class');

		if($id instanceof $treeClass) {
			return $id;
		}
		else if($id && is_numeric($id)) {
			$currentStage = Versioned::get_reading_mode();

			if($this->getRequest()->getVar('Version')) {
				$versionID = (int) $this->getRequest()->getVar('Version');
			}

			if($versionID) {
				$record = Versioned::get_version($treeClass, $id, $versionID);
			} else {
				$record = DataObject::get_by_id($treeClass, $id);
			}

			// Then, try getting a record from the live site
			if(!$record) {
				// $record = Versioned::get_one_by_stage($treeClass, "Live", "\"$treeClass\".\"ID\" = $id");
				Versioned::reading_stage('Live');
				singleton($treeClass)->flushCache();

				$record = DataObject::get_by_id($treeClass, $id);
			}

			// Then, try getting a deleted record
			if(!$record) {
				$record = Versioned::get_latest_version($treeClass, $id);
			}

			// Don't open a page from a different locale
			/** The record's Locale is saved in database in 2.4, and not related with Session,
			 *  we should not check their locale matches the Translatable::get_current_locale,
			 * 	here as long as we all the HTTPRequest is init with right locale.
			 *	This bit breaks the all FileIFrameField functions if the field is used in CMS
			 *  and its relevent ajax calles, like loading the tree dropdown for TreeSelectorField.
			 */
			/* if($record && SiteTree::has_extension('Translatable') && $record->Locale && $record->Locale != Translatable::get_current_locale()) {
				$record = null;
			}*/

			// Set the reading mode back to what it was.
			Versioned::set_reading_mode($currentStage);

			return $record;

		} else if(substr($id,0,3) == 'new') {
			return $this->getNewItem($id);
		}
	}

	/**
	 * @param int $id
	 * @param FieldList $fields
	 * @return CMSForm
	 */
	public function getEditForm($id = null, $fields = null) {

		if(!$id) $id = $this->currentPageID();
		$form = parent::getEditForm($id);

		// TODO Duplicate record fetching (see parent implementation)
		$record = $this->getRecord($id);
		if($record && !$record->canView()) return Security::permissionFailure($this);

		if(!$fields) $fields = $form->Fields();
		$actions = $form->Actions();

		if($record) {
			$deletedFromStage = $record->getIsDeletedFromStage();
			$deleteFromLive = !$record->getExistsOnLive();

			$fields->push($idField = new HiddenField("ID", false, $id));
			// Necessary for different subsites
			$fields->push($liveLinkField = new HiddenField("AbsoluteLink", false, $record->AbsoluteLink()));
			$fields->push($liveLinkField = new HiddenField("LiveLink"));
			$fields->push($stageLinkField = new HiddenField("StageLink"));
			$fields->push(new HiddenField("TreeTitle", false, $record->TreeTitle));

			if($record->ID && is_numeric( $record->ID ) ) {
				$liveLink = $record->getAbsoluteLiveLink();
				if($liveLink) $liveLinkField->setValue($liveLink);
				if(!$deletedFromStage) {
					$stageLink = Controller::join_links($record->AbsoluteLink(), '?stage=Stage');
					if($stageLink) $stageLinkField->setValue($stageLink);
				}
			}

			// Added in-line to the form, but plucked into different view by LeftAndMain.Preview.js upon load
			if(in_array('CMSPreviewable', class_implements($record)) && !$fields->fieldByName('SilverStripeNavigator')) {
				$navField = new LiteralField('SilverStripeNavigator', $this->getSilverStripeNavigator());
				$navField->setAllowHTML(true);
				$fields->push($navField);
			}

			// getAllCMSActions can be used to completely redefine the action list
			if($record->hasMethod('getAllCMSActions')) {
				$actions = $record->getAllCMSActions();
			} else {
				$actions = $record->getCMSActions();

				// Find and remove action menus that have no actions.
				if ($actions && $actions->Count()) {
					$tabset = $actions->fieldByName('ActionMenus');
					if ($tabset) {
						foreach ($tabset->getChildren() as $tab) {
							if (!$tab->getChildren()->count()) {
								$tabset->removeByName($tab->getName());
							}
						}
					}
				}
			}

			// Use <button> to allow full jQuery UI styling
			$actionsFlattened = $actions->dataFields();
			if($actionsFlattened) foreach($actionsFlattened as $action) $action->setUseButtonTag(true);

			if($record->hasMethod('getCMSValidator')) {
				$validator = $record->getCMSValidator();
			} else {
				$validator = new RequiredFields();
			}

			$form = CMSForm::create(
				$this, "EditForm", $fields, $actions, $validator
			)->setHTMLID('Form_EditForm');
			$form->setResponseNegotiator($this->getResponseNegotiator());
			$form->loadDataFrom($record);
			$form->disableDefaultAction();
			$form->addExtraClass('cms-edit-form');
			$form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
			// TODO Can't merge $FormAttributes in template at the moment
			$form->addExtraClass('center ' . $this->BaseCSSClasses());
			// if($form->Fields()->hasTabset()) $form->Fields()->findOrMakeTab('Root')->setTemplate('CMSTabSet');
			$form->setAttribute('data-pjax-fragment', 'CurrentForm');
			// Set validation exemptions for specific actions
			$form->setValidationExemptActions(array('restore', 'revert', 'deletefromlive', 'delete', 'unpublish', 'rollback', 'doRollback'));

			// Announce the capability so the frontend can decide whether to allow preview or not.
			if(in_array('CMSPreviewable', class_implements($record))) {
				$form->addExtraClass('cms-previewable');
			}

			if(!$record->canEdit() || $deletedFromStage) {
				$readonlyFields = $form->Fields()->makeReadonly();
				$form->setFields($readonlyFields);
			}

			$this->extend('updateEditForm', $form);
			return $form;
		} else if($id) {
			$form = CMSForm::create( $this, "EditForm", new FieldList(
				new LabelField('PageDoesntExistLabel',_t('CMSMain.PAGENOTEXISTS',"This page doesn't exist"))), new FieldList()
			)->setHTMLID('Form_EditForm');
			$form->setResponseNegotiator($this->getResponseNegotiator());
			return $form;
		}
	}

	/**
	 * @param SS_HTTPRequest $request
	 * @return string HTML
	 */
	public function treeview($request) {
		return $this->renderWith($this->getTemplatesWithSuffix('_TreeView'));
	}

	/**
	 * @param SS_HTTPRequest $request
	 * @return string HTML
	 */
	public function listview($request) {
		return $this->renderWith($this->getTemplatesWithSuffix('_ListView'));
	}

	/**
	 * Callback to request the list of page types allowed under a given page instance.
	 * Provides a slower but more precise response over SiteTreeHints
	 *
	 * @param SS_HTTPRequest $request
	 * @return SS_HTTPResponse
	 */
	public function childfilter($request) {
		// Check valid parent specified
		$parentID = $request->requestVar('ParentID');
		$parent = SiteTree::get()->byID($parentID);
		if(!$parent || !$parent->exists()) return $this->httpError(404);

		// Build hints specific to this class
		// Identify disallows and set globals
		$classes = SiteTree::page_type_classes();
		$disallowedChildren = array();
		foreach($classes as $class) {
			$obj = singleton($class);
			if($obj instanceof HiddenClass) continue;

			if(!$obj->canCreate(null, array('Parent' => $parent))) {
				$disallowedChildren[] = $class;
			}
		}

		$this->extend('updateChildFilter', $disallowedChildren, $parentID);
		return $this
			->getResponse()
			->addHeader('Content-Type', 'application/json; charset=utf-8')
			->setBody(Convert::raw2json($disallowedChildren));
	}

	/**
	 * Safely reconstruct a selected filter from a given set of query parameters
	 *
	 * @param array $params Query parameters to use
	 * @return CMSSiteTreeFilter The filter class, or null if none present
	 * @throws InvalidArgumentException if invalid filter class is passed.
	 */
	protected function getQueryFilter($params) {
		if(empty($params['FilterClass'])) return null;
		$filterClass = $params['FilterClass'];
		if(!is_subclass_of($filterClass, 'CMSSiteTreeFilter')) {
			throw new InvalidArgumentException("Invalid filter class passed: {$filterClass}");
		}
		return $filterClass::create($params);
	}

	/**
	 * Returns the pages meet a certain criteria as {@see CMSSiteTreeFilter} or the subpages of a parent page
	 * defaulting to no filter and show all pages in first level.
	 * Doubles as search results, if any search parameters are set through {@link SearchForm()}.
	 *
	 * @param array $params Search filter criteria
	 * @param int $parentID Optional parent node to filter on (can't be combined with other search criteria)
	 * @return SS_List
	 * @throws InvalidArgumentException if invalid filter class is passed.
	 */
	public function getList($params = array(), $parentID = 0) {
		if($filter = $this->getQueryFilter($params)) {
			return $filter->getFilteredPages();
		} else {
			$list = DataList::create($this->stat('tree_class'));
			$parentID = is_numeric($parentID) ? $parentID : 0;
			return $list->filter("ParentID", $parentID);
		}
	}

	public function ListViewForm() {
		$params = $this->getRequest()->requestVar('q');
		$list = $this->getList($params, $parentID = $this->getRequest()->requestVar('ParentID'));
		$gridFieldConfig = GridFieldConfig::create()->addComponents(
			new GridFieldSortableHeader(),
			new GridFieldDataColumns(),
			new GridFieldPaginator(self::config()->page_length)
		);
		if($parentID){
			$gridFieldConfig->addComponent(
				GridFieldLevelup::create($parentID)
					->setLinkSpec('?ParentID=%d&view=list')
					->setAttributes(array('data-pjax' => 'ListViewForm,Breadcrumbs'))
			);
		}
		$gridField = new GridField('Page','Pages', $list, $gridFieldConfig);
		$columns = $gridField->getConfig()->getComponentByType('GridFieldDataColumns');

		// Don't allow navigating into children nodes on filtered lists
		$fields = array(
			'getTreeTitle' => _t('SiteTree.PAGETITLE', 'Page Title'),
			'singular_name' => _t('SiteTree.PAGETYPE'),
			'LastEdited' => _t('SiteTree.LASTUPDATED', 'Last Updated'),
		);
		$gridField->getConfig()->getComponentByType('GridFieldSortableHeader')->setFieldSorting(array('getTreeTitle' => 'Title'));
		$gridField->getState()->ParentID = $parentID;

		if(!$params) {
			$fields = array_merge(array('listChildrenLink' => ''), $fields);
		}

		$columns->setDisplayFields($fields);
		$columns->setFieldCasting(array(
			'Created' => 'Datetime->Ago',
			'LastEdited' => 'Datetime->FormatFromSettings',
			'getTreeTitle' => 'HTMLText'
		));

		$controller = $this;
		$columns->setFieldFormatting(array(
			'listChildrenLink' => function($value, &$item) use($controller) {
				$num = $item ? $item->numChildren() : null;
				if($num) {
					return sprintf(
						'<a class="cms-panel-link list-children-link" data-pjax-target="ListViewForm,Breadcrumbs" href="%s">%s</a>',
						Controller::join_links(
							$controller->Link(),
							sprintf("?ParentID=%d&view=list", (int)$item->ID)
						),
						$num
					);
				}
			},
			'getTreeTitle' => function($value, &$item) use($controller) {
				return sprintf(
					'<a class="action-detail" href="%s">%s</a>',
					Controller::join_links(
						singleton('CMSPageEditController')->Link('show'),
						(int)$item->ID
					),
					$item->TreeTitle // returns HTML, does its own escaping
				);
			}
		));

		$listview = CMSForm::create(
			$this,
			'ListViewForm',
			new FieldList($gridField),
			new FieldList()
		)->setHTMLID('Form_ListViewForm');
		$listview->setAttribute('data-pjax-fragment', 'ListViewForm');
		$listview->setResponseNegotiator($this->getResponseNegotiator());

		$this->extend('updateListView', $listview);

		$listview->disableSecurityToken();
		return $listview;
	}

	public function currentPageID() {
		$id = parent::currentPageID();

		$this->extend('updateCurrentPageID', $id);

		return $id;
	}

	//------------------------------------------------------------------------------------------//
	// Data saving handlers

	/**
	 * Save and Publish page handler
	 */
	public function save($data, $form) {
		$className = $this->stat('tree_class');

		// Existing or new record?
		$id = $data['ID'];
		if(substr($id,0,3) != 'new') {
			$record = DataObject::get_by_id($className, $id);
			if($record && !$record->canEdit()) return Security::permissionFailure($this);
			if(!$record || !$record->ID) throw new SS_HTTPResponse_Exception("Bad record ID #$id", 404);
		} else {
			if(!singleton($this->stat('tree_class'))->canCreate()) return Security::permissionFailure($this);
			$record = $this->getNewItem($id, false);
		}

		// TODO Coupling to SiteTree
		$record->HasBrokenLink = 0;
		$record->HasBrokenFile = 0;

		if (!$record->ObsoleteClassName) $record->writeWithoutVersion();

		// Update the class instance if necessary
		if(isset($data['ClassName']) && $data['ClassName'] != $record->ClassName) {
			$newClassName = $record->ClassName;
			// The records originally saved attribute was overwritten by $form->saveInto($record) before.
			// This is necessary for newClassInstance() to work as expected, and trigger change detection
			// on the ClassName attribute
			$record->setClassName($data['ClassName']);
			// Replace $record with a new instance
			$record = $record->newClassInstance($newClassName);
		}

		// save form data into record
		$form->saveInto($record);
		$record->write();

		// If the 'Save & Publish' button was clicked, also publish the page
		if (isset($data['publish']) && $data['publish'] == 1) {
			$record->doPublish();
		}

		return $this->getResponseNegotiator()->respond($this->getRequest());
	}

	/**
	 * @uses LeftAndMainExtension->augmentNewSiteTreeItem()
	 */
	public function getNewItem($id, $setID = true) {
		$parentClass = $this->stat('tree_class');
		list($dummy, $className, $parentID, $suffix) = array_pad(explode('-',$id),4,null);

		if(!is_subclass_of($className, $parentClass) && strcasecmp($className, $parentClass) != 0) {
			$response = Security::permissionFailure($this);
			if (!$response) {
				$response = $this->getResponse();
			}
			throw new SS_HTTPResponse_Exception($response);
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

		$newItem->Title = _t(
			'CMSMain.NEWPAGE',
			"New {pagetype}",'followed by a page type title',
			array('pagetype' => singleton($className)->i18n_singular_name())
		);
		$newItem->ClassName = $className;
		$newItem->ParentID = $parentID;

		// DataObject::fieldExists only checks the current class, not the hierarchy
		// This allows the CMS to set the correct sort value
		if($newItem->castingHelper('Sort')) {
			$newItem->Sort = DB::prepared_query('SELECT MAX("Sort") FROM "SiteTree" WHERE "ParentID" = ?', array($parentID))->value() + 1;
		}

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
		if($record && !($record->canDelete() && $record->canDeleteFromLive())) return Security::permissionFailure($this);

		$descRemoved = '';
		$descendantsRemoved = 0;
		$recordTitle = $record->Title;
		$recordID = $record->ID;

		// before deleting the records, get the descendants of this tree
		if($record) {
			$descendantIDs = $record->getDescendantIDList();

			// then delete them from the live site too
			$descendantsRemoved = 0;
			foreach( $descendantIDs as $descID )
				if( $descendant = DataObject::get_by_id('SiteTree', $descID) ) {
					$descendant->doDeleteFromLive();
					$descendantsRemoved++;
				}

			// delete the record
			$record->doDeleteFromLive();
		}

		Versioned::reading_stage('Stage');

		if(isset($descendantsRemoved)) {
			$descRemoved = ' ' . _t(
				'CMSMain.DESCREMOVED',
				'and {count} descendants',
				array('count' => $descendantsRemoved)
			);
		} else {
			$descRemoved = '';
		}

		$this->getResponse()->addHeader(
			'X-Status',
			rawurlencode(
				_t(
					'CMSMain.REMOVED',
					'Deleted \'{title}\'{description} from live site',
					array('title' => $recordTitle, 'description' => $descRemoved)
				)
			)
		);

		// Even if the record has been deleted from stage and live, it can be viewed in "archive mode"
		return $this->getResponseNegotiator()->respond($this->getRequest());
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
		if(!isset($data['ID'])) return new SS_HTTPResponse("Please pass an ID in the form content", 400);

		$id = (int) $data['ID'];
		$restoredPage = Versioned::get_latest_version("SiteTree", $id);
		if(!$restoredPage) 	return new SS_HTTPResponse("SiteTree #$id not found", 400);

		$record = Versioned::get_one_by_stage('SiteTree', 'Live', array(
			'"SiteTree_Live"."ID"' => $id
		));

		// a user can restore a page without publication rights, as it just adds a new draft state
		// (this action should just be available when page has been "deleted from draft")
		if($record && !$record->canEdit()) return Security::permissionFailure($this);
		if(!$record || !$record->ID) throw new SS_HTTPResponse_Exception("Bad record ID #$id", 404);

		$record->doRevertToLive();

		$this->getResponse()->addHeader(
			'X-Status',
			rawurlencode(_t(
				'CMSMain.RESTORED',
				"Restored '{title}' successfully",
				'Param %s is a title',
				array('title' => $record->Title)
			))
		);

		return $this->getResponseNegotiator()->respond($this->getRequest());
	}

	/**
	 * Delete the current page from draft stage.
	 * @see deletefromlive()
	 */
	public function delete($data, $form) {
		Deprecation::notice('4.0', 'Delete from stage is deprecated. Use archive instead');
		$id = $data['ID'];
		$record = DataObject::get_by_id("SiteTree", $id);
		if($record && !$record->canDelete()) return Security::permissionFailure();
		if(!$record || !$record->ID) throw new SS_HTTPResponse_Exception("Bad record ID #$id", 404);

		// Delete record
		$record->delete();

		$this->getResponse()->addHeader(
			'X-Status',
			rawurlencode(sprintf(_t('CMSMain.REMOVEDPAGEFROMDRAFT',"Removed '%s' from the draft site"), $record->Title))
		);

		// Even if the record has been deleted from stage and live, it can be viewed in "archive mode"
		return $this->getResponseNegotiator()->respond($this->getRequest());
	}

	/**
	 * Delete this page from both live and stage
	 *
	 * @param type $data
	 * @param type $form
	 */
	public function archive($data, $form) {
		$id = $data['ID'];
		$record = DataObject::get_by_id("SiteTree", $id);
		if(!$record || !$record->exists()) {
			throw new SS_HTTPResponse_Exception("Bad record ID #$id", 404);
		}
		if(!$record->canArchive()) {
			return Security::permissionFailure();
		}

		// Archive record
		$record->doArchive();

		$this->getResponse()->addHeader(
			'X-Status',
			rawurlencode(sprintf(_t('CMSMain.ARCHIVEDPAGE',"Archived page '%s'"), $record->Title))
		);

		// Even if the record has been deleted from stage and live, it can be viewed in "archive mode"
		return $this->getResponseNegotiator()->respond($this->getRequest());
	}

	public function publish($data, $form) {
		$data['publish'] = '1';

		return $this->save($data, $form);
	}

	public function unpublish($data, $form) {
		$className = $this->stat('tree_class');
		$record = DataObject::get_by_id($className, $data['ID']);

		if($record && !$record->canDeleteFromLive()) return Security::permissionFailure($this);
		if(!$record || !$record->ID) throw new SS_HTTPResponse_Exception("Bad record ID #" . (int)$data['ID'], 404);

		$record->doUnpublish();

		$this->getResponse()->addHeader(
			'X-Status',
			rawurlencode(_t('CMSMain.REMOVEDPAGE',"Removed '{title}' from the published site", array('title' => $record->Title)))
		);

		return $this->getResponseNegotiator()->respond($this->getRequest());
	}

	/**
	 * @return array
	 */
	public function rollback() {
		return $this->doRollback(array(
			'ID' => $this->currentPageID(),
			'Version' => $this->getRequest()->param('VersionID')
		), null);
	}

	/**
	 * Rolls a site back to a given version ID
	 *
	 * @param array
	 * @param Form
	 *
	 * @return html
	 */
	public function doRollback($data, $form) {
		$this->extend('onBeforeRollback', $data['ID']);

		$id = (isset($data['ID'])) ? (int) $data['ID'] : null;
		$version = (isset($data['Version'])) ? (int) $data['Version'] : null;

		$record = DataObject::get_by_id($this->stat('tree_class'), $id);
		if($record && !$record->canEdit()) return Security::permissionFailure($this);

		if($version) {
			$record->doRollbackTo($version);
			$message = _t(
				'CMSMain.ROLLEDBACKVERSIONv2',
				"Rolled back to version #%d.",
				array('version' => $data['Version'])
			);
		} else {
			$record->doRollbackTo('Live');
			$message = _t(
				'CMSMain.ROLLEDBACKPUBv2',"Rolled back to published version."
			);
		}

		$this->getResponse()->addHeader('X-Status', rawurlencode($message));

		// Can be used in different contexts: In normal page edit view, in which case the redirect won't have any effect.
		// Or in history view, in which case a revert causes the CMS to re-load the edit view.
		// The X-Pjax header forces a "full" content refresh on redirect.
		$url = Controller::join_links(singleton('CMSPageEditController')->Link('show'), $record->ID);
		$this->getResponse()->addHeader('X-ControllerURL', $url);
		$this->getRequest()->addHeader('X-Pjax', 'Content');
		$this->getResponse()->addHeader('X-Pjax', 'Content');

		return $this->getResponseNegotiator()->respond($this->getRequest());
	}

	/**
	 * Batch Actions Handler
	 */
	public function batchactions() {
		return new CMSBatchActionHandler($this, 'batchactions');
	}

	public function BatchActionParameters() {
		$batchActions = CMSBatchActionHandler::config()->batch_actions;

		$forms = array();
		foreach($batchActions as $urlSegment => $batchAction) {
			$SNG_action = singleton($batchAction);
			if ($SNG_action->canView() && $fieldset = $SNG_action->getParameterFields()) {
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
	public function BatchActionList() {
		return $this->batchactions()->batchActionList();
	}

	public function buildbrokenlinks($request) {
		// Protect against CSRF on destructive action
		if(!SecurityToken::inst()->checkRequest($request)) return $this->httpError(400);

		increase_time_limit_to();
		increase_memory_limit_to();

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

	public function publishall($request) {
		if(!Permission::check('ADMIN')) return Security::permissionFailure($this);

		increase_time_limit_to();
		increase_memory_limit_to();

		$response = "";

		if(isset($this->requestParams['confirm'])) {
			// Protect against CSRF on destructive action
			if(!SecurityToken::inst()->checkRequest($request)) return $this->httpError(400);

			$start = 0;
			$pages = DataObject::get("SiteTree", "", "", "", "$start,30");
			$count = 0;
			while($pages) {
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
			$response .= _t('CMSMain.PUBPAGES',"Done: Published {count} pages", array('count' => $count));

		} else {
			$token = SecurityToken::inst();
			$fields = new FieldList();
			$token->updateFieldSet($fields);
			$tokenField = $fields->First();
			$tokenHtml = ($tokenField) ? $tokenField->FieldHolder() : '';
			$publishAllDescription = _t(
				'CMSMain.PUBALLFUN2',
				'Pressing this button will do the equivalent of going to every page and pressing "publish".  '
				. 'It\'s intended to be used after there have been massive edits of the content, such as when '
				. 'the site was first built.'
			);
			$response .= '<h1>' . _t('CMSMain.PUBALLFUN','"Publish All" functionality') . '</h1>
				<p>' . $publishAllDescription . '</p>
				<form method="post" action="publishall">
					<input type="submit" name="confirm" value="'
					. _t('CMSMain.PUBALLCONFIRM',"Please publish every page in the site, copying content stage to live",'Confirmation button') .'" />'
					. $tokenHtml .
				'</form>';
		}

		return $response;
	}

	/**
	 * Restore a completely deleted page from the SiteTree_versions table.
	 */
	public function restore($data, $form) {
		if(!isset($data['ID']) || !is_numeric($data['ID'])) {
			return new SS_HTTPResponse("Please pass an ID in the form content", 400);
		}

		$id = (int)$data['ID'];
		$restoredPage = Versioned::get_latest_version("SiteTree", $id);
		if(!$restoredPage) 	return new SS_HTTPResponse("SiteTree #$id not found", 400);

		$restoredPage = $restoredPage->doRestoreToStage();

		$this->getResponse()->addHeader(
			'X-Status',
			rawurlencode(_t(
				'CMSMain.RESTORED',
				"Restored '{title}' successfully",
				array('title' => $restoredPage->Title)
			))
		);

		return $this->getResponseNegotiator()->respond($this->getRequest());
	}

	public function duplicate($request) {
		// Protect against CSRF on destructive action
		if(!SecurityToken::inst()->checkRequest($request)) return $this->httpError(400);

		if(($id = $this->urlParams['ID']) && is_numeric($id)) {
			$page = DataObject::get_by_id("SiteTree", $id);
			if($page && (!$page->canEdit() || !$page->canCreate(null, array('Parent' => $page->Parent())))) {
				return Security::permissionFailure($this);
			}
			if(!$page || !$page->ID) throw new SS_HTTPResponse_Exception("Bad record ID #$id", 404);

			$newPage = $page->duplicate();

			// ParentID can be hard-set in the URL.  This is useful for pages with multiple parents
			if(isset($_GET['parentID']) && is_numeric($_GET['parentID'])) {
				$newPage->ParentID = $_GET['parentID'];
				$newPage->write();
			}

			$this->getResponse()->addHeader(
				'X-Status',
				rawurlencode(_t(
					'CMSMain.DUPLICATED',
					"Duplicated '{title}' successfully",
					array('title' => $newPage->Title)
				))
			);
			$url = Controller::join_links(singleton('CMSPageEditController')->Link('show'), $newPage->ID);
			$this->getResponse()->addHeader('X-ControllerURL', $url);
			$this->getRequest()->addHeader('X-Pjax', 'Content');
			$this->getResponse()->addHeader('X-Pjax', 'Content');

			return $this->getResponseNegotiator()->respond($this->getRequest());
		} else {
			return new SS_HTTPResponse("CMSMain::duplicate() Bad ID: '$id'", 400);
		}
	}

	public function duplicatewithchildren($request) {
		// Protect against CSRF on destructive action
		if(!SecurityToken::inst()->checkRequest($request)) return $this->httpError(400);
		increase_time_limit_to();
		if(($id = $this->urlParams['ID']) && is_numeric($id)) {
			$page = DataObject::get_by_id("SiteTree", $id);
			if($page && (!$page->canEdit() || !$page->canCreate(null, array('Parent' => $page->Parent())))) {
				return Security::permissionFailure($this);
			}
			if(!$page || !$page->ID) throw new SS_HTTPResponse_Exception("Bad record ID #$id", 404);

			$newPage = $page->duplicateWithChildren();

			$this->getResponse()->addHeader(
				'X-Status',
				rawurlencode(_t(
					'CMSMain.DUPLICATEDWITHCHILDREN',
					"Duplicated '{title}' and children successfully",
					array('title' => $newPage->Title)
				))
			);
			$url = Controller::join_links(singleton('CMSPageEditController')->Link('show'), $newPage->ID);
			$this->getResponse()->addHeader('X-ControllerURL', $url);
			$this->getRequest()->addHeader('X-Pjax', 'Content');
			$this->getResponse()->addHeader('X-Pjax', 'Content');

			return $this->getResponseNegotiator()->respond($this->getRequest());
		} else {
			return new SS_HTTPResponse("CMSMain::duplicatewithchildren() Bad ID: '$id'", 400);
		}
	}

	public function providePermissions() {
		$title = _t("CMSPagesController.MENUTITLE", LeftAndMain::menu_title_for_class('CMSPagesController'));
		return array(
			"CMS_ACCESS_CMSMain" => array(
				'name' => _t('CMSMain.ACCESS', "Access to '{title}' section", array('title' => $title)),
				'category' => _t('Permission.CMS_ACCESS_CATEGORY', 'CMS Access'),
				'help' => _t(
					'CMSMain.ACCESS_HELP',
					'Allow viewing of the section containing page tree and content. View and edit permissions can be handled through page specific dropdowns, as well as the separate "Content permissions".'
				),
				'sort' => -99 // below "CMS_ACCESS_LeftAndMain", but above everything else
			)
		);
	}

}
