<?php

class StatisticsAdmin extends LeftAndMain {
	static $tree_class = "SiteTree";
	static $subitem_class = "Member";

	/**
	 * Initialisation method called before accessing any functionality that BulkLoaderAdmin has to offer
	 */
	public function init() {
		//Requirements::javascript('cms/javascript/StatisticsAdmin_left.js');
		//Requirements::javascript('cms/javascript/StatisticsAdmin_right.js');
		parent::init();
	}
 
	public function Link($action=null) {
		return "admin/statistics/$action";
	}
 
	/**
	 * Form that will be shown when we open one of the items
	 */	 
	public function getEditForm($id = null) {
		return new Form($this, "EditForm",
			new FieldSet(
				new ReadonlyField('id #',$id)
			),
			new FieldSet(
				new FormAction('go')
			)
		);
	}
	
	function getSiteTreeFor($className) {
		$obj = singleton($className);
		$obj->markPartialTree();
		if($p = $this->currentPage()) $obj->markToExpose($p);

		// getChildrenAsUL is a flexible and complex way of traversing the tree
		$siteTree = $obj->getChildrenAsUL("", '
					"<li id=\"record-$child->ID\" class=\"" . $child->CMSTreeClasses($extraArg) . "\">" .
					"<a href=\"" . Director::link(substr($extraArg->Link(),0,-1), "show", $child->ID) . "\" " . (($child->canEdit() || $child->canAddChildren()) ? "" : "class=\"disabled\"") . " title=\"Page type: ".$child->class."\" >" .
					($child->TreeTitle()) .
					"</a>"
'
					,$this, true);

		// Wrap the root if needs be.
	
		$rootLink = $this->Link() . '0';
		$siteTree = "<ul id=\"sitetree\" class=\"tree unformatted\"><li id=\"record-0\" class=\"Root nodelete\"><a href=\"$rootLink\">Pages</a>"
			. $siteTree . "</li></ul>";
		

		return $siteTree;
	}
	
	public function SiteTreeAsUL() {
		return $this->getSiteTreeFor("SiteTree");
	}
	
	public function versions() {
		/*$pageID = $this->urlParams['ID'];
		//$pageID = "1";
		$page = $this->getRecord($pageID);
		if($page) {
			$versions = $page->allVersions($_REQUEST['unpublished'] ? "" : "`SiteTree_versions`.WasPublished = 1");
			return array(
				'Versions' => $versions,
			);		
		} else {
			return "Can't find page #$pageID";
		}*/
		
	}
	
}

?>
