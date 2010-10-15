<?php
/**
 * Base "abstract" class creating reports on your data.
 * 
 * Creating reports
 * ================
 * 
 * Creating a new report is a matter overloading a few key methods
 * 
 *  {@link title()}: Return the title - i18n is your responsibility
 *  {@link description()}: Return the description - i18n is your responsibility
 *  {@link sourceQuery()}: Return a DataObjectSet of the search results
 *  {@link columns()}: Return information about the columns in this report.
 *  {@link parameterFields()}: Return a FieldSet of the fields that can be used to filter this
 *  report.
 * 
 * If you can't express your report as a query, you can implement the this method instead:
 * 
 *     // Return an array of fields that can be used to sort the data
 *     public function sourceRecords($params, $sort, $limit) { ... }
 * 
 * The $sort value will be set to the corresponding key of the columns() array.  If you wish to
 * make only a subset of the columns sortable, then you can override `sortColumns()` to return a
 * subset of the array keys.
 * 
 * Note that this implementation is less efficient and should only be used when necessary.
 * 
 * If you wish to modify the report in more extreme ways, you could overload these methods instead.
 * 
 * {@link getReportField()}: Return a FormField in the place where your report's TableListField
 * usually appears.
 * {@link getCMSFields()}: Return the FieldSet representing the complete right-hand area of the 
 * report, including the title, description, parameter fields, and results.
 * 
 * Showing reports to the user
 * ===========================
 * 
 * Right now, all subclasses of SS_Report will be shown in the ReportAdmin.  However, we are planning
 * on adding an explicit registration mechanism, so that you can decide which reports go in the
 * report admin, and which go elsewhere (such as the side panel in the CMS).
 * 
 * @package cms
 * @subpackage reports
 */
class SS_Report extends ViewableData {
	/**
	 * Report registry populated by {@link SS_Report::register()}
	 */
	private static $registered_reports = array();

	/**
	 * This is the title of the report,
	 * used by the ReportAdmin templates.
	 *
	 * @var string
	 */
	protected $title = '';

	/**
	 * This is a description about what this
	 * report does. Used by the ReportAdmin
	 * templates.
	 *
	 * @var string
	 */
	protected $description = '';
	
	/**
	 * The class of object being managed by this report.
	 * Set by overriding in your subclass.
	 */
	protected $dataClass = 'SiteTree';
	
	/**
	 * Return the title of this report.
	 * 
	 * You have two ways of specifying the description:
	 *  - overriding description(), which lets you support i18n 
	 *  - defining the $description property
	 */
	function title() {
		return $this->title;
	}
	
	/**
	 * Return the description of this report.
	 * 
	 * You have two ways of specifying the description:
	 *  - overriding description(), which lets you support i18n 
	 *  - defining the $description property
	 */
	function description() {
		return $this->description;
	}
	
	/**
	 * Return a FieldSet specifying the search criteria for this report.
	 * 
	 * Override this method to define search criteria.
	 */
	function parameterFields() {
		return null;
	}

	/**
	 * Return the {@link SQLQuery} that provides your report data.
	 */
	function sourceQuery($params) {
		if($this->hasMethod('sourceRecords')) {
			$query = new SS_Report_FakeQuery($this, 'sourceRecords', $params);
			$query->setSortColumnMethod('sortColumns');
			return $query;
		} else {
			user_error("Please override sourceQuery()/sourceRecords() and columns() or, if necessary, override getReportField()", E_USER_ERROR);
		}
	}
	
	/**
	 * Return a DataObjectSet records for this report.
	 */
	function records($params) {
		if($this->hasMethod('sourceRecords')) return $this->sourceRecords($params, null, null);
		else {
			$query = $this->sourceQuery();
			return singleton($this->dataClass())->buildDataObjectSet($query->execute(), "DataObjectSet", $query);
		}
	}

	/**
	 * Return an map of columns for your report.
	 *  - The map keys will be the source columns for your report (in TableListField dot syntax)
	 *  - The values can either be a string (the column title), or a map containing the following
	 *    column parameters:
	 *    - title: The column title
	 *    - formatting: A formatting string passed to {@link TableListField::setFieldFormatting()}
	 */
	function columns() {
		user_error("Please override sourceQuery() and columns() or, if necessary, override getReportField()", E_USER_ERROR);
	}
	
	function sortColumns() {
		return array_keys($this->columns());
	}
	
	/**
	 * Return the number of records in this report with no filters applied.
	 */
	function count() {
		return (int)$this->sourceQuery(array())->unlimitedRowCount();
	}
	
	/**
	 * Return the data class for this report
	 */
	function dataClass() {
		return $this->dataClass;
	}


	/**
	 * Returns a FieldSet with which to create the CMS editing form.
	 * You can use the extend() method of FieldSet to create customised forms for your other
	 * data objects.
	 *
	 * @uses getReportField() to render a table, or similar field for the report. This
	 * method should be defined on the SS_Report subclasses.
	 *
	 * @return FieldSet
	 */
	function getCMSFields() {
		$fields = new FieldSet(
			new LiteralField(
				'ReportTitle', 
				 "<h3>{$this->title()}</h3>"
			)
		);
		
		if($this->description) $fields->push(
			new LiteralField('ReportDescription', "<p>{$this->description}</p>"));
			
		// Add search fields is available
		if($params = $this->parameterFields()) {
			$filters = new FieldGroup('Filters');
			foreach($params as $param) {
				if ($param instanceof HiddenField) $fields->push($param);
				else $filters->push($param);
			}
			$fields->push($filters);

			// Add a search button
			$fields->push(new FormAction('updatereport', 'Filter'));
		}
		
		$fields->push($this->getReportField());
		
		$this->extend('updateCMSFields', $fields);
		
		return $fields;
	}
	
	function getCMSActions() {
		// getCMSActions() can be extended with updateCMSActions() on a decorator
		$actions = new FieldSet();
		$this->extend('updateCMSActions', $actions);
		return $actions;
	}
	
	/**
	 * Return a field, such as a {@link ComplexTableField} that is
	 * used to show and manipulate data relating to this report.
	 * 
	 * Generally, you should override {@link columns()} and {@link records()} to make your report,
	 * but if they aren't sufficiently flexible, then you can override this method.
	 *
	 * @return FormField subclass
	 */
	function getReportField() {
		$columnTitles = array();
		$fieldFormatting = array();
		$csvFieldFormatting = array();
		$fieldCasting = array();
		
		// Parse the column information
		foreach($this->columns() as $source => $info) {
			if(is_string($info)) $info = array('title' => $info);
			
			if(isset($info['formatting'])) $fieldFormatting[$source] = $info['formatting'];
			if(isset($info['csvFormatting'])) $csvFieldFormatting[$source] = $info['csvFormatting'];
			if(isset($info['casting'])) $fieldCasting[$source] = $info['casting'];
			$columnTitles[$source] = isset($info['title']) ? $info['title'] : $source;
		}
		
		// To do: implement pagination
		$query = $this->sourceQuery($_REQUEST);
			
		$tlf = new TableListField('ReportContent', $this->dataClass(), $columnTitles);
		$tlf->setCustomQuery($query);
		$tlf->setShowPagination(true);
		$tlf->setPageSize(50);
		$tlf->setPermissions(array('export', 'print'));
		
		// Hack to figure out if we are printing
		if (isset($_REQUEST['url']) && array_pop(explode('/', $_REQUEST['url'])) == 'printall') {
			$tlf->setTemplate('SSReportTableField');
		}
		
		if($fieldFormatting) $tlf->setFieldFormatting($fieldFormatting);
		if($csvFieldFormatting) $tlf->setCSVFieldFormatting($csvFieldFormatting);
		if($fieldCasting) $tlf->setFieldCasting($fieldCasting);

		return $tlf;
	}
	
	/**
	 * @param Member $member
	 * @return boolean
	 */
	function canView($member = null) {
		if(!$member && $member !== FALSE) {
			$member = Member::currentUser();
		}
		
		return true;
	}
	

	/**
	 * Return the name of this report, which
	 * is used by the templates to render the
	 * name of the report in the report tree,
	 * the left hand pane inside ReportAdmin.
	 *
	 * @return string
	 */
	function TreeTitle() {
		return $this->title();/* . ' (' . $this->count() . ')'; - this is too slow atm */
	}
	
	/**
	 * Return the ID of this Report class.
	 * Because it doesn't have a number, we
	 * use the class name as the ID.
	 *
	 * @return string
	 */
	function ID() {
		return $this->class;
	}
	
	/////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Register a report.
	 * @param $list The list to add the report to: "ReportAdmin" or "SideReports"
	 * @param $reportClass The class of the report to add.
	 * @param $priority The priority.  Higher numbers will appear furhter up in the reports list.
	 * The default value is zero.
	 */
	static function register($list, $reportClass, $priority = 0) {
		if(strpos($reportClass, '(') === false && (!class_exists($reportClass) || !is_subclass_of($reportClass,'SS_Report'))) {
			user_error("SS_Report::register(): '$reportClass' is not a subclass of SS_Report", E_USER_WARNING);
			return;
		}
		
		self::$registered_reports[$list][$reportClass] = $priority;
	}
	
	/**
	 * Unregister a report, removing it from the list
	 */
	static function unregister($list, $reportClass) {
		unset(self::$registered_reports[$list][$reportClass]);
	}
	
	/**
	 * Return the SS_Report objects making up the given list.
	 * @return An array of SS_Report objects
	 */
	static function get_reports($list) {
		$output = array();
		if(isset(self::$registered_reports[$list])) {
			$listItems = self::$registered_reports[$list];

			// Sort by priority, preserving internal order of items with the same priority
			$groupedItems = array();
			foreach($listItems as $k => $v) {
				$groupedItems[$v][] = $k;
			}
			krsort($groupedItems);
			$sortedListItems = call_user_func_array('array_merge', $groupedItems);
			
			foreach($sortedListItems as $report) {
				if(strpos($report,'(') === false) $reportObj = new $report;
				else $reportObj = eval("return new $report;");

				$output[$reportObj->ID()] = $reportObj;
			} 
		}
		
		return $output;
	}

}

/**
 * This is an object that can be used to dress up a more complex querying mechanism in the clothing
 * of a SQLQuery object.  This means that you can inject it into a TableListField.
 * 
 * Use it like this:
 * 
 *     function sourceQuery($params) {
 *         return new SS_Report_FakeQuery($this, 'sourceRecords', $params)
 *     }
 *     function sourceRecords($params, $sort, $limit) {
 *         // Do some stuff
 *         // Return a DataObjectSet of actual objects.
 *     }
 * 
 * This object is used by the default implementation of sourceQuery() on SS_Report, to make use of
 * a sourceReords() method if one exists.
 */
class SS_Report_FakeQuery extends SQLQuery {
	public $orderby;
	public $limit;
	
	protected $obj, $method, $params;
	
	function __construct($obj, $method, $params) {
		$this->obj = $obj;
		$this->method = $method;
		$this->params = $params;
	}
	
	/**
	 * Provide a method that will return a list of columns that can be used to sort.
	 */
	function setSortColumnMethod($sortColMethod) {
		$this->sortColMethod = $sortColMethod;
	}

	function limit($limit) {
		$this->limit = $limit;
	}
	
	function unlimitedRowCount() {
		$source = $this->obj->{$this->method}($this->params, null, null);
		return $source ? $source->Count() : 0;
	}
	
	function execute() {
		$output = array();
		$source = $this->obj->{$this->method}($this->params, $this->orderby, $this->limit);
		if($source) foreach($source as $item) {
			$mapItem = $item->toMap();
			$mapItem['RecordClassName'] = get_class($item);
			$output[] = $mapItem;
		}
		return $output;
	}
	
	function canSortBy($fieldName) {
		$fieldName = preg_replace('/(\s+?)(A|DE)SC$/', '', $fieldName);
		if($this->sortColMethod) {
			$columns = $this->obj->{$this->sortColMethod}();
			return in_array($fieldName, $columns);
		} else {
			return false;
		}
	}
}

/**
 * SS_ReportWrapper is a base class for creating report wappers.
 * 
 * Wrappers encapsulate an existing report to alter their behaviour - they are implementations of
 * the standard GoF decorator pattern.
 * 
 * This base class ensure that, by default, wrappers behave in the same way as the report that is
 * being wrapped.  You should override any methods that need to behave differently in your subclass
 * of SS_ReportWrapper.
 * 
 * It also makes calls to 2 empty methods that you can override {@link beforeQuery()} and
 * {@link afterQuery()}
 * 
 * @package cms
 * @subpackage reports
 */
abstract class SS_ReportWrapper extends SS_Report {
	protected $baseReport;

	function __construct($baseReport) {
		$this->baseReport = is_string($baseReport) ? new $baseReport : $baseReport;
		$this->dataClass = $this->baseReport->dataClass();
		parent::__construct();
	}

	function ID() {
		return get_class($this->baseReport) . '_' . get_class($this);
	}

	///////////////////////////////////////////////////////////////////////////////////////////
	// Filtering

	function parameterFields() {
		return $this->baseReport->parameterFields();
	}

	///////////////////////////////////////////////////////////////////////////////////////////
	// Columns

	function columns() {
		return $this->baseReport->columns();
	}

	///////////////////////////////////////////////////////////////////////////////////////////
	// Querying

	/**
	 * Override this method to perform some actions prior to querying.
	 */
	function beforeQuery($params) {
	}

	/**
	 * Override this method to perform some actions after querying.
	 */
	function afterQuery() {
	}

	function sourceQuery($params) {
		if($this->baseReport->hasMethod('sourceRecords')) {
			// The default implementation will create a fake query from our sourceRecords() method
			return parent::sourceQuery($params);

		} else if($this->baseReport->hasMethod('sourceQuery')) {
			$this->beforeQuery($params);
			$query = $this->baseReport->sourceQuery($params);
			$this->afterQuery();
			return $query;

		} else {
			user_error("Please override sourceQuery()/sourceRecords() and columns() in your base report", E_USER_ERROR);
		}

	}

	function sourceRecords($params = array(), $sort = null, $limit = null) {
		$this->beforeQuery($params);
		$records = $this->baseReport->sourceRecords($params, $sort, $limit);
		$this->afterQuery();
		return $records;
	}


	///////////////////////////////////////////////////////////////////////////////////////////
	// Pass-through

	function title() {
		return $this->baseReport->title();
	}
	
	function group() {
		return $this->baseReport->hasMethod('group') ? $this->baseReport->group() : 'Group';
	}
	
	function sort() {
		return $this->baseReport->hasMethod('sort') ? $this->baseReport->sort() : 0;
	}

	function description() {
		return $this->baseReport->title();
	}

	function canView() {
		return $this->baseReport->canView();
	}
	
}

?>
