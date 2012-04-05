<?php
/**
 * Reports section of the CMS.
 * 
 * All reports that should show in the ReportAdmin section
 * of the CMS need to subclass {@link SS_Report}, and implement
 * the appropriate methods and variables that are required.
 * 
 * @see SS_Report
 * 
 * @package cms
 * @subpackage reports
 */
class ReportAdmin extends LeftAndMain implements PermissionProvider {
	
	static $url_segment = 'reports';
	
	static $url_rule = '/$Action/$ID';
	
	static $menu_title = 'Reports';	
	
	static $template_path = null; // defaults to (project)/templates/email
	
	static $tree_class = 'SS_Report';

	/**
	 * Variable that describes which report we are currently viewing based on the URL (gets set in init method)
	 * @var String
	 */
	protected $reportClass;

	protected $reportObject;
	
	public function init() {
		parent::init();

		//set the report we are currently viewing from the URL
		$this->reportClass = (isset($this->urlParams['ID'])) ? $this->urlParams['ID'] : null;
		$allReports = SS_Report::get_reports();
		$this->reportObject = (isset($allReports[$this->reportClass])) ? $allReports[$this->reportClass] : null;

		Requirements::css(CMS_DIR . '/css/screen.css');

		// Set custom options for TinyMCE specific to ReportAdmin
		HtmlEditorConfig::get('cms')->setOption('ContentCSS', project() . '/css/editor.css');
		HtmlEditorConfig::get('cms')->setOption('Lang', i18n::get_tinymce_lang());

		// Always block the HtmlEditorField.js otherwise it will be sent with an ajax request
		Requirements::block(SAPPHIRE_DIR . '/javascript/HtmlEditorField.js');
	}

	/**
	 * Does the parent permission checks, but also
	 * makes sure that instantiatable subclasses of
	 * {@link Report} exist. By default, the CMS doesn't
	 * include any Reports, so there's no point in showing
	 *
	 * @param Member $member
	 * @return boolean
	 */
	function canView($member = null) {
		if(!$member && $member !== FALSE) $member = Member::currentUser();

		if(!parent::canView($member)) return false;

		$hasViewableSubclasses = false;
		foreach($this->Reports() as $report) {
			if($report->canView($member)) return true;
		}

		return false;
	}

	/**
	 * Return a SS_List of SS_Report subclasses
	 * that are available for use.
	 *
	 * @return SS_List
	 */
	public function Reports() {
 		$output = new ArrayList();
		foreach(SS_Report::get_reports() as $report) {
			if($report->canView()) $output->push($report);
		}
		return $output;
	}

	/**
	 * Determine if we have reports and need
	 * to display the "Reports" main menu item
	 * in the CMS.
	 *
	 * The test for an existance of a report
	 * is done by checking for a subclass of
	 * "SS_Report" that exists.
	 *
	 * @return boolean
	 */
	public static function has_reports() {
		return sizeof(SS_Report::get_reports()) > 0;
	}
	
	public function updatereport() {
		// FormResponse::load_form($this->EditForm()->forTemplate());
		// return FormResponse::respond();
	}

	function providePermissions() {
		$title = _t("ReportAdmin.MENUTITLE", LeftAndMain::menu_title_for_class($this->class));
		return array(
			"CMS_ACCESS_ReportAdmin" => array(
				'name' => sprintf(_t('CMSMain.ACCESS', "Access to '%s' section"), $title),
				'category' => _t('Permission.CMS_ACCESS_CATEGORY', 'CMS Access')
			)
		);
	}

	public function getEditForm($id = null, $fields = null) {
		$fields = new FieldList();
		
		$report = $this->reportObject;

		if($report) {
			// List all reports
			$gridFieldConfig = GridFieldConfig::create()->addComponents(
				new GridFieldToolbarHeader(),
				new GridFieldSortableHeader(),
				new GridFieldDataColumns(),
				new GridFieldPaginator(),
				new GridFieldPrintButton(),
				new GridFieldExportButton()
			);
			$gridField = new GridField('Report',$report->title(), $report->sourceRecords(array(), null, null), $gridFieldConfig);
			$displayFields = array();
			$fieldCasting = array();
			$fieldFormatting = array();
			
			// Parse the column information
			foreach($report->columns() as $source => $info) {
				if(is_string($info)) $info = array('title' => $info);
				
				if(isset($info['formatting'])) $fieldFormatting[$source] = $info['formatting'];
				if(isset($info['csvFormatting'])) $csvFieldFormatting[$source] = $info['csvFormatting'];
				if(isset($info['casting'])) $fieldCasting[$source] = $info['casting'];

				$displayFields[$source] = isset($info['title']) ? $info['title'] : $source;
			}
			$gridField->setDisplayFields($displayFields);
			$gridField->setFieldCasting($fieldCasting);
			$gridField->setFieldFormatting($fieldFormatting);

			$fields->push($gridField);
		} else {
			// List all reports
			$gridFieldConfig = GridFieldConfig::create()->addComponents(
				new GridFieldToolbarHeader(),
				new GridFieldSortableHeader(),
				new GridFieldDataColumns()
			);
			$gridField = new GridField('Reports','Reports', $this->Reports(), $gridFieldConfig);
			$gridField->setDisplayFields(array(
				'title' => 'Title',
				'description' => 'Description'
			));
			$gridField->setFieldFormatting(array(
				'title' => '<a href=\"$Link\">$value</a>'
			));
			$fields->push($gridField);
		}

		$actions = new FieldList();
		$form = new Form($this, "EditForm", $fields, $actions);
		$form->addExtraClass('cms-edit-form cms-panel-padded center ' . $this->BaseCSSClasses());

		$this->extend('updateEditForm', $form);

		return $form;
	}
}

