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
 *  {@link sourceQuery()}: Return a SS_List of the search results
 *  {@link columns()}: Return information about the columns in this report.
 *  {@link parameterFields()}: Return a FieldList of the fields that can be used to filter this
 *  report.
 * 
 * If you wish to modify the report in more extreme ways, you could overload these methods instead.
 * 
 * {@link getReportField()}: Return a FormField in the place where your report's TableListField
 * usually appears.
 * {@link getCMSFields()}: Return the FieldList representing the complete right-hand area of the 
 * report, including the title, description, parameter fields, and results.
 * 
 * Showing reports to the user
 * ===========================
 * 
 * Right now, all subclasses of SS_Report will be shown in the ReportAdmin. In SS3 there is only
 * one place where reports can go, so this class is greatly simplifed from from its version in SS2.
 * 
 * @package cms
 * @subpackage reports
 */
class SS_Report extends ViewableData {
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
	 * A field that specifies the sort order of this report
	 * @var int
	 */
	protected $sort = 0;

	/**
	 * Reports which should not be collected and returned in get_reports
	 * @var array
	 * @config
	 */
	private static $excluded_reports = array(
		'SS_Report',
		'SS_ReportWrapper',
		'SideReportWrapper'
	);
	
	/**
	 * Return the title of this report.
	 * 
	 * You have two ways of specifying the description:
	 *  - overriding description(), which lets you support i18n 
	 *  - defining the $description property
	 */
	public function title() {
		return $this->title;
	}
	
	/**
	 * Return the description of this report.
	 * 
	 * You have two ways of specifying the description:
	 *  - overriding description(), which lets you support i18n 
	 *  - defining the $description property
	 */
	public function description() {
		return $this->description;
	}
	
	/**
	 * Return the {@link SQLQuery} that provides your report data.
	 */
	public function sourceQuery($params) {
		if($this->hasMethod('sourceRecords')) {
			return $this->sourceRecords()->dataQuery();
		} else {
			user_error("Please override sourceQuery()/sourceRecords() and columns() or, if necessary, override getReportField()", E_USER_ERROR);
		}
	}
	
	/**
	 * Return a SS_List records for this report.
	 */
	public function records($params) {
		if($this->hasMethod('sourceRecords')) {
			return $this->sourceRecords($params, null, null);
		} else {
			$query = $this->sourceQuery();
			$results = new ArrayList();
			foreach($query->execute() as $data) {
				$class = $this->dataClass();
				$result = new $class($data);
				$results->push($result);
			}
			return $results;
		}
	}

	/**
	 * Return the data class for this report
	 */
	public function dataClass() {
		return $this->dataClass;
	}

	public function getLink($action = null) {
		return Controller::join_links(
			'admin/reports/',
			"$this->class",
			'/', // trailing slash needed if $action is null!
			"$action"
		);
	}

	/**
	 * Exclude certain reports classes from the list of Reports in the CMS
	 * @param $reportClass Can be either a string with the report classname or an array of reports classnames
	 */
	static public function add_excluded_reports($reportClass) {
		if (is_array($reportClass)) {
			self::config()->excluded_reports = array_merge(self::config()->excluded_reports, $reportClass);
		} else {
			if (is_string($reportClass)) {
				//add to the excluded reports, so this report doesn't get used
				self::config()->excluded_reports = array($reportClass);
			}
		}
	}

	/**
	 * Return an array of excluded reports. That is, reports that will not be included in
	 * the list of reports in report admin in the CMS.
	 *
	 * @deprecated 3.2 Use the "Report.excluded_reports" config setting instead
	 * @return array
	 */
	static public function get_excluded_reports() {
		Deprecation::notice('3.2', 'Use the "Report.excluded_reports" config setting instead');
		return self::config()->excluded_reports;
	}

	/**
	 * Return the SS_Report objects making up the given list.
	 * @return Array of SS_Report objects
	 */
	static public function get_reports() {
		$reports = ClassInfo::subclassesFor(get_called_class());

		$reportsArray = array();
		if ($reports && count($reports) > 0) {
			//collect reports into array with an attribute for 'sort'
			foreach($reports as $report) {
				if (in_array($report, self::config()->excluded_reports)) continue;   //don't use the SS_Report superclass
				$reflectionClass = new ReflectionClass($report);
				if ($reflectionClass->isAbstract()) continue;   //don't use abstract classes

				$reportObj = new $report;
				if (method_exists($reportObj,'sort')) $reportObj->sort = $reportObj->sort();  //use the sort method to specify the sort field
				$reportsArray[$report] = $reportObj;
			}
		}

		uasort($reportsArray, function($a, $b) {
			if($a->sort == $b->sort) return 0;
			else return ($a->sort < $b->sort) ? -1 : 1;
		});

		return $reportsArray;
	}

	/////////////////////// UI METHODS ///////////////////////


	/**
	 * Returns a FieldList with which to create the CMS editing form.
	 * You can use the extend() method of FieldList to create customised forms for your other
	 * data objects.
	 *
	 * @uses getReportField() to render a table, or similar field for the report. This
	 * method should be defined on the SS_Report subclasses.
	 *
	 * @return FieldList
	 */
	public function getCMSFields() {
		$fields = new FieldList();

		if($title = $this->title()) {
			$fields->push(new LiteralField('ReportTitle', "<h3>{$title}</h3>"));
		}
		
		if($description = $this->description()) {
			$fields->push(new LiteralField('ReportDescription', "<p>" . $description . "</p>"));
		}
			
		// Add search fields is available
		if($this->hasMethod('parameterFields') && $fields = $this->parameterFields()) {
			foreach($fields as $field) {
				// Namespace fields for easier handling in form submissions
				$field->setName(sprintf('filters[%s]', $field->getName()));
				$field->addExtraClass('no-change-track'); // ignore in changetracker
				$fields->push($field);
			}

			// Add a search button
			$fields->push(new FormAction('updatereport', _t('GridField.Filter')));
		}
		
		$fields->push($this->getReportField());

		$this->extend('updateCMSFields', $fields);
		
		return $fields;
	}
	
	public function getCMSActions() {
		// getCMSActions() can be extended with updateCMSActions() on a extension
		$actions = new FieldList();
		$this->extend('updateCMSActions', $actions);
		return $actions;
	}
	
	/**
	 * Return a field, such as a {@link GridField} that is
	 * used to show and manipulate data relating to this report.
	 * 
	 * Generally, you should override {@link columns()} and {@link records()} to make your report,
	 * but if they aren't sufficiently flexible, then you can override this method.
	 *
	 * @return FormField subclass
	 */
	public function getReportField() {
		// TODO Remove coupling with global state
		$params = isset($_REQUEST['filters']) ? $_REQUEST['filters'] : array();
		$items = $this->sourceRecords($params, null, null);

		$gridFieldConfig = GridFieldConfig::create()->addComponents(
			new GridFieldToolbarHeader(),
			new GridFieldSortableHeader(),
			new GridFieldDataColumns(),
			new GridFieldPaginator(),
			new GridFieldButtonRow('after'),
			new GridFieldPrintButton('buttons-after-left'),
			new GridFieldExportButton('buttons-after-left')
		);
		$gridField = new GridField('Report',false, $items, $gridFieldConfig);
		$columns = $gridField->getConfig()->getComponentByType('GridFieldDataColumns');
		$displayFields = array();
		$fieldCasting = array();
		$fieldFormatting = array();

		// Parse the column information
		foreach($this->columns() as $source => $info) {
			if(is_string($info)) $info = array('title' => $info);
			
			if(isset($info['formatting'])) $fieldFormatting[$source] = $info['formatting'];
			if(isset($info['csvFormatting'])) $csvFieldFormatting[$source] = $info['csvFormatting'];
			if(isset($info['casting'])) $fieldCasting[$source] = $info['casting'];

			if(isset($info['link']) && $info['link']) {
				$fieldFormatting[$source] = function($value, &$item) {
					return sprintf(
						'<a href="%s">%s</a>',
						Controller::join_links(singleton('CMSPageEditController')->Link('show'), $item->ID),
						Convert::raw2xml($value)
					);
				};
			}

			$displayFields[$source] = isset($info['title']) ? $info['title'] : $source;
		}
		$columns->setDisplayFields($displayFields);
		$columns->setFieldCasting($fieldCasting);
		$columns->setFieldFormatting($fieldFormatting);

		return $gridField;
	}
	
	/**
	 * @param Member $member
	 * @return boolean
	 */
	public function canView($member = null) {
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
	public function TreeTitle() {
		return $this->title();
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

	public function __construct($baseReport) {
		$this->baseReport = is_string($baseReport) ? new $baseReport : $baseReport;
		$this->dataClass = $this->baseReport->dataClass();
		parent::__construct();
	}

	public function ID() {
		return get_class($this->baseReport) . '_' . get_class($this);
	}

	///////////////////////////////////////////////////////////////////////////////////////////
	// Filtering

	public function parameterFields() {
		return $this->baseReport->parameterFields();
	}

	///////////////////////////////////////////////////////////////////////////////////////////
	// Columns

	public function columns() {
		return $this->baseReport->columns();
	}

	///////////////////////////////////////////////////////////////////////////////////////////
	// Querying

	/**
	 * Override this method to perform some actions prior to querying.
	 */
	public function beforeQuery($params) {
	}

	/**
	 * Override this method to perform some actions after querying.
	 */
	public function afterQuery() {
	}

	public function sourceQuery($params) {
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

	public function sourceRecords($params = array(), $sort = null, $limit = null) {
		$this->beforeQuery($params);
		$records = $this->baseReport->sourceRecords($params, $sort, $limit);
		$this->afterQuery();
		return $records;
	}


	///////////////////////////////////////////////////////////////////////////////////////////
	// Pass-through

	public function title() {
		return $this->baseReport->title();
	}
	
	public function group() {
		return $this->baseReport->hasMethod('group') ? $this->baseReport->group() : 'Group';
	}
	
	public function sort() {
		return $this->baseReport->hasMethod('sort') ? $this->baseReport->sort() : 0;
	}

	public function description() {
		return $this->baseReport->description();
	}

	public function canView($member = null) {
		return $this->baseReport->canView($member);
	}
	
}


