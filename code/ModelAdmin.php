<?php
/**
 * Generates a three-pane UI for editing model classes,
 * with an automatically generated search panel, tabular results
 * and edit forms.
 * Relies on data such as {@link DataObject::$db} and {@DataObject::getCMSFields()}
 * to scaffold interfaces "out of the box", while at the same time providing
 * flexibility to customize the default output.
 * 
 * Add a route (note - this doc is not currently in sync with the code, need to update)
 * <code>
 * Director::addRules(50, array('admin/mymodel/$Class/$Action/$ID' => 'MyModelAdmin'));
 * </code>
 *
 * @todo saving logic (should mostly use Form->saveInto() and iterate over relations)
 * @todo ajax form loading and saving
 * @todo ajax result display
 * @todo relation formfield scaffolding (one tab per relation) - relations don't have DBField sublclasses, we do
 * 	we define the scaffold defaults. can be ComplexTableField instances for a start. 
 * @todo has_many/many_many relation autocomplete field (HasManyComplexTableField doesn't work well with larger datasets)
 * 
 * Long term TODOs:
 * @todo Hook into RESTful interface on DataObjects (yet to be developed)
 * @todo Permission control via datamodel and Form class
 * 
 * @uses {@link SearchContext}
 * 
 * @package cms
 */
abstract class ModelAdmin extends LeftAndMain {
	
	/**
	 * List of all managed {@link DataObject}s in this interface.
	 *
	 * @var array
	 */
	protected static $managed_models = null;
	
	public static $allowed_actions = array(
		'add',
		'edit',
		'delete',
		'import',
		'renderimportform',
		'handleList',
		'handleItem',
		'ImportForm'
	);
	
	/**
	 * @param string $collection_controller_class Override for controller class
	 */
	protected static $collection_controller_class = "ModelAdmin_CollectionController";
	
	/**
	 * @param string $collection_controller_class Override for controller class
	 */
	protected static $record_controller_class = "ModelAdmin_RecordController";
	
	/**
	 * Forward control to the default action handler
	 */
	public static $url_handlers = array(
		'$Action' => 'handleAction'
	);
	
	/**
	 * Model object currently in manipulation queue. Used for updating Link to point
	 * to the correct generic data object in generated URLs.
	 *
	 * @var string
	 */
	private $currentModel = false;
		
	/**
	 * List of all {@link DataObject}s which can be imported through
	 * a subclass of {@link BulkLoader} (mostly CSV data).
	 * By default {@link CsvBulkLoader} is used, assuming a standard mapping
	 * of column names to {@link DataObject} properties/relations.
	 *
	 * @var array
	 */
	protected static $model_importers = null;
	
	/**
	 * Amount of results showing on a single page.
	 *
	 * @var int
	 */
	protected static $page_length = 30;
	
	/**
	 * Initialize the model admin interface. Sets up embedded jquery libraries and requisite plugins.
	 * 
	 * @todo remove reliance on urlParams
	 */
	public function init() {
		parent::init();
		
		// security check for valid models
		if(isset($this->urlParams['Action']) && !in_array($this->urlParams['Action'], $this->getManagedModels())) {
			//user_error('ModelAdmin::init(): Invalid Model class', E_USER_ERROR);
		}
		
		Requirements::css('cms/css/ModelAdmin.css'); // standard layout formatting for management UI
		Requirements::css('cms/css/silverstripe.tabs.css'); // follows the jQuery UI theme conventions
		
		Requirements::javascript('jsparty/jquery/jquery.js');
		Requirements::javascript('jsparty/jquery/plugins/livequery/jquery.livequery.js');
		Requirements::javascript('jsparty/jquery/ui/ui.core.js');
		Requirements::javascript('jsparty/jquery/ui/ui.tabs.js');
		Requirements::javascript('jsparty/jquery/plugins/form/jquery.form.js');
		Requirements::javascript('jsparty/jquery/jquery_improvement.js');
		Requirements::javascript('cms/javascript/ModelAdmin.js');
	}
	
	/**
	 * overwrite the static page_length of the admin panel, 
	 * should be called in the project _config file.
	 */
	static function set_page_length($length){
		self::$page_length = $length;
	}
	
	/**
	 * Return the static page_length of the admin, default as 30
	 */
	static function get_page_length(){
		return self::$page_length;
	} 
	/**
	 * Add mappings for generic form constructors to automatically delegate to a scaffolded form object.
	 */
	function defineMethods() {
		parent::defineMethods();
		foreach($this->getManagedModels() as $ClassName) {
			$this->addWrapperMethod($ClassName, 'bindModelController');
		}
	}
	
	/**
	 * Return default link to this controller. This assfaced method is abstract, so we can't
	 * get rid of it.
	 *
	 * @todo extract full URL binding from Director::addRules so that it's not tied to 'admin/crm'
	 * @todo is there a way of removing the dependency on this method from the Form?
	 * 
	 * @return string
	 */
	public function Link() {
		return Controller::join_links(Director::absoluteBaseURL(), 'admin/crm/');
	}
	
	/**
	 * Base scaffolding method for returning a generic model instance.
	 */
	public function bindModelController($model, $request = null) {
		$class = $this->stat('collection_controller_class');
		return new $class($this, $model);
	}
	
	/**
	 * Allows to choose which record needs to be created.
	 * 
	 * @return Form
	 */
	protected function ManagedModelsSelect() {
		$models = $this->getManagedModels();
		$modelMap = array();
		foreach($models as $modelName) {
			if(singleton($modelName)->canCreate(Member::currentUser())) $modelMap[$modelName] = singleton($modelName)->singular_name();
		}
		
		$form = new Form(
			$this,
			"ManagedModelsSelect",
			new FieldSet(
				new DropdownField('ClassName', 'Type', $modelMap)
			),
			new FieldSet(
				new FormAction('add', _t('GenericDataAdmin.CREATE'))
			)
		);
		$form->setFormMethod('get');
		return $form;
	}
	
	/**
	 * Generate a CSV import form with an option to select
	 * one of the "importable" models specified through {@link self::$model_importers}.
	 *
	 * @return Form
	 */	
	public function ImportForm() {
		$models = $this->getManagedModels();
		$modelMap = array();
		$importers = $this->getModelImporters();
		if(!$importers) return false;

		foreach($importers as $modelName => $importerClass) {
			$modelMap[$modelName] = singleton($modelName)->singular_name();
		}
		
		$fields = new FieldSet(
			new DropdownField('ClassName', 'Type', $modelMap),
			new FileField('_CsvFile', false)
		);
		
		// get HTML specification for each import (column names etc.)
		foreach($importers as $modelName => $importerClass) {
			$importer = new $importerClass($modelName);
			$spec = $importer->getImportSpec();
			$specFields = new DataObjectSet();
			foreach($spec['fields'] as $name => $desc) {
				$specFields->push(new ArrayData(array('Name' => $name, 'Description' => $desc)));
			}
			$specRelations = new DataObjectSet();
			foreach($spec['relations'] as $name => $desc) {
				$specRelations->push(new ArrayData(array('Name' => $name, 'Description' => $desc)));
			}
			$specHTML = $this->customise(array(
				'ModelName' => $modelName, 
				'Fields' => $specFields,
				'Relations' => $specRelations, 
			))->renderWith('ModelAdmin_ImportSpec');
			
			$fields->push(new LiteralField("SpecFor{$modelName}", $specHTML));
		}
		
		$actions = new FieldSet(
			new FormAction('import', _t('ModelAdmin.IMPORT', 'Import from CSV'))
		);
		
		$form = new Form(
			$this,
			"ImportForm",
			$fields,
			$actions
		);
		return $form;
	}
	
	function add($data, $form, $request) {
		$className = $request->requestVar("ClassName");
		return $this->$className()->add($request);
	}
	
	/**
	 * Imports the submitted CSV file based on specifications given in
	 * {@link self::model_importers}.
	 * Redirects back with a success/failure message.
	 * 
	 * @todo Figure out ajax submission of files via jQuery.form plugin
	 *
	 * @param unknown_type $data
	 * @param unknown_type $form
	 * @param unknown_type $request
	 */
	function import($data, $form, $request) {
		$importers = $this->getModelImporters();
		$importerClass = $importers[$data['ClassName']];
		
		$loader = new $importerClass($data['ClassName']);
		$results = $loader->load($_FILES['_CsvFile']['tmp_name']);
		$resultsCount = ($results) ? $results->Count() : 0;
		
		Session::setFormMessage('Form_ImportForm', "Loaded {$resultsCount} items", 'good');
		Director::redirect($_SERVER['HTTP_REFERER'] . '#Form_ImportForm_holder');
	}
	
	/**
	 * 
	 * @uses {@link SearchContext}
	 * @uses {@link SearchFilter}
	 * @return Form
	 */
	protected function getSearchForms() {
		$modelClasses = $this->getManagedModels();
		
		$forms = new DataObjectSet();
		foreach($modelClasses as $modelClass) {
			$this->$modelClass()->SearchForm();

			$forms->push(new ArrayData(array(
				'Form' => $this->$modelClass()->SearchForm(),
				'Title' => singleton($modelClass)->singular_name(),
				'ClassName' => $modelClass,
			)));
		}
		
		return $forms;
	}
	
	/**
	 * @return array
	 */
	protected function getManagedModels() {
		$models = $this->stat('managed_models');
		if(!count($models)) user_error('ModelAdmin::getManagedModels(): 
			You need to specify at least one DataObject subclass in $managed_models', E_USER_ERROR);
		
		return $models;
	}
	
	/**
	 * Returns all importers defined in {@link self::$model_importers}.
	 * If none are defined, we fall back to {@link self::managed_models}
	 * with a default {@link CsvBulkLoader} class. In this case the column names of the first row
	 * in the CSV file are assumed to have direct mappings to properties on the object.
	 *
	 * @return array
	 */
	protected function getModelImporters() {
		$importers = $this->stat('model_importers');

		// fallback to all defined models if not explicitly defined
		if(is_null($importers)) {
			$models = $this->getManagedModels();
			foreach($models as $modelName) $importers[$modelName] = 'CsvBulkLoader';
		}
		
		return $importers;
	}
}

/**
 * Handles a managed model class and provides default collection filtering behavior.
 *
 */
class ModelAdmin_CollectionController extends Controller {
	protected $parentController;
	protected $modelClass;
	
	static $url_handlers = array(
		'$Action' => 'handleActionOrID'
	);

	function __construct($parent, $model) {
		$this->parentController = $parent;
		$this->modelClass = $model;
	}
	
	/**
	 * Appends the model class to the URL.
	 *
	 * @return unknown
	 */
	function Link() {
		return Controller::join_links($this->parentController->Link(), "$this->modelClass");
	}
	
	/**
	 * Return the class name of the model being managed.
	 *
	 * @return unknown
	 */
	function getModelClass() {
		return $this->modelClass;
	}
		
	/**
	 * Delegate to different control flow, depending on whether the
	 * URL parameter is a number (record id) or string (action).
	 * 
	 * @param unknown_type $request
	 * @return unknown
	 */
	function handleActionOrID($request) {
		if (is_numeric($request->param('Action'))) {
			return $this->handleID($request);
		} else {
			return $this->handleAction($request);
		}
	}
	
	/**
	 * Delegate to the RecordController if a valid numeric ID appears in the URL
	 * segment.
	 *
	 * @param HTTPRequest $request
	 * @return RecordController
	 */
	function handleID($request) {
		$class = $this->parentController->stat('record_controller_class');
		return new $class($this, $request);
	}

	/////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Get a search form for a single {@link DataObject} subclass.
	 * 
	 * @return Form
	 */
	public function SearchForm() {
		$context = singleton($this->modelClass)->getDefaultSearchContext();
		$fields = $context->getSearchFields();
		$columnSelectionField = $this->ColumnSelectionField();
		$fields->push($columnSelectionField);
		
		$form = new Form($this, "SearchForm",
			$fields,
			new FieldSet(
				new FormAction('search', _t('MemberTableField.SEARCH'))
			)
		);
		$form->setFormAction(Controller::join_links($this->Link(), "search"));
		$form->setFormMethod('get');
		$form->setHTMLID("Form_SearchForm_" . $this->modelClass);
		return $form;
	}
	
	/**
	 * Give the flexibilility to show variouse combination of columns in the search result table
	 */
	public function ColumnSelectionField() {
		$model = singleton($this->modelClass);
		
		$source = $model->summaryFields();
		foreach ($source as $fieldName => $label){
			$value[] = $fieldName;
		}
		$checkboxes = new CheckboxSetField("ResultAssembly", "Tick the box if you want it to be shown in the results", $source, $value);
		
		$field = new CompositeField(
			new LiteralField("ToggleResultAssemblyLink", "<a class=\"form_frontend_function toggle_result_assembly\" href=\"#\">+ choose columns</a>"),
			$checkboxesBlock = new CompositeField(
				$checkboxes,
				new LiteralField("ClearDiv", "<div class=\"clear\"></div>"),
				new LiteralField("TickAllAssemblyLink","<a class=\"form_frontend_function tick_all_result_assembly\" href=\"#\">select all</a>"),
				new LiteralField("UntickAllAssemblyLink","<a class=\"form_frontend_function untick_all_result_assembly\" href=\"#\">select none</a>")
			)
		);
		
		$field -> setExtraClass("ResultAssemblyBlock");
		$checkboxesBlock -> setExtraClass("hidden");
		return $field;
	}
	
	/**
	 * Action to render a data object collection, using the model context to provide filters
	 * and paging.
	 * 
	 * @return string
	 */
	function search($request) {
		$form = $this->ResultsForm();

		return $form->forTemplate();
	}
	
	/**
	 * Gets the search query generated on the SearchContext from
	 * {@link DataObject::getDefaultSearchContext()},
	 * and the current GET parameters on the request.
	 *
	 * @return SQLQuery
	 */
	function getSearchQuery() {
		$context = singleton($this->modelClass)->getDefaultSearchContext();
		
		return $context->getQuery($this->request->getVars());
	}
	
	/**
	 * Shows results from the "search" action in a TableListField.
	 *
	 * @return Form
	 */
	function ResultsForm() {
		$model = singleton($this->modelClass);
		$summaryFields = $model->summaryFields();
		$resultAssembly = $_REQUEST['ResultAssembly'];
		foreach($summaryFields as $fieldname=>$label){
			if(!$resultAssembly[$fieldname]){
				unset($summaryFields[$fieldname]);
			}
		}
		$tf = new TableListField(
			$this->modelClass,
			$this->modelClass,
			$summaryFields
		);
		$tf->setCustomQuery($this->getSearchQuery());
		$tf->setPageSize($this->parentController->stat('page_length'));
		$tf->setShowPagination(true);
		$tf->setPermissions(array_merge(array('view'), $model->stat('results_permissions')));
		$url = '<a href=\"' . $this->Link() . '/$ID/edit\">$value</a>';
		$tf->setFieldFormatting(array_combine(array_keys($summaryFields), array_fill(0,count($summaryFields), $url)));

		// implemented as a form to enable further actions on the resultset
		// (serverside sorting, export as CSV, etc)
		$form = new Form(
			$this,
			'ResultsForm',
			new FieldSet(
				new HeaderField(_t('ModelAdmin.SEARCHRESULTS','Search Results'), 2),
				$tf
			),
			new FieldSet()
		);
		
		// HACK to preserve search parameters on TableField
		// ajax actions like pagination
		$filteredParams = $this->request->getVars();
		unset($filteredParams['ctf']);
		unset($filteredParams['url']);
		unset($filteredParams['action_search']);
		$tf->setExtraLinkParams($filteredParams);
		
		return $form;
	}
	

	/////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Create a new model record.
	 *
	 * @param unknown_type $request
	 * @return unknown
	 */
	function add($request) {
		return $this->AddForm()->forAjaxTemplate();
	}

	/**
	 * Returns a form for editing the attached model
	 */
	public function AddForm() {
		$newRecord = new $this->modelClass();
		$fields = $newRecord->getCMSFields();
		
		$validator = ($newRecord->hasMethod('getCMSValidator')) ? $newRecord->getCMSValidator() : null;
		
		$actions = new FieldSet(new FormAction("doCreate", "Add"));
		
		$form = new Form($this, "AddForm", $fields, $actions, $validator);

		return $form;
	}	
	
	function doCreate($data, $form, $request) {
		$className = $this->getModelClass();
		$model = new $className();
		$form->saveInto($model);
		$model->write();
		
		Director::redirect(Controller::join_links($this->Link(), $model->ID , 'edit'));
	}
}

/**
 * Handles operations on a single record from a managed model.
 * 
 * @todo change the parent controller varname to indicate the model scaffolding functionality in ModelAdmin
 */
class ModelAdmin_RecordController extends Controller {
	protected $parentController;
	protected $currentRecord;
	
	static $allowed_actions = array('edit', 'view', 'EditForm', 'ViewForm');
	
	function __construct($parentController, $request) {
		$this->parentController = $parentController;
		$modelName = $parentController->getModelClass();
		$recordID = $request->param('Action');
		$this->currentRecord = DataObject::get_by_id($modelName, $recordID);
	}
	
	/**
	 * Link fragment - appends the current record ID to the URL.
	 *
	 */
	function Link() {
		return Controller::join_links($this->parentController->Link(), "/{$this->currentRecord->ID}");
	}

	/////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Edit action - shows a form for editing this record
	 */
	function edit($request) {
		if ($this->currentRecord) {
			return $this->EditForm()->forAjaxTemplate();
		} else {
			return "I can't find that item";
		}
	}

	/**
	 * Returns a form for editing the attached model
	 */
	public function EditForm() {
		$fields = $this->currentRecord->getCMSFields();
		$fields->push(new HiddenField("ID"));
		
		$validator = ($this->currentRecord->hasMethod('getCMSValidator')) ? $this->currentRecord->getCMSValidator() : null;
		
		$actions = new FieldSet(
			//new FormAction("goBack", "Back"),
			new FormAction("doSave", "Save")
		);
		
		$form = new Form($this, "EditForm", $fields, $actions, $validator);
		$form->loadDataFrom($this->currentRecord);

		return $form;
	}

	/**
	 * Postback action to save a record
	 *
	 * @param array $data
	 * @param Form $form
	 * @param HTTPRequest $request
	 * @return mixed
	 */
	function doSave($data, $form, $request) {
		$form->saveInto($this->currentRecord);
		$this->currentRecord->write();
		
		// Behaviour switched on ajax.
		if(Director::is_ajax()) {
			return $this->edit($request);
		} else {
			Director::redirectBack();
		}
	}	

	/////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Renders the record view template.
	 * 
	 * @param HTTPRequest $request
	 * @return mixed
	 */
	function view($request) {
		if ($this->currentRecord) {
			$form = $this->ViewForm();
			return $form->forAjaxTemplate();
		} else {
			return "I can't find that item";
		}
	}

	/**
	 * Returns a form for viewing the attached model
	 * 
	 * @return Form
	 */
	public function ViewForm() {
		$fields = $this->currentRecord->getCMSFields();
		$form = new Form($this, "EditForm", $fields, new FieldSet());
		$form->loadDataFrom($this->currentRecord);
		$form->makeReadonly();
		return $form;
	}
	
	/////////////////////////////////////////////////////////////////////////////////////////////////////////

	function index() {
		Director::redirect(Controller::join_links($this->Link(), 'edit'));
	}
	
}

?>