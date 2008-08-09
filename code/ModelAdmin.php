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
		'delete'
	);
	
	/**
	 * Model object currently in manipulation queue. Used for updating Link to point
	 * to the correct generic data object in generated URLs.
	 *
	 * @var string
	 */
	private $currentModel = false;
	
	/**
	 * Initialize the model admin interface. Sets up embedded jquery libraries and requisite plugins
	 */
	public function init() {
		parent::init();
		
		// security check for valid models
		if(isset($this->urlParams['Model']) && !in_array($this->urlParams['Model'], $this->getManagedModels())) {
			user_error('ModelAdmin::init(): Invalid Model class', E_USER_ERROR);
		}
		
		Requirements::css('cms/css/ModelAdmin.css'); // standard layout formatting for management UI
		Requirements::css('cms/css/silverstripe.tabs.css'); // follows the jQuery UI theme conventions
		
		Requirements::javascript('jsparty/jquery/jquery.js');
		Requirements::javascript('jsparty/jquery/livequery/jquery.livequery.js');
		Requirements::javascript('jsparty/jquery/ui/ui/ui.base.js');
		Requirements::javascript('jsparty/jquery/ui/ui/ui.tabs.js');
		Requirements::javascript('jsparty/jquery/ui/plugins/form/jquery.form.js');
		Requirements::javascript('cms/javascript/ModelAdmin.js');
	}
	
	/**
	 * Add mappings for generic form constructors to automatically delegate to a scaffolded form object.
	 */
	function defineMethods() {
		parent::defineMethods();
		foreach($this->getManagedModels() as $class) {
			$this->addWrapperMethod('SearchForm_' . $class, 'getSearchFormForModel');
			$this->addWrapperMethod('AddForm_' . $class, 'getAddFormForModel');
			$this->addWrapperMethod('EditForm_' . $class, 'getEditFormForModel');
		}
	}	
	
	/**
	 * Return default link to this controller
	 *
	 * @todo extract full URL binding from Director::addRules so that it's not tied to 'admin/crm'
	 * @todo is there a way of removing the dependency on this method from the Form?
	 * 
	 * @return string
	 */
	public function Link() {
		return 'admin/crm/' . implode('/', array_values(array_unique($this->urlParams)));
	}
	
	/**
	 * Create empty edit form (scaffolded from DataObject->getCMSFields() by default).
	 * Can be called either through {@link AddForm()} or directly through URL:
	 * "/myadminroute/add/MyModelClass"
	 */
	public function add($data) {
		$className = (isset($data['ClassName'])) ? $data['ClassName'] : $this->urlParams['ClassName'];
		
		if(!isset($className) || !in_array($data['ClassName'], $this->getManagedModels())) return false;

		$form = $this->getEditForm($data['ClassName']);
		return $form->forTemplate();
	}
	
	/**
	 * Edit forms (scaffolded from DataObject->getCMSFields() by default).
	 *
	 * @param array $data
	 * @param Form $form
	 */
	public function edit($data, $form) {
		if(!isset($data['ClassName']) || !in_array($data['ClassName'], $this->getManagedModels())) return false;
		
		// @todo generate editform
	}
	
	/**
	 *
	 * @param array $data
	 */
	public function save($data, $form) {
		Debug::dump($data);
	}
	
	/**
	 * Allows to choose which record needs to be created.
	 * 
	 * @return Form
	 */
	protected function AddForm() {
		$models = $this->getManagedModels();
		$modelMap = array();
		foreach($models as $modelName) $modelMap[$modelName] = singleton($modelName)->singular_name();
		
		$form = new Form(
			$this,
			"AddForm",
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
	 * 
	 * @uses {@link SearchContext}
	 * @uses {@link SearchFilter}
	 * @return Form
	 */
	protected function getSearchForms() {
		$modelClasses = $this->getManagedModels();
		
		$forms = new DataObjectSet();
		foreach($modelClasses as $modelClass) {
			$modelObj = singleton($modelClass);
			
			$form = $this->getSearchFormForModel($modelClass);
			
			$forms->push(new ArrayData(array(
				'Form' => $form,
				'Title' => $modelObj->singular_name() 
			)));
		}
		
		return $forms;
	}
	
	/**
	 * Enter description here...
	 *
	 * @todo Add customizeable validator
	 * 
	 * @param string $modelClass
	 */
	protected function getEditForm($modelClass) {
		$modelObj = singleton($modelClass);
		$formName = "EditForm_$modelClass";
		$fields = $this->getFieldsForModel($modelObj);
		$this->getComponentFieldsForModel($modelObj, $fields);
		$actions = $this->getActionsForModel($modelObj);
		$validator = $this->getValidatorForModel($modelObj);
		// necessary because of overriding the form action - NEED TO VERIFY THIS
		$fields->push(new HiddenField("executeForm", $formName));
		$form = new Form($this, $formName, $fields, $actions, $validator);
		return $form;
	}
	
	// ############# Utility Methods ##############
	
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
	 * Get all cms fields for the model
	 * (defaults to {@link DataObject->getCMSFields()}).
	 * 
	 * @todo Make method hook customizeable
	 * 
	 * @param DataObject $modelObj
	 * @return FieldSet
	 */
	protected function getFieldsForModel($modelObj) {
		$fields = $modelObj->getCMSFields();
		if (!$fields->dataFieldByName('ID')) {
			$fields->push(new HiddenField('ID'));
		}
		return $fields;
	}
	
	/**
	 * Update: complex table field generation moved into controller - could be
	 * in the DataObject scaffolding?
	 *
	 * @param unknown_type $modelObj
	 * @return FieldSet of ComplexTableFields
	 */
	protected function getComponentFieldsForModel($modelObj, $fieldSet) {
		foreach($modelObj->has_many() as $relationship => $component) {
			
			$relationshipFields = array_keys(singleton($component)->searchableFields());
			$fieldSet->push(new ComplexTableField($this, $relationship, $component, $relationshipFields));
			
		}
		return $fieldSet;		
	}
	
	/**
	 * Get all actions which are possible in this controller,
	 * and allowed by the model security.
	 * 
	 * @todo Hook into model security once its is implemented
	 * 
	 * @param DataObject $modelObj
	 * @return FieldSet
	 */
	protected function getActionsForModel($modelObj) {
		$actions = new FieldSet(
			new FormAction('save', _t('GenericDataAdmin.SAVE')),
			new FormAction('delete', _t('GenericDataAdmin.DELETE'))
		);
		return $actions;
	}
	
	/**
	 * NOT IMPLEMENTED
	 * Get a valdidator for the model.
	 * 
	 * @todo Hook into model security once its is implemented
	 * 
	 * @param DataObject $modelObj
	 * @return FieldSet
	 */
	protected function getValidatorForModel($modelObj) {
		return false;
	}
		
	/**
	 * Get a search form for a single {@link DataObject} subclass.
	 * 
	 * @param string $modelClass
	 * @return FieldSet
	 */
	protected function getSearchFormForModel($modelClass) {
		if(substr($modelClass,0,11) == 'SearchForm_') $modelClass = substr($modelClass, 11);
		
		$context = singleton($modelClass)->getDefaultSearchContext();
		
		$form = new Form(
			$this,
			"SearchForm_$modelClass",
			$context->getSearchFields(),
			new FieldSet(
				new FormAction('search', _t('MemberTableField.SEARCH'))
			)
		);
		$form->setFormMethod('get');
		return $form;
	}
	
	/**
	 * Overwrite the default form action with the path to the DataObject filter
	 * (relies on the search form having the specifically named id as 'SearchForm_DataObject')
	 * 
	 * @todo override default linking to add DataObject based URI paths
	 */
	function FormObjectLink($formName) {
		return $this->Link();
	}

	/**
	 * Action to render a data object collection, using the model context to provide filters
	 * and paging.
	 */
	function search($data, $form) {
		$className = $this->urlParams['ClassName'];
		Debug::dump($className);
		if (in_array($className, $this->getManagedModels())) {
			$model = singleton($className);
			// @todo need to filter post vars
			$searchKeys = array_intersect_key($_POST, $model->searchableFields());
			$context = $model->getDefaultSearchContext();
			$results = $context->getResultSet($searchKeys);
			if ($results) {
				echo "<table>";
				foreach($results as $row) {
					$uri = Director::absoluteBaseUrl();
					echo "<tr id=\"{$uri}admin/crm/view/$className/$row->ID\">";
					foreach($model->searchableFields() as $key=>$val) {
						echo "<td>";
						echo $row->getField($key);
						echo "</td>";
					}
					echo "</tr>";
				}
				echo "</table>";
			} else {
				echo "<p>No results found</p>";
			}
		}
		die();
	}
	
	/**
	 * View a generic model using a form object to render a partial HTML
	 * fragment to be embedded via Ajax calls.
	 */
	function show() {
		$className = $this->urlParams['ClassName'];
		$ID = $this->urlParams['ID'];
		
		if (in_array($className, $this->getManagedModels())) {
			
			$model = DataObject::get_by_id($className, $ID);
			
			/*$fields = $model->getCMSFields();
			
			$actions = new FieldSet(
				new FormAction('save', 'Save')
			);
			
			$form = new Form($this, $className, $fields, $actions);
			$form->makeReadonly();*/
			$form = $this->getEditForm($className);
			$form->loadNonBlankDataFrom($model);
			return $form->forTemplate();
		}
	}
	
	

}
?>