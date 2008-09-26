<?php

class Page extends SiteTree {
	static $db = array(
	);
	
	static $defaults = array(
	);

	
}

class Page_Controller extends ContentController {
	function init() {
		parent::init();
		
		Requirements::themedCSS("layout");
		Requirements::themedCSS("typography");
		Requirements::themedCSS("form");
	}
}

?>
