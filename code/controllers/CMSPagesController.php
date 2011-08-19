<?php

/**
 * @package cms
 */
class CMSPagesController extends CMSMain {
	
	static $url_segment = 'pages';
	static $url_rule = '/$Action/$ID/$OtherID';
	static $url_priority = 41;
	static $menu_title = 'Pages';	
	
	function init() {
		parent::init();
		
		Requirements::javascript(CMS_DIR . '/javascript/CMSPagesController.Tree.js');
	}
	
	function show($request) {
		if($request->param('ID')) {
			$c = new CMSPageEditController();
			return $this->redirect(Controller::join_links($c->Link('show'), $request->param('ID')));
		}
		
		return parent::show($request);
	}
	
	function Link($action = null) {
		// Special case: All show links should redirect to the page edit interface instead (mostly from tree nodes)
		if(preg_match('/^show/', $action)) {
			return singleton('CMSPageEditController')->Link($action);
		}
		
		return parent::Link($action);
	}
	
}