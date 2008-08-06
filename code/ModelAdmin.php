<?php
/**
 * Generates a three-pane UI for editing one or many model classes,
 * with an automatically generated search panel, tabular results
 * and edit forms.
 * Relies on data such as {@link DataObject::$db} and {@DataObject::getCMSFields()}
 * to scaffold interfaces "out of the box", while at the same time providing
 * flexibility to customize the default output.
 * 
 * Add a route:
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
	
	public function init() {
		parent::init();
		
		// security check for valid models
		if(isset($this->urlParams['Model']) && !in_array($this->urlParams['Model'], $this->getManagedModels())) {
			user_error('ModelAdmin::init(): Invalid Model class', E_USER_ERROR);
		}
		
		Requirements::css('cms/css/ModelAdmin.css');
	}
	
	/**
	 * Return default link to this controller
	 *
	 * @todo We've specified this in Director::addRules(), why repeat?
	 * 
	 * @return string
	 */
	public function Link() {
		return 'admin/crm/' . implode('/', array_values(array_unique($this->urlParams)));
	}
	
	/**
	 * Create empty edit form (scaffolded from DataObject->getCMSFields() by default).
	 * Can be called either through {@link AddForm()} or directly through URL:
	 * "/myadminroute/MyModelClass/add"
	 *
	 * @param array $data
	 * @param Form $form
	 */
	public function add($data, $form) {
		$className = (isset($data['ClassName'])) ? $data['ClassName'] : $this->urlParams['ClassName'];
		if(!isset($className) || !in_array($data['ClassName'], $this->getManagedModels())) return false;

		return $this->customise(array(
			'EditForm' => $this->getEditForm($data['ClassName'])
		))->renderWith('LeftAndMain');
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
	 * @todo documentation
	 *
	 * @param array $data
	 * @param Form $form
	 */
	public function save($data, $form) {
		// @todo implementation
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
			'AddForm',
			new FieldSet(
				new DropdownField(
					'ClassName',
					'Type',
					$modelMap
				)
			),
			new FieldSet(
				new FormAction('add', _t('GenericDataAdmin.CREATE'))
			)
		);
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
		
		$fields = $this->getFieldsForModel($modelObj); 
		
		$actions = $this->getActionsForModel($modelObj);
		
		$validator = $this->getValidatorForModel($modelObj);
		
		$form = new Form(
			$this,
			'ModelEditForm',
			$fields,
			$actions,
			$validator
		);
		
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
		return $modelObj->getCMSFields();
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
		$context = singleton($modelClass)->getDefaultSearchContext();
		
		$form = new Form(
			$this,
			"SearchForm_{$modelClass}",
			$context->getSearchFields(),
			new FieldSet(
				new FormAction('search', _t('MemberTableField.SEARCH'))
			)
		);
		//$form->setFormMethod('get');
		return $form;
	}
	
	/**
	 * Another counter intuitive hoop to jump through, as the Form constructor
	 * is still thoroughly confusing the hell out of me.
	 */
	function FormObjectLink($value) {
		$value = str_replace('SearchForm_', '', $value);
		return "admin/crm/$value/search";
	}

	/**
	 * Action to execute a search using the model context
	 */
	function search() {
		$className = $this->urlParams['ClassName'];
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
					echo "<tr id=\"{$uri}admin/crm/$className/view/$row->ID\">";
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
	}
	
	function view() {
		$className = $this->urlParams['ClassName'];
		$ID = $this->urlParams['ID'];
		
		if (in_array($className, $this->getManagedModels())) {
			
			$model = DataObject::get_by_id($className, $ID);
			
			$fields = $model->getCMSFields();
			
			$form = new Form($this, $className, $fields, new FieldSet());
			$form->makeReadonly();
			$form->loadNonBlankDataFrom($model);
			echo $form->forTemplate();
		}
	}
	
	

}
?>