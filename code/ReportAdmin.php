<?php

/**
 * @package cms
 * @subpackage
 */

/**
 * Reports section of the CMS
 */
class ReportAdmin extends LeftAndMain {
	static $subitem_class = "GrantObject";
	
	static $template_path = null; // defaults to (project)/templates/email
	
	public function init() {
		parent::init();

		Requirements::javascript(MCE_ROOT . "tiny_mce_src.js");
		Requirements::javascript("jsparty/tiny_mce_improvements.js");

		Requirements::javascript("jsparty/hover.js");
		Requirements::javascript("jsparty/scriptaculous/controls.js");
		
		Requirements::javascript("cms/javascript/SecurityAdmin.js");
        
    	Requirements::javascript("cms/javascript/LeftAndMain_left.js");
		Requirements::javascript("cms/javascript/LeftAndMain_right.js");
    	Requirements::javascript("cms/javascript/CMSMain_left.js");
        

		Requirements::javascript("cms/javascript/ReportAdmin_left.js");
		Requirements::javascript("cms/javascript/ReportAdmin_right.js");
		
		Requirements::css("cms/css/ReportAdmin.css");		
		
		// TODO Find a better solution to integrate optional Requirements in a specific order
		if(Director::fileExists("ecommerce/css/DataReportCMSMain.css")) {
			Requirements::css("ecommerce/css/DataReportCMSMain.css");		
		}
		if(Director::fileExists("ecommerce/css/DataReportCMSMain.css")) {
			Requirements::javascript("ecommerce/javascript/DataReport.js");		
		}
		if(Director::fileExists(project() . "/css/DataReportCMSMain.css")) {
			Requirements::css(project() . "/css/DataReportCMSMain.css");		
		}
		if(Director::fileExists(project() . "/css/DataReportCMSMain.css")) {
			Requirements::javascript(project() . "/javascript/DataReport.js");		
		}

		// We don't want this showing up in every ajax-response, it should always be present in a CMS-environment
		if(!Director::is_ajax()) {
			Requirements::javascriptTemplate("cms/javascript/tinymce.template.js", array(
				"ContentCSS" => project() . "/css/editor.css",
				"BaseURL" => Director::absoluteBaseURL(),
				"Lang" => i18n::get_tinymce_lang()
			));
		}
	}
	
	public function Link($action = null) {
		return "admin/reports/$action";
	}
	
	public function Reports(){
		$allReports= ClassInfo::subclassesFor("Report");
		foreach($allReports as $report) {
			if($report != 'Report') $processedReports[] = new $report();
		}
		
		$reports = new DataObjectSet($processedReports);
		return $reports;
	}
	
	public function showreport($params) {
	    return $this->showWithEditForm( $params, $this->getReportEditForm( $params['ID'] ) );	
	}

	protected function showWithEditForm( $params, $editForm ) {
		if($params['ID']) {
			Session::set('currentPage', $params['ID']);
		}
		if($params['OtherID']) {
			Session::set('currentOtherID', $params['OtherID']);
		}

		if($_REQUEST['ajax']) {
			SSViewer::setOption('rewriteHashlinks', false);
			$result = $this->customise( array( 'EditForm' => $editForm ) )->renderWith($this->getTemplatesWithSuffix("_right"));
			return $this->getLastFormIn($result);
		} else {
			return array();
		}
  }
  
  public function EditForm() {
		$id = $_REQUEST['ID'] ? $_REQUEST['ID'] : Session::get('currentPage');
		
		$subclasses = ClassInfo::subclassesFor('Report');
		
		foreach($subclasses as $class){
			if($class != 'Report') {
				$obj = new $class();
				$ids[] = $obj->getOwnerID();
			}
		}

	 	// bdc: do we have any subclasses?
	    if(sizeof($ids) > 0){
				if($id && in_array($id, $ids)) return $this->getReportEditForm($id);
	    }
	    else {
				return null;	    	
	    }
	   
	}
  
  public function getReportEditForm($id){
  	if(is_numeric($id))
  		$page = DataObject::get_by_id("SiteTree", $id);
		if($page) $reportClass = "Report_".$page->ClassName;
		
		if(!$reportClass)
			$reportClass = $id;
		
  	$obj = new $reportClass();
		$fields = $obj->getCMSFields();
			
		$fields->push($idField = new HiddenField("ID"));
		$idField->setValue($id);
		
		//$actions = new FieldSet(new FormAction('exporttocsv', 'Export to CVS'));
		$actions = new FieldSet();
		$form = new Form($this, "EditForm", $fields, $actions);

		return $form;
	}
}

?>
