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
class ReportAdmin extends LeftAndMain {
	
	static $url_segment = 'reports';
	
	static $url_rule = '/$Action/$ID';
	
	static $menu_title = 'Reports';	
	
	static $template_path = null; // defaults to (project)/templates/email
	
	public function init() {
		parent::init();
		
		Requirements::css(CMS_DIR . '/css/ReportAdmin.css');		
		
		// Set custom options for TinyMCE specific to ReportAdmin
		HtmlEditorConfig::get('cms')->setOption('ContentCSS', project() . '/css/editor.css');
		HtmlEditorConfig::get('cms')->setOption('Lang', i18n::get_tinymce_lang());
		
		// Always block the HtmlEditorField.js otherwise it will be sent with an ajax request
		Requirements::block(SAPPHIRE_DIR . '/javascript/HtmlEditorField.js');
		Requirements::javascript(CMS_DIR . '/javascript/ReportAdmin.Tree.js');
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
	 * Return a DataObjectSet of SS_Report subclasses
	 * that are available for use.
	 *
	 * @return DataObjectSet
	 */
	public function Reports() {
 		$output = new DataObjectSet();
		foreach(SS_Report::get_reports('ReportAdmin') as $report) {
			if($report->canView()) $output->push($report);
		}
		return $output;
	}
	
	/**
	 * Get the current report
	 *
	 * @return SSReport
	 */
	public function CurrentReport() {
		$id = isset($_REQUEST['ID']) ? $_REQUEST['ID'] : Session::get('currentReport');
		
		if($id) {
			foreach($this->Reports() as $report) {
				if($id == $report->ID()) return $report;
			}
		}
		return false;
	}
	
	/**
	 * Return a Form instance with fields for the
	 * particular report currently viewed.
	 * 
	 * @param string $className Class of the report to fetch
	 * @return Form
	 */
	public function getEditForm($className = null) {
		if (!$className) {
			return $form = $this->EmptyForm();
		}
		
		Session::set('currentPage', $className);
		
		$fields = new FieldSet();
		$actions = new FieldSet();
		
		$reports = SS_Report::get_reports('ReportAdmin');
		if(!isset($reports[$className])) return false;

		$report = $reports[$className];		
		if(!$report || !$report->canView()) return Security::permissionFailure($this);

		$fields = $report->getCMSFields();
		$actions = $report->getCMSActions();
		
		$idField = new HiddenField('ID');
		$idField->setValue($id);
		$fields->push($idField);
		
		$form = new Form($this, 'EditForm', $fields, $actions);

		// Include search criteria in the form action so that pagination works
		$filteredCriteria = array_merge($_GET, $_POST);
		foreach(array('ID','url','ajax','ctf','update','action_updatereport','SecurityID') as $notAParam) {
			unset($filteredCriteria[$notAParam]);
		}

		$formLink = $this->Link() . '/EditForm';
		if($filteredCriteria) $formLink .= '?' . http_build_query($filteredCriteria);
		$form->setFormAction($formLink);
		$form->setTemplate('ReportAdminForm');
		$form->loadDataFrom($this->request->requestVars());

		return $form;
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
		return sizeof(SS_Report::get_reports('ReportAdmin')) > 0;
	}
}

?>
