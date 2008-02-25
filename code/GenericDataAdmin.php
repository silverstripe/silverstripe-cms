<?php

/**
 * Provides a common interface for searching, viewing and editing DataObjects.
 * Extend the class to adjust functionality to your specific DataObjects.
 * 
 * @var $data_type DataObject The base class
 * @var $data_type_extra Array Additional DataObjects which are included in the search.
 * @var $resultColumnts Array Columnnames shown in the result-table.
 */
abstract class GenericDataAdmin extends LeftAndMain {

	public $filter;
	
	/**
	 * @var  FieldSet Specifies which {Actions} can be performed on a resultset,
	 * e.g. "Export" or "Send". The form contains the resultset as CSV for further processing.
	 * These actions can be extended in a subclassed constructor.
	 */
	protected $result_actions;
	
	/**
	 * @var string 
	 */
	static $data_type;
	
	static $data_type_extra;
	
	/**
	 * Specifies which information should be listed in the results-box,
	 * either in "table"- or "list"-format (see {$result_format}).
	 * 
	 * Format "table":
	 * array(
	 * 	'AccountName' => 'AccountName'
	 * )
	 * 
	 * Format "list":
	 * see {@DataObject->buildNestedUL}
	 */
	static $result_columns;
	
	/**
	 * @var string 
	 * Either "table" or "list". List-format also supports second level results.
	 */
	static $result_format = "table";
	
	static $csv_columns;
	
	private $results;

	function __construct() {
		$this->result_actions = new FieldSet(
			new FormAction("export","Export as CSV")
		);
		
		parent::__construct();
	}

	/**
	 * Sets Requirements and checks for Permissions.
	 * Subclass this function to add custom Requirements.
	 */
	function init() {
		parent::init();

		Requirements::javascript(MCE_ROOT . "tiny_mce_src.js");
		Requirements::javascript("jsparty/tiny_mce_improvements.js");

		Requirements::javascript("jsparty/hover.js");
		Requirements::javascript("jsparty/scriptaculous/controls.js");

		Requirements::javascript("cms/javascript/SecurityAdmin.js");
		Requirements::javascript("cms/javascript/CMSMain_left.js");

		Requirements::javascript("cms/javascript/GenericDataAdmin_left.js");
		Requirements::javascript("cms/javascript/GenericDataAdmin_right.js");
		Requirements::javascript("cms/javascript/SideTabs.js");
		
		// We don't want this showing up in every ajax-response, it should always be present in a CMS-environment
		if(!Director::is_ajax()) {
			Requirements::javascriptTemplate("cms/javascript/tinymce.template.js", array(
				"ContentCSS" => project() . "/css/editor.css",
				"BaseURL" => Director::absoluteBaseURL(),
				"Lang" => i18n::get_tinymce_lang()
			));
		}

		Requirements::css("cms/css/GenericDataAdmin.css");

		//For wrightgroup workshop
		Requirements::css("writework/css/WorkshopCMSLayout.css");
	}
	
	function Link() {
		$args = func_get_args();	
		return call_user_func_array( array( &$this, 'getLink' ), $args );
	}
	
	/**
	 * @return String
	 */
	function DataTypeSingular() {
		return singleton($this->stat('data_type'))->singular_name();
	}

	/**
	 * @return String
	 */
	function DataTypePlural() {
		return singleton($this->stat('data_type'))->plural_name();
	}

	/**
	 * @return Form
	 */
	function CreationForm() {
		$plural_name = singleton($this->stat('data_type'))->plural_name();
		$singular_name = singleton($this->stat('data_type'))->singular_name();
		return new Form($this, 'CreationForm', new FieldSet(), new FieldSet(new FormAction("createRecord", "Create {$singular_name}")));
	}

	/**
	 * @return Form
	 */
	function EditForm() {
		$id = isset($_REQUEST['ID']) ? $_REQUEST['ID'] : Session::get('currentPage');
		if($id && DataObject::get_by_id($this->stat('data_type'), $id)) {
			return $this->getEditForm($id);
		}
	}

	// legacy
	function ExportForm() {
		return $this->EditForm();
	}

	/**
	 * @return Form
	 */
	function SearchForm() {
		
		$fields = $this->getSearchFields();
		$actions = new FieldSet($action = new FormAction("getResults", "Go"));

		$searchForm = new Form($this, "SearchForm", $fields, $actions);
		$searchForm->loadDataFrom($_REQUEST);
		return $searchForm;
	}
	
	/**
	 * Determines fields and actions for the given {$data_type}, and populates
	 * these fields with values from {$data_type} and any connected {$data_type_extra}.
	 * Adds default actions ("save" and "delete") if no custom actions are found.
	 * Returns an empty form if no fields or actions are found (on first load).
	 * 
	 * @param $id Number
	 * @return Form
	 */
	function getEditForm($id) {
		if(isset($_GET['debug_profile'])) Profiler::mark('getEditForm');
		
		$genericData = DataObject::get_by_id($this->stat('data_type'), $id);

		$fields = (method_exists($genericData, 'getCMSFields')) ? $genericData->getCMSFields() : new FieldSet();

		if(!$fields->dataFieldByName('ID')) {

			$fields->push($idField = new HiddenField("ID","ID",$id));
			$idField->setValue($id);
		}
		
		if(method_exists($genericData, 'getGenericStatus')){
			$genericDataStatus = $genericData->getGenericStatus();
			if($genericDataStatus){
				$fields->push($dataStatusField = new ReadonlyField("GenericDataStatus", "", $genericDataStatus));
				$dataStatusField -> dontEscape = true;
			}
		}
		

		$actions = (method_exists($genericData, 'getCMSActions')) ? $genericData->getCMSActions() : new FieldSet();
		if(!$actions->fieldByName('action_save')) {
			$actions->push(new FormAction('save', 'Save','ajaxAction-save'));
		}
		if(!$actions->fieldByName('action_delete')) {
			$actions->push(new FormAction('delete', 'Delete','ajaxAction-delete'));
		}
		
		$required = (method_exists($genericData, 'getCMSRequiredField')) ? $genericData->getCMSRequiredField() : new RequiredFields(); 
		$form = new Form($this, "EditForm", $fields, $actions, $required); 
		if($this->stat('data_type_extra')) {
			foreach ($this->stat('data_type_extra') as $oneRelated) {
				$oneExtra = $genericData-> $oneRelated();
				if($oneExtra) {
					$allFields = $oneExtra->getAllFields();
					foreach ($allFields as $k => $v) {
						$fieldname = $oneRelated . "[" . $k . "]";
						$allFields[$fieldname] = $v;
						unset ($allFields[$k]);
					}

					$form->loadDataFrom($allFields);
				}
			}
		}

		$form->loadDataFrom($genericData);
		$form->disableDefaultAction();

		if(isset($_GET['debug_profile'])) Profiler::unmark('getEditForm');
		return $form;
	}

	/**
	 * Display the results of the search.
	 * @return String
	 */
	function Results() {
		$ret = "";
		
		$singular_name = singleton($this->stat('data_type'))->singular_name();
		$plural_name = singleton($this->stat('data_type'))->plural_name();
		if (!$this->filter) {
		$this->filter = array(
			"ClassName" => $this->stat('data_type')
		);
		} else {
			$this->filter = $this->filter + array("ClassName" => $this->stat('data_type'));
		}
		
		$results = $this->performSearch();
		if($results) {
			$name = ($results->Count() > 1) ? $plural_name : $singular_name;
			$ret .= "<H2>{$results->Count()} {$name} found:</H2>";
			
			switch($this->stat('result_format')) {
				case 'table':
					$ret .= $this->getResultTable($results);
					break;
				case 'list':
					$ret .= $this->getResultList($results);
					break;
			}
			$ret .= $this->getResultActionsForm($results);
		} else {
			if($this->hasMethod('isEmptySearch') && $this->isEmptySearch()) {
				$ret .="<h3>Please choose some search criteria and press 'Go'.</h3>";
			} else {
				$ret .="<h3>Sorry, no {$plural_name} found by this search.</h3>";
			}
		}
		return $ret;
	}
	
	function getResults($data, $form) {
		return $this->Results($data, $form);
	}
	
	function getResultList($results, $link = true) {
		$listBody = $results->buildNestedUL($this->stat('result_columns'));
		
		return <<<HTML
<div class="ResultList">
	$listBody
</div>
HTML;
	}
	
	/**
	 * @param $results
	 * @param $link Link the rows to their according result (evaluated by javascript)
	 * @return String Result-Table as HTML
	 */
	function getResultTable($results, $link = true) {
		$tableHeader = $this->columnheader();

		$tableBody = $this->columnbody($results);
		
		return <<<HTML
<table class="ResultTable">
	<thead>
		$tableHeader
	</thead>
	<tbody>
		$tableBody
	</tbody>
</table>
HTML;
	}
	
	protected function columnheader(){
		$content = "";
		foreach( array_keys($this->stat('result_columns')) as $field ) {
			$content .= $this->htmlTableCell($field);
		}
		return $this->htmlTableRow($content); 
	}
	
	protected function columnbody($results=null) {
		// shouldn't be called here, but left in for legacy
		if(!$results) {
			$results = $this->performSearch();
		}
		
		$body = "";
		if($results){
			$i=0;
			foreach($results as $result){
				$i++;
				$html = "";
				foreach($this->stat('result_columns') as $field) {
					$value = $this->buildResultFieldValue($result, $field);
					$html .= $this->htmlTableCell($value, $this->Link("show", $result->ID), "show", true);
				
				}
				$evenOrOdd = ($i%2)?"even":"odd";
				$row = $this->htmlTableRow($html, null, $evenOrOdd);
				$body .= $row;
			}
		}
		return $body;
	}
	
	protected function listbody($results=null) {
		
	}
	
	/**
	 * @param $results Array 
	 * @return String Form-Content
	 */
	function getResultActionsForm($results) {
		$ret = "";
		
		$csvValues = array();
		foreach($results as $record) {
			$csvValues[] = $record->ID;
		}
		
		$form = new Form(
			$this,
			"ExportForm",
			new FieldSet(
				new HiddenField("csvIDs","csvIDs",implode(",",$csvValues))
			),
			$this->result_actions
		);

		$ret = <<<HTML
<div id="Form_ResultForm">
{$form->renderWith("Form")}
</div>
HTML;

		return $ret;
	}
	
	/**
	 * @param $result
	 * @param $field Mixed can be array: eg: array("FirstName", "Surname"), in which case two fields 
	 * in database table should concatenate to one cell for report table.
	 * The field could be "Total->Nice" or "Order.Created->Date", intending to show its specific format.
	 * Caster then is "Nice" "Date" etc.
	 */
	protected function buildResultFieldValue($result, $field) {
		if(is_array($field)) {
			$i = 0;
			foreach($field as $each) {
				$value .= $i == 0 ? "" : "_";
				$value .= $this->buildResultFieldValue($result, $each);
				$i++;
			}
		} else {
			$fieldParts = explode("->", $field);
			$field = $fieldParts[0];
			if(preg_match('/^(.+)\.(.+)$/', $field, $matches)) {
				$field = $matches[2];
			}

			if(isset($fieldParts[1])) {
				$caster = $fieldParts[1];
				// When the intending value is Created.Date, the obj need to be casted as Datetime explicitely.
				if ($field == "Created" || $field == "LastEdited") {
					$created = Object::create('Datetime', $result->Created, "Created");
					// $created->setVal();
					$value = $created->val($caster);
				} else // Dealing with other field like "Total->Nice", etc.
					$value = $result->obj($field)->val($caster);
			} else { // Simple field, no casting
				$value = $result->val($field);
			}
		}

		return $value;
	}

	protected function htmlTableCell($value, $link = false, $class = "", $id = null) {
		if($link) {
			return "<td><a href=\"$link\" class=\"$class\" id=\"$id\">" . htmlentities($value) . "</a></td>";
		} else {
			return "<td>" . htmlentities($value) . "</td>";
		}
	}

	protected function htmlTableRow($value, $link = null, $evenOrOdd = null) {
		if ($link) {
			return "<tr class=\"$evenOrOdd\"><a href=\"$link\">" . $value . "</a></tr>";
		} else {
			return "<tr class=\"$evenOrOdd\">" . $value . "</tr>";
		}
	}

	/**
	 * Exports a given set of comma-separated IDs (from a previous search-query, stored in a HiddenField).
	 * Uses {$csv_columns} if present, and falls back to {$result_columns}.
	 */
	function export() {
		
		$now = Date("s-i-H");
		$fileName = "export-$now.csv";
		
		$csv_columns = ($this->stat('csv_columns')) ? array_values($this->stat('csv_columns')) : array_values($this->stat('result_columns'));

		$fileData = "";
		$fileData .= "\"" . implode("\";\"",$csv_columns) . "\"";
		$fileData .= "\n";

		$records = $this->performSearch();
		if($records) {
			foreach($records as $record) {
				$columnData = array();
				foreach($csv_columns as $column) {
					$tmpColumnData = "\"" . str_replace("\"", "\"\"", $record->$column) . "\"";
					$tmpColumnData = str_replace(array("\r", "\n"), "", $tmpColumnData);
					$columnData[] = $tmpColumnData;
				}
				$fileData .= implode(",",$columnData);
				$fileData .= "\n";
			}
			
			HTTP::sendFileToBrowser($fileData, $fileName);
		} else {
			user_error("No records found", E_USER_ERROR);
		}

	}
	
	
	/**
	 * Save generic data handler
	 * 
	 * @return String Statusmessage
	 */
	function save($urlParams, $form) {

		$className = $this->stat('data_type');

		$id = $_REQUEST['ID'];

		if(substr($id, 0, 3) != 'new') {
			$generic = DataObject::get_one($className, "`$className`.ID = $id");
			$generic->Status = "Saved (Update)";
		} else {
			$generic = new $className();
			$generic->Status = "Saved (New)";
		}

		$form->saveInto($generic, true);
		$id = $generic->write();

		if($this->stat('data_type_extra')) {
			foreach($this->stat('data_type_extra') as $oneRelated) {
				$oneExtra = $generic->$oneRelated();
				if($_REQUEST[$oneExtra->class]) {
					foreach($_REQUEST[$oneExtra->class] as $field => $value) {
						$oneExtra->setField($field, $value);
					}
					$oneExtra->write();
				}
			}
		}

		FormResponse::status_message('Saved', 'good');
		FormResponse::update_status($generic->Status);

		if (method_exists($this, "saveAfterCall")) {
			$this->saveAfterCall($generic, $urlParams, $form);
		}
		
		return FormResponse::respond();
	}

	/**
	 * Show a single record
	 * 
	 * @return Array Editing Form
	 */
	function show() {

		Session::set('currentPage', $this->urlParams['ID']);

		$editForm = $this->getEditForm($this->urlParams['ID']);

		if(Director::is_ajax()) {
			return $editForm->formHtmlContent();
		} else {
			return array (
				'EditForm' => $editForm
			);
		}
	}

	/**
	 * Add a new DataObject
	 * 
	 * @return String
	 */
	function createRecord() {
		$baseClass = $this->stat('data_type');
		$obj = new $baseClass();
		$obj->write();

		$editForm = $this->getEditForm($obj->ID);

		return (Director::is_ajax()) ? $editForm->formHtmlContent() : array ('EditForm' => $editForm);
	}

	/**
	 * Delete a given Dataobjebt by ID
	 * 
	 * @param $urlParams Array
	 * @param $form Form
	 * @return String
	 */
	function delete($urlParams, $form) {
		$id = Convert::raw2sql($_REQUEST['ID']);
		$obj = DataObject::get_by_id($this->stat('data_type'), $id);
		if ($obj) {
			$obj->delete();
		}
		
		// clear session data
		Session::clear('currentPage');

		FormResponse::status_message('Successfully deleted', 'good');
		FormResponse::add("$('Form_EditForm').deleteEffect();");

		return FormResponse::respond();
	}
	
	protected function getRelatedData() {

		$relatedName = $_REQUEST['RelatedClass'];
		$id = $_REQUEST[$relatedName]['ID'];
		$baseClass = $this->stat('data_type');
		$relatedClasses = singleton($baseClass)->stat('has_one');
		if($id){
			$relatedObject = DataObject::get_by_id($relatedClasses[$relatedName], $id);
			$response .= <<<JS
			$('$relatedName').unsetNewRelatedKey();
JS;
		}
		elseif($id !== '0'){ //in case of null;
			$relatedObject = new $relatedClasses[$relatedName]();
			if($parentID = $_REQUEST[$relatedName]['ParentID']){
				$relatedObject->ParentID = $parentID;
			}
			$id = $relatedObject->write();
			$response .= <<<JS
			$('$relatedName').setNewRelatedKey($id);
JS;
		}else{ // in case of 0
			$relatedObject = new $relatedClasses[$relatedName]();
			if($parentID = $_REQUEST[$relatedName]['ParentID']){
				$relatedObject->ParentID = $parentID;
			}
			$response .= <<<JS
			$('$relatedName').unsetNewRelatedKey();
JS;
		}

		if(Director::is_ajax()) {
			$fields = $_REQUEST[$relatedName];

			$response .= <<<JS
var dataArray = new Array();
JS;
			foreach ($fields as $k => $v) {
				$JS_newKey = Convert::raw2js($relatedName . '[' . $k . ']');
				$JS_newValue = Convert::raw2js($relatedObject-> $k);
				$response .=<<<JS
dataArray['$JS_newKey'] = '$JS_newValue';
JS;
			}

			$response .=<<<JS
$('$relatedName').updateChildren(dataArray, true);
JS;

			FormResponse::add($response);
		} 
		
		return FormResponse::respond();
	}

	protected function updateRelatedKey() {
		if(Director::is_ajax()) {
			$funcName = "get" . $_REQUEST['RelatedClass'] . "Dropdown";
			$relatedKeyDropdown = singleton($this->stat('data_type'))->$funcName();
			$relatedKeyDropdown->extraClass = "relatedDataKey";
			echo $relatedKeyDropdown->FieldHolder();
		} else {
			Director::redirectBack();
		}
	}

	/**
	 * Execute a query based on {$filter} and build a DataObjectSet
	 * out of the results.
	 * 
	 * @return DataObjectSet
	 */
	abstract function performSearch();

	/**
	 * Form fields which trigger {getResults} and {peformSearch}.
	 * Provide HTML in the following format to get auto-collapsing "advanced search"-fields.
	 * <div id="BasicSearchFields"></div>
	 * <div class="ToggleAdvancedSearchFields" style="display:none"><a href="#">Show advanced options</a></div>
	 * <div id="AdvancedSearchFields"></div>
	 * 
	 * @return FieldSet
	 */
	abstract function getSearchFields();

	/**
	 * Provide custom link.
	 * 
	 * @return String
	 */
	abstract function getLink();

	//abstract function create();

	/**
	 * Legacy
	 */
	function AddForm() {
		return $this->CreationForm();
	}
}
?>