<?

class Page extends SiteTree {
	static $db = array(
	);
	static $has_one = array(
   );
}

class Page_Controller extends ContentController {

	// Gets the Project Name
	function project() {
		global $project;
		 return $project;
	}	
	
}

?>