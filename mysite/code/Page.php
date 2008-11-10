<?php
class Page extends SiteTree {
	
	public static $db = array(
	);
	
	public static $has_one = array(
	);
	
}

class Page_Controller extends ContentController {
	
	public function init() {
		parent::init();

		// Note: you should use <% require %> tags inside your templates instead of putting Requirements calls here.  However
		// these are included so that our older themes still work
		Requirements::themedCSS("layout"); 
		Requirements::themedCSS("typography"); 
		Requirements::themedCSS("form"); 
	}
	
}
