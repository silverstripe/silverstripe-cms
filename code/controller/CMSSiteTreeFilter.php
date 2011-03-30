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

	/**
	 * @var Array Search parameters, mostly properties on {@link SiteTree}.
	 * Caution: Unescaped data.
	 */
	protected $params = array();
	
	/**
	 * @var Array
	 */
	protected $_cache_ids = null;
	
	/**
	 * @var Array
	 */
	protected $_cache_expanded = array();
	
	/**
	 * @var String 
	 */
	protected $childrenMethod = null;
		
	function __construct($params = null) {
		if($params) $this->params = $params;
		
		parent::__construct();
	}
	
	/**
	 * @return String Method on {@link Hierarchy} objects
	 * which is used to traverse into children relationships.
	 */
	function getChildrenMethod() {
		return $this->childrenMethod;
	}
	
	/**
	 * @return Array Map of Page IDs to their respective ParentID values.
	 */
	function pagesIncluded() {}
	
	/**
	 * Populate the IDs of the pages returned by pagesIncluded(), also including
	 * the necessary parent helper pages.
	 */
	protected function populateIDs() {
		$parents = array();
		$this->_cache_ids = array();
		
		if($pages = $this->pagesIncluded()) {
			
			// And keep a record of parents we don't need to get 
			// parents of themselves, as well as IDs to mark
			foreach($pages as $pageArr) {
				$parents[$pageArr['ParentID']] = true;
				$this->_cache_ids[$pageArr['ID']] = true;
			}
		
			if(!empty($parents)) {
				$q = new SQLQuery();
				$q->select(array('"ID"','"ParentID"'))
					->from('"SiteTree"')
					->where('"ID" in ('.implode(',',array_keys($parents)).')');

				foreach($q->execute() as $row) {
					if ($row['ParentID']) $parents[$row['ParentID']] = true;
					$this->_cache_ids[$row['ID']] = true;
					$this->_cache_expanded[$row['ID']] = true;
				}
			}
		}
	}
	
	/**
	 * Returns TRUE if the given page should be included in the tree.
	 * Caution: Does NOT check view permissions on the page.
	 * 
	 * @param SiteTree $page
	 * @return Boolean
	 */
	public function isPageIncluded($page) {
		if($this->_cache_ids === NULL) $this->populateIDs();

		return (isset($this->_cache_ids[$page->ID]) && $this->_cache_ids[$page->ID]);
	}

}

/**
 * Works a bit different than the other filters:
 * Shows all pages *including* those deleted from stage and live.
 * It does not filter out pages still existing in the different stages.
 * 
 * @package cms
 * @subpackage content
 */
class CMSSiteTreeFilter_DeletedPages extends CMSSiteTreeFilter {
	
	protected $childrenMethod = "AllHistoricalChildren";
	
	static function title() {
		return _t('CMSSiteTreeFilter_DeletedPages.Title', "All pages, including deleted");
	}
	
	function pagesIncluded() {
		$ids = array();
		// TODO Not very memory efficient, but usually not very many deleted pages exist
		$pages = Versioned::get_including_deleted('SiteTree');
		if($pages) foreach($pages as $page) {
			$ids[] = array('ID' => $page->ID, 'ParentID' => $page->ParentID);
		}
		return $ids;
	}
}

/**
 * Gets all pages which have changed on stage.
 * 
 * @package cms
 * @subpackage content
 */
class CMSSiteTreeFilter_ChangedPages extends CMSSiteTreeFilter {
	
	static function title() {
		return _t('CMSSiteTreeFilter_ChangedPages.Title', "Changed pages");
	}
	
	function pagesIncluded() {
		$ids = array();
		$q = new SQLQuery();
		$q->select(array('"SiteTree"."ID"','"SiteTree"."ParentID"'))
			->from('"SiteTree"')
			->leftJoin('SiteTree_Live', '"SiteTree_Live"."ID" = "SiteTree"."ID"')
			->where('"SiteTree"."Version" > "SiteTree_Live"."Version"');

		foreach($q->execute() as $row) {
			$ids[] = array('ID'=>$row['ID'],'ParentID'=>$row['ParentID']);
		}

		return $ids;
	}	
}

/**
 * @package cms
 * @subpackage content
 */
class CMSSiteTreeFilter_Search extends CMSSiteTreeFilter {

	static function title() {
		return _t('CMSSiteTreeFilter_Search.Title', "All pages");
	}
	
	/**
	 * Retun an array of maps containing the keys, 'ID' and 'ParentID' for each page to be displayed
	 * in the search.
	 * 
	 * @return Array
	 */
	function pagesIncluded() {
		$ids = array();
		$q = new SQLQuery();
		$q->select(array('"ID"','"ParentID"'))
			->from('"SiteTree"');
		$where = array();
		
		$SQL_params = Convert::raw2sql($this->params);
		foreach($SQL_params as $name => $val) {
			switch($name) {
				// Match against URLSegment, Title, MenuTitle & Content
				case 'SiteTreeSearchTerm':
					$where[] = "\"URLSegment\" LIKE '%$val%' OR \"Title\" LIKE '%$val%' OR \"MenuTitle\" LIKE '%$val%' OR \"Content\" LIKE '%$val%'";
					break;
				// Match against date
				case 'SiteTreeFilterDate':
				 // TODO Date Parsing
					$val = ((int)substr($val,6,4)) 
						. '-' . ((int)substr($val,3,2)) 
						. '-' . ((int)substr($val,0,2));
					$where[] = "\"LastEdited\" > '$val'";
					break;
				// Match against exact ClassName
				case 'ClassName':
					if($val && $val != 'All') {
						$where[] = "\"ClassName\" = '$val'";
					}
					break;
				default:
					// Partial string match against a variety of fields 
					if(!empty($val) && singleton("SiteTree")->hasDatabaseField($name)) {
						$where[] = "\"$name\" LIKE '%$val%'";
					}
			}
		}
		$q->where(empty($where) ? '' : '(' . implode(') AND (',$where) . ')');
		
		foreach($q->execute() as $row) {
			$ids[] = array('ID'=>$row['ID'],'ParentID'=>$row['ParentID']);
		}
		
		return $ids;
	}
}