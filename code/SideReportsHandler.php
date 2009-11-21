<?php
/**
 * @package cms
 * @subpackage reports
 */
class SideReportsHandler extends RequestHandler {	
	static $url_handlers = array(
		'$Action' => 'handleAction'
	);
	
	protected $parentController;
	
	/**
	 * @var String
	 */
	protected $urlSegment;
	
	/**
	 * @param string $parentController
	 * @param string $urlSegment
	 * @param string $recordClass
	 */
	function __construct($parentController, $urlSegment, $recordClass = null) {
		$this->parentController = $parentController;
		$this->urlSegment = $urlSegment;
		if($recordClass) $this->recordClass = $recordClass;
		
		parent::__construct();
	}
	
	function Link() {
		return Controller::join_links($this->parentController->Link(), $this->urlSegment);
	}

	function handleAction($request) {
		// This method can't be called without ajax.
		if(!Director::is_ajax()) return Director::redirectBack();

		$form = $this->getForm($request->requestVar("ReportClass"), $request->requestVars());
		// TODO Accept custom actions
		return $form->forTemplate();
	} 
	
	/**
	 * @param String $reportClass
	 * @param Array $data
	 * @return Form
	 */
	function getForm($reportClass, $data) {
		$report = new $reportClass();

		$fields = $report->getParameterFields();
		if(!$fields) $fields = new FieldSet();
		$fields->push(new LiteralField('ReportHtml', $report->getHTML()));
		$fields->push(new HiddenField('ReportClass', null, $reportClass));
		$fields->push(new HiddenField('ID', false, (isset($data['ID'])) ? $data['ID'] : null));
		$fields->push(new HiddenField('Locale', false, (isset($data['Locale'])) ? $data['Locale'] : null));
		
		$form = new Form(
			$this,
			'ReportForm',
			$fields,
			new FieldSet(
				new FormAction('sidereport', _t('CMSMain_left.ss.REFRESH','Refresh'))
			)
		);
		$form->setFormAction($this->Link());
		$form->unsetValidator();
		$form->setFormMethod('GET');
		$form->loadDataFrom($data);
		
		return $form;
	}
	
	/**
	 * Returns all viewable subclasses of {@link SideReport}
	 * 
	 * @return array
	 */
	function getReportClasses() {
		$classes = ClassInfo::subclassesFor("SideReport");
		foreach($classes as $i => $class) {
			if($class != 'SideReport') $report = singleton($class);
			if(
				$class == 'SideReport' 
				|| ($report && !$report->canView())
			) {
				unset($classes[$i]);
			} 
		}
		
		return $classes;
	}
}