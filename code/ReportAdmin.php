<?php
/**
 * Reports section of the CMS.
 * 
 * All reports that should show in the ReportAdmin section
 * of the CMS need to subclass {@link SSReport}, and implement
 * the appropriate methods and variables that are required.
 * 
 * @see SSReport
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
		if(!$member && $member !== FALSE) {
			$member = Member::currentUser();
		}
		
		if(!parent::canView($member)) return false;
		
		$hasViewableSubclasses = false;
		$subClasses = array_values(ClassInfo::subclassesFor('SSReport'));
		foreach($subClasses as $subclass) {
			// Remove abstract classes and LeftAndMain
			$classReflection = new ReflectionClass($subclass);
			if($classReflection->isInstantiable() && $subclass != 'SSReport') {
				if(singleton($subclass)->canView()) $hasViewableSubclasses = true;
			}			
		}
		
		return $hasViewableSubclasses;
	}
	
	/**
	 * Return a DataObjectSet of SSReport subclasses
	 * that are available for use.
	 *
	 * @return DataObjectSet
	 */
	public function Reports() {
		$processedReports = array();
		$subClasses = ClassInfo::subclassesFor('SSReport');
		
		if($subClasses) {
			foreach($subClasses as $subClass) {
				if($subClass != 'SSReport') {
					$processedReports[] = new $subClass();
				}
			}
		}
		
		$reports = new DataObjectSet($processedReports);
		
		return $reports;
	}
	
	/**
	 * Show a report based on the URL query string.
	 *
	 * @param HTTPRequest $request The HTTP request object
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
		if(isset($params['ID'])) Session::set('currentPage', $params['ID']);
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
		$ids = array();
		$id = isset($_REQUEST['ID']) ? $_REQUEST['ID'] : Session::get('currentPage');
		$subClasses = ClassInfo::subclassesFor('SSReport');
		
		if($subClasses) {
			foreach($subClasses as $subClass) {
				if($subClass != 'SSReport') {
					$obj = new $subClass();
					$ids[] = $obj->ID();
				}
			}
		}
		
		if($id && in_array($id, $ids)) return $this->reportEditFormFor($id);
		else return false;
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
		
		if(is_numeric($id)) $page = DataObject::get_by_id('SiteTree', $id);
		$reportClass = is_object($page) ? 'SSReport_' . $page->ClassName : $id;
		
		$obj = new $reportClass();
		if($obj) $fields = $obj->getCMSFields();
		
		$idField = new HiddenField('ID');
		$idField->setValue($id);
		$fields->push($idField);
		
		$form = new Form($this, 'EditForm', $fields, $actions);
		
		return $form;
	}
	
	/**
	 * Determine if we have reports and need
	 * to display the "Reports" main menu item
	 * in the CMS.
	 * 
	 * The test for an existance of a report
	 * is done by checking for a subclass of
	 * "SSReport" that exists.
	 *
	 * @return boolean
	 */
	public static function has_reports() {
		$subClasses = ClassInfo::subclassesFor('SSReport');
		
		if($subClasses) {
			foreach($subClasses as $subClass) {
				if($subClass != 'SSReport') {
					return true;
				}
			}
		}
		
		return false;
	}
}

?>