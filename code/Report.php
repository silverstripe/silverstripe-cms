<?php
/**
 * Base "abstract" class for all Report classes
 * viewable in the Reports top menu section of CMS.
 */
class Report extends ViewableData {

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
	 * method should be defined on the Report subclasses.
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

		$this->extend('augmentReportCMSFields', $fields);

		return $fields;
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