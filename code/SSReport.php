<?php
/**
 * Base "abstract" class for all Report classes
 * viewable in the Reports top menu section of CMS.
 * 
 * To include your own report into the ReportAdmin
 * of the CMS, your subclass of SSReport should
 * overload these:
 * 
 * @link SSReport::$title
 * @link SSReport::$description
 * @link SSReport->getReportField()
 * 
 * getReportField() should return a FormField instance,
 * such as a ComplexTableField, or TableListField. This
 * is the "meat" of the report, as it's designed to
 * show the actual data for the function of the report.
 * For example, if this was a report that should show
 * all orders that aren't printed, then it would show
 * a TableListField listing orders that have the property
 * "Unprinted = 1".
 * 
 * @see ReportAdmin for where SSReport instances are
 * used in the CMS.
 * 
 * @package cms
 * @subpackage reports
 */
class SSReport extends ViewableData {

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
	 * Returns a FieldSet with which to create the CMS editing form.
	 * You can use the extend() method of FieldSet to create customised forms for your other
	 * data objects.
	 *
	 * @uses getReportField() to render a table, or similar field for the report. This
	 * method should be defined on the SSReport subclasses.
	 *
	 * @return FieldSet
	 */
	function getCMSFields() {
		$fields = new FieldSet(
			new TabSet('Root',
				new Tab('Report',
					new LiteralField('ReportTitle', "<h3>{$this->title}</h3>"),
					new LiteralField('ReportDescription', "<p>{$this->description}</p>"),
					$this->getReportField()
				)
			)
		);

		return $fields;
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
	 * Return a field, such as a {@link ComplexTableField} that is
	 * used to show and manipulate data relating to this report.
	 *
	 * For example, if this were an "Unprinted Orders" report, this
	 * field would return a table that shows all Orders with "Unprinted = 1".
	 *
	 * @return FormField subclass
	 */
	function getReportField() {
		user_error('Please implement getReportField() on ' . $this->class, E_USER_ERROR);
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
		return $this->title;
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

}

?>