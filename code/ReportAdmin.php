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
		
		Requirements::javascript(CMS_DIR . '/javascript/ReportAdmin_left.js');
		Requirements::javascript(CMS_DIR . '/javascript/ReportAdmin_right.js');

		Requirements::css(CMS_DIR . '/css/ReportAdmin.css');		
		
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
	 * Show a report based on the URL query string.
	 *
	 * @param SS_HTTPRequest $request The HTTP request object
	 */
	public function show($request) {
		$params = $request->allParams();
		
		return $this->showWithEditForm($params, $this->reportEditFormFor($params['ID']));	
	}

	/**
	 * @TODO What does this do?
	 *
	 * @param unknown_type $params
	 * @param unknown_type $editForm
	 * @return unknown
	 */
	protected function showWithEditForm($params, $editForm) {
		if(isset($params['ID'])) Session::set('currentReport', $params['ID']);
		if(isset($params['OtherID'])) Session::set('currentOtherID', $params['OtherID']);
		
		if(Director::is_ajax()) {
			SSViewer::setOption('rewriteHashlinks', false);
			
			$result = $this->customise(array(
				'EditForm' => $editForm
			))->renderWith($this->getTemplatesWithSuffix('_right'));
						
			return $this->getLastFormIn($result);
		}
		
		return array();
	}
	
	/**
	 * For the current report that the user is viewing,
	 * return a Form instance with the fields for that
	 * report.
	 *
	 * @return Form
	 */
	public function EditForm() {
		// Return the report if the ID is sent by request, or we're specifically asking for the edit form
		$id = isset($_REQUEST['ID']) ? $_REQUEST['ID'] : ($this->getRequest()->latestParam('Action') == 'EditForm') ? Session::get('currentReport') : null;
		
		if($id) {
			foreach($this->Reports() as $report) {
				if($id == $report->ID()) return $this->reportEditFormFor($id);
			}
		}
		return false;
	}
	
	/**
	 * Get the current report
	 *
	 * @return SS_Report
	 */
	public function CurrentReport() {
		$id = isset($_REQUEST['ID']) ? $_REQUEST['ID'] : ($this->getRequest()->latestParam('Action') == 'EditForm') ? Session::get('currentReport') : null;
		
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
	 * @TODO Dealing with multiple data types for the
	 * $id parameter is confusing. Ideally, it should
	 * deal with only one.
	 *
	 * @param id|string $id The ID of the report, or class name
	 * @return Form
	 */
	public function reportEditFormFor($id) {
		$page = false;
		$fields = new FieldSet();
		$actions = new FieldSet();
		
		$reports = SS_Report::get_reports('ReportAdmin');
		$obj = $reports[$id];

		if($obj) $fields = $obj->getCMSFields();
		if($obj) $actions = $obj->getCMSActions();
		
		$idField = new HiddenField('ID');
		$idField->setValue($id);
		$fields->push($idField);
		
		$form = new Form($this, 'EditForm', $fields, $actions);

		$form->loadDataFrom($_REQUEST);

		// Include search criteria in the form action so that pagination works
		$filteredCriteria = array_merge($_GET, $_POST);
		foreach(array('ID','url','ajax','ctf','update','action_updatereport','SecurityID') as $notAParam) {
			unset($filteredCriteria[$notAParam]);
		}

		$formLink = $this->Link() . '/EditForm';
		if($filteredCriteria) $formLink .= '?' . http_build_query($filteredCriteria);
		$form->setFormAction($formLink);
		$form->setTemplate('ReportAdminForm');
		
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
	
	public function updatereport() {
		FormResponse::load_form($this->EditForm()->forTemplate());
		return FormResponse::respond();
	}
}





?>
