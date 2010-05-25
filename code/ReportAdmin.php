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
	* Returns an array of all the reports classnames that could be shown on this site 
	* to any user. Base class is not included in the response.
	* It does not perform filtering based on canView(). 
	*
	* @return An array of report class-names, i.e.:
	*         array("AllOrdersReport","CurrentOrdersReport","UnprintedOrderReport")
	*/
	public function getReportClassNames() {

		$baseClass  = 'SS_Report';		
		$response   = array();
		
		// get all sub-classnames (incl. base classname).
		$classNames = ClassInfo::subclassesFor( $baseClass );
		
		// drop base className
		$classNames = array_diff($classNames, array($baseClass));
		
		// drop report classes, which are not initiatable.
		foreach($classNames as $className) {
			
			// Remove abstract classes
			$classReflection = new ReflectionClass($className);
			if($classReflection->isInstantiable() ) {
				$response[] = $className;
			}			
		}
		return $response;
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
		$subClasses = array_values( $this->getReportClassNames() );

		foreach($subClasses as $subclass) {

			if(singleton($subclass)->canView()) {
				$hasViewableSubclasses = true;
			}
				
		}
		return $hasViewableSubclasses;
	}
	
	/**
	 * Return a DataObjectSet of SS_Report subclasses
	 * that are available for use.
	 *
	 * @return DataObjectSet
	 */
	public function Reports() {
		$processedReports = array();
		$subClasses = $this->getReportClassNames();
		
		if($subClasses) {
			foreach($subClasses as $subClass) {
				$processedReports[] = new $subClass();
			}
		}
		$reports = new DataObjectSet($processedReports);
		
		return $reports;
	}
	
	/**
	 * Get EditForm for the class specified in request or in session variable
	 *
	 * @param HTTPRequest
	 * @return Form
	 */
	public function EditForm($request = null) {
		$className = Session::get('currentPage');
		$requestId = $this->getRequest()->requestVar('ID');
		if(!$requestId) $requestId = $this->getRequest()->latestParam('ID');

		if ( $requestId )
			return $this->getEditForm($requestId);
		
		// $className can be null
		return $this->getEditForm($className);

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
		
		if (!class_exists($className)) {
			die("$className does not exist");
		}

		Session::set('currentPage', $className);

		$obj = new $className();
		if(!$obj->canView()) return Security::permissionFailure($this);

		$fields = $obj->getCMSFields();

		$idField = new HiddenField('ID');
		$idField->setValue($className);
		$fields->push($idField);

		$actions = $obj->getCMSActions();

		$form = new Form($this, 'EditForm', $fields, $actions);
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
		$subClasses = $this->getReportClassNames();
		if($subClasses) {
			foreach($subClasses as $subClass) {
				return true;
			}
		}
		return false;
	}
}

?>
