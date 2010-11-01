<?php
/**
 * Special request handler for admin/batchaction
 *  
 * @package cms
 * @subpackage batchaction
 */
class CMSBatchActionHandler extends RequestHandler {
	static $batch_actions = array(
		'publish' => 'CMSBatchAction_Publish',
		'delete' => 'CMSBatchAction_Delete',
		'deletefromlive' => 'CMSBatchAction_DeleteFromLive',
	);
	
	static $url_handlers = array(
		'$BatchAction/applicablepages' => 'handleApplicablePages',
		'$BatchAction/confirmation' => 'handleConfirmation',
		'$BatchAction' => 'handleAction',
	);
	
	protected $parentController;
	protected $urlSegment;
	
	/**
	 * Register a new batch action.  Each batch action needs to be represented by a subclass
	 * of 
	 * 
	 * @param $urlSegment The URL Segment of the batch action - the URL used to process this
	 * action will be admin/batchactions/(urlSegment)
	 * @param $batchActionClass The name of the CMSBatchAction subclass to register
	 */
	static function register($urlSegment, $batchActionClass) {
		if(is_subclass_of($batchActionClass, 'CMSBatchAction')) {
			self::$batch_actions[$urlSegment] = $batchActionClass;
		} else {
			user_error("CMSBatchActionHandler::register() - Bad class '$batchActionClass'", E_USER_ERROR);
		}
	}
	
	function __construct($parentController, $urlSegment) {
		$this->parentController = $parentController;
		$this->urlSegment = $urlSegment;
		parent::__construct();
	}
	
	function Link() {
		return Controller::join_links($this->parentController->Link(), $this->urlSegment);
	}

	function handleAction($request) {
		// This method can't be called without ajax.
		if(!Director::is_ajax()) {
			Director::redirectBack();
			return;
		}
		
		// Protect against CSRF on destructive action
		if(!SecurityToken::inst()->checkRequest($request)) return $this->httpError(400);

		$actions = Object::get_static($this->class, 'batch_actions');
		$actionClass = $actions[$request->param('BatchAction')];
		$actionHandler = new $actionClass();
		
		// Sanitise ID list and query the database for apges
		$ids = split(' *, *', trim($request->requestVar('csvIDs')));
		foreach($ids as $k => $v) if(!is_numeric($v)) unset($ids[$k]);
		
		if($ids) {
			$pages = DataObject::get('SiteTree', "\"SiteTree\".\"ID\" IN (" . implode(", ", $ids) . ")");
			
			// If we didn't query all the pages, then find the rest on the live site
			if(!$pages || $pages->Count() < sizeof($ids)) {
				foreach($ids as $id) $idsFromLive[$id] = true;
				if($pages) foreach($pages as $page) unset($idsFromLive[$page->ID]);
				$idsFromLive = array_keys($idsFromLive);
				
				// Debug::message("\"SiteTree\".\"ID\" IN (" . implode(", ", $idsFromLive) . ")");
				$livePages = Versioned::get_by_stage('SiteTree', 'Live', "\"SiteTree\".\"ID\" IN (" . implode(", ", $idsFromLive) . ")");
				if($pages) $pages->merge($livePages);
				else $pages = $livePages;
			}
		} else {
			$pages = new DataObjectSet();
		}
		
		return $actionHandler->run($pages);
	} 

	function handleApplicablePages($request) {
		// Find the action handler
		$actions = Object::get_static($this->class, 'batch_actions');
		$actionClass = $actions[$request->param('BatchAction')];
		$actionHandler = new $actionClass();

		// Sanitise ID list and query the database for apges
		$ids = split(' *, *', trim($request->requestVar('csvIDs')));
		foreach($ids as $k => $id) $ids[$k] = (int)$id;
		$ids = array_filter($ids);
		
		if($actionHandler->hasMethod('applicablePages')) {
			$applicableIDs = $actionHandler->applicablePages($ids);
		} else {
			$applicableIDs = $ids;
		}
		
		$response = new SS_HTTPResponse(json_encode($applicableIDs));
		$response->addHeader("Content-type", "application/json");
		return $response;
	}
	
	function handleConfirmation($request) {
		// Find the action handler
		$actions = Object::get_static($this->class, 'batch_actions');
		$actionClass = $actions[$request->param('BatchAction')];
		$actionHandler = new $actionClass();

		// Sanitise ID list and query the database for apges
		$ids = split(' *, *', trim($request->requestVar('csvIDs')));
		foreach($ids as $k => $id) $ids[$k] = (int)$id;
		$ids = array_filter($ids);
		
		if($actionHandler->hasMethod('confirmationDialog')) {
			$response = new SS_HTTPResponse(json_encode($actionHandler->confirmationDialog($ids)));
		} else {
			$response = new SS_HTTPResponse(json_encode(array('alert' => false)));
		}
		
		$response->addHeader("Content-type", "application/json");
		return $response;
	}
	
	/**
	 * Return a DataObjectSet of ArrayData objects containing the following pieces of info
	 * about each batch action:
	 *  - Link
	 *  - Title
	 *  - DoingText
	 */
	function batchActionList() {
		$actions = Object::get_static($this->class, 'batch_actions');
		$actionList = new DataObjectSet();
		
		foreach($actions as $urlSegment => $actionClass) {
			$actionObj = new $actionClass();
			if($actionObj->canView()) {
				$actionDef = new ArrayData(array(
					"Link" => Controller::join_links($this->Link(), $urlSegment),
					"Title" => $actionObj->getActionTitle(),
					"DoingText" => $actionObj->getDoingText(),
				));
				$actionList->push($actionDef);
			}
		}
		
		return $actionList;
	}
	


}