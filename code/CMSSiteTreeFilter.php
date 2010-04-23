<?php
/**
 * Base class for filtering the subtree for certain node statuses.
 * 
 * The simplest way of building a CMSSiteTreeFilter is to create a pagesToBeShown() method that
 * returns an Iterator of maps, each entry containing the 'ID' and 'ParentID' of the pages to be
 * included in the tree.  The reuslt of a DB::query() can be returned directly.
 *
 * If you wish to make a more complex tree, you can overload includeInTree($page) to return true/
 * false depending on whether the given page should be included.  Note that you will need to include
 * parent helper pages yourself.
 * 
 * @package cms
 * @subpackage content
 */
abstract class CMSSiteTreeFilter extends Object {

	protected $ids = null;
	protected $expanded = array();
	
	static function showInList() {
		return true;
	}

	function getTree() {
		if(method_exists($this, 'pagesIncluded')) {
			$this->populateIDs();
		}

		$leftAndMain = new CMSMain();
		$tree = $leftAndMain->getSiteTreeFor('SiteTree', isset($_REQUEST['ID']) ? $_REQUEST['ID'] : 0, null, null, array($this, 'includeInTree'), count($this->ids));

		// Trim off the outer tag
		$tree = ereg_replace('^[ \t\r\n]*<ul[^>]*>','', $tree);
		$tree = ereg_replace('</ul[^>]*>[ \t\r\n]*$','', $tree);

		return $tree;
	}
	
	/**
	 * Populate $this->ids with the IDs of the pages returned by pagesIncluded(), also including
	 * the necessary parent helper pages.
	 */
	protected function populateIDs() {
		if($res = $this->pagesIncluded()) {
			
			/* And keep a record of parents we don't need to get parents of themselves, as well as IDs to mark */
			foreach($res as $row) {
				if ($row['ParentID']) $parents[$row['ParentID']] = true;
				$this->ids[$row['ID']] = true;
			}
		
		
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
	}
	
	/**
	 * Returns true if the given page should be included in the tree.
	 */
	public function includeInTree($page) {
		return isset($this->ids[$page->ID]) && $this->ids[$page->ID] ? true : false;
	}

}

/**
 * @package cms
 * @subpackage content
 */
class CMSSiteTreeFilter_DeletedPages extends CMSSiteTreeFilter {
	static function title() {
		return _t('CMSSiteTreeFilter.DELETEDPAGES', "All pages, including deleted");
	}
	
	function getTree() {
		$leftAndMain = new CMSMain();
		$tree = $leftAndMain->getSiteTreeFor('SiteTree', isset($_REQUEST['ID']) ? $_REQUEST['ID'] : 0, "AllHistoricalChildren", "numHistoricalChildren");

		// Trim off the outer tag
		$tree = ereg_replace('^[ \t\r\n]*<ul[^>]*>','', $tree);
		$tree = ereg_replace('</ul[^>]*>[ \t\r\n]*$','', $tree);

		return $tree;
	}
}

/**
 * @package cms
 * @subpackage content
 */
class CMSSiteTreeFilter_ChangedPages extends CMSSiteTreeFilter {
	static function title() {
		return _t('CMSSiteTreeFilter.CHANGEDPAGES', "Changed pages");
	}
	
	function pagesIncluded() {
		return DB::query('SELECT "ParentID", "ID" FROM "SiteTree" WHERE "Status" LIKE \'Saved%\'');
	}	
}

/**
 * @package cms
 * @subpackage content
 */
class CMSSiteTreeFilter_Search extends CMSSiteTreeFilter {
	public $data;
	
	
	function __construct() {
		$this->data = $_REQUEST;
	}
	
	static function showInList() { return false; }
	
	static function title() {
		return _t('CMSSiteTreeFilter.SEARCH', 'Search');
	}
	
	/**
	 * Retun an array of maps containing the keys, 'ID' and 'ParentID' for each page to be displayed
	 * in the search.
	 */
	function pagesIncluded() {
		$data = $this->data;
		
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
		return $res;
	}
}
