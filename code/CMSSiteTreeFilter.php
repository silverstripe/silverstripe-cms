<?php
/**
 * Base class for filtering the subtree for certain node statuses
 * @package cms
 * @subpackage content
 */
abstract class CMSSiteTreeFilter extends Object {
	abstract function getTree();
	static abstract function title();
	
	static function showInList() {
		return true;
	}
}

class CMSSiteTreeFilter_DeletedPages extends CMSSiteTreeFilter {
	static function title() {
		return "Deleted pages";
	}
	
	function getTree() {
		$leftAndMain = new LeftAndMain();
		$tree = $leftAndMain->getSiteTreeFor('SiteTree', isset($_REQUEST['ID']) ? $_REQUEST['ID'] : 0, "AllHistoricalChildren");

		// Trim off the outer tag
		$tree = ereg_replace('^[ \t\r\n]*<ul[^>]*>','', $tree);
		$tree = ereg_replace('</ul[^>]*>[ \t\r\n]*$','', $tree);

		return $tree;
	}
}

class CMSSiteTreeFilter_ChangedPages extends CMSSiteTreeFilter {
	static function title() {
		return "Changed pages";
	}
	
	function getTree() {
		$search = new CMSSitetreeFilter_Search();
		$search->data = array('Status' => 'Saved');
		return $search->getTree();
	}
}

class CMSSiteTreeFilter_Search extends CMSSiteTreeFilter {
	protected $ids = null;
	protected $expanded = array();
	public $data;
	
	
	function __construct() {
		$this->data = $_REQUEST;
	}
	
	static function showInList() { return false; }
	
	static function title() {
		return "Search";
	}
	
	function populateIds($data) {
		$this->ids = array();
		$this->expanded = array();

		$where = array();
		
		// Match against URLSegment, Title, MenuTitle & Content
		if (isset($data['SiteTreeSearchTerm'])) {
			$term = Convert::raw2sql($data['SiteTreeSearchTerm']);
			$where[] = "\"URLSegment\" LIKE '%$term%' OR \"Title\" LIKE '%$term%' OR \"MenuTitle\" LIKE '%$term%' OR \"Content\" LIKE '%$term%'";
		}
		
		// Match against date
		if (isset($data['SiteTreeFilterDate'])) {
			$date = $data['SiteTreeFilterDate'];
			$date = ((int)substr($date,6,4)) . '-' . ((int)substr($date,3,2)) . '-' . ((int)substr($date,0,2));
			$where[] = "\"LastEdited\" > '$date'"; 
		}
		
		// Match against exact ClassName
		if (isset($data['ClassName']) && $data['ClassName'] != 'All') {
			$klass = Convert::raw2sql($data['ClassName']);
			$where[] = "\"ClassName\" = '$klass'";
		}
		
		// Partial string match against a variety of fields 
		foreach (CMSMain::T_SiteTreeFilterOptions() as $key => $value) {
			if (!empty($data[$key])) {
				$match = Convert::raw2sql($data[$key]);
				$where[] = "\"$key\" LIKE '%$match%'";
			}
		}
		
		$where = empty($where) ? '' : 'WHERE (' . implode(') AND (',$where) . ')';
		
		$parents = array();
		
		/* Do the actual search */
		$res = DB::query('SELECT "ParentID", "ID" FROM "SiteTree" '.$where);
		if (!$res) return;
		
		/* And keep a record of parents we don't need to get parents of themselves, as well as IDs to mark */
		foreach($res as $row) {
			if ($row['ParentID']) $parents[$row['ParentID']] = true;
			$this->ids[$row['ID']] = true;
		}
		
		/* We need to recurse up the tree, finding ParentIDs for each ID until we run out of parents */
		while (!empty($parents)) {
			$res = DB::query('SELECT "ParentID", "ID" FROM "SiteTree" WHERE "ID" in ('.implode(',',array_keys($parents)).')');
			$parents = array();

			foreach($res as $row) {
				if ($row['ParentID']) $parents[$row['ParentID']] = true;
				$this->ids[$row['ID']] = true;
				$this->expanded[$row['ID']] = true;
			}
		}
	}
	
	public function includeInTree($page) {
		if ($this->ids === null) $this->populateIds($this->data);
		return isset($this->ids[$page->ID]) && $this->ids[$page->ID] ? true : false;
	}
	
	function getTree() {
		$leftAndMain = new LeftAndMain();
		$tree = $leftAndMain->getSiteTreeFor('SiteTree', isset($_REQUEST['ID']) ? $_REQUEST['ID'] : 0, null, array($this, 'includeInTree'));

		// Trim off the outer tag
		$tree = ereg_replace('^[ \t\r\n]*<ul[^>]*>','', $tree);
		$tree = ereg_replace('</ul[^>]*>[ \t\r\n]*$','', $tree);

		return $tree;
	}
}
