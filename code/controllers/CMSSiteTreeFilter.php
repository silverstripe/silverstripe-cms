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
	
	/**
	 * Returns a sorted array of all implementators of CMSSiteTreeFilter, suitable for use in a dropdown.
	 * 
	 * @return array
	 */
	public static function get_all_filters() {
		// get all filter instances
		$filters = ClassInfo::subclassesFor('CMSSiteTreeFilter');
		// remove abstract CMSSiteTreeFilter class
		array_shift($filters);
		// add filters to map
		$filterMap = array();
		
		foreach($filters as $filter) {
			$filterMap[$filter] = call_user_func(array($filter, 'title'));
		}
		// ensure that 'all pages' filter is on top position
		uasort($filterMap, 
			create_function('$a,$b', 'return ($a == "CMSSiteTreeFilter_Search") ? 1 : -1;')
		);
		
		return $filterMap;
	}
		
	public function __construct($params = null) {
		if($params) $this->params = $params;
		
		parent::__construct();
	}
	
	/**
	 * @return String Method on {@link Hierarchy} objects
	 * which is used to traverse into children relationships.
	 */
	public function getChildrenMethod() {
		return $this->childrenMethod;
	}
	
	/**
	 * @return Array Map of Page IDs to their respective ParentID values.
	 */
	public function pagesIncluded() {}
	
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

			while(!empty($parents)) {
				$q = new SQLQuery();
				$q->setSelect(array('"ID"','"ParentID"'))
					->setFrom('"SiteTree"')
					->setWhere('"ID" in ('.implode(',',array_keys($parents)).')');

				$parents = array();

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
	
	static public function title() {
		return _t('CMSSiteTreeFilter_DeletedPages.Title', "All pages, including deleted");
	}
	
	public function pagesIncluded() {
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
	
	static public function title() {
		return _t('CMSSiteTreeFilter_ChangedPages.Title', "Changed pages");
	}
	
	public function pagesIncluded() {
		$ids = array();
		$q = new SQLQuery();
		$q->setSelect(array('"SiteTree"."ID"','"SiteTree"."ParentID"'))
			->setFrom('"SiteTree"')
			->addLeftJoin('SiteTree_Live', '"SiteTree_Live"."ID" = "SiteTree"."ID"')
			->setWhere('"SiteTree"."Version" > "SiteTree_Live"."Version"');

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

	static public function title() {
		return _t('CMSSiteTreeFilter_Search.Title', "All pages");
	}
	
	/**
	 * Retun an array of maps containing the keys, 'ID' and 'ParentID' for each page to be displayed
	 * in the search.
	 * 
	 * @return Array
	 */
	public function pagesIncluded() {
		$sng = singleton('SiteTree');
		$ids = array();

		$query = new DataQuery('SiteTree');
		$query->setQueriedColumns(array('ID', 'ParentID'));

		foreach($this->params as $name => $val) {
			$SQL_val = Convert::raw2sql($val);

			switch($name) {
				case 'Term':
					$query->whereAny(array(
						"\"URLSegment\" LIKE '%$SQL_val%'",
						"\"Title\" LIKE '%$SQL_val%'",
						"\"MenuTitle\" LIKE '%$SQL_val%'",
						"\"Content\" LIKE '%$SQL_val%'"
					));
					break;

				case 'LastEditedFrom':
					$fromDate = new DateField(null, null, $SQL_val);
					$query->where("\"LastEdited\" >= '{$fromDate->dataValue()}'");
					break;

				case 'LastEditedTo':
					$toDate = new DateField(null, null, $SQL_val);
					$query->where("\"LastEdited\" <= '{$toDate->dataValue()}'");
					break;

				case 'ClassName':
					if($val && $val != 'All') {
						$query->where("\"ClassName\" = '$SQL_val'");
					}
					break;

				default:
					if(!empty($val) && $sng->hasDatabaseField($name)) {
						$filter = $sng->dbObject($name)->defaultSearchFilter();
						$filter->setValue($val);
						$filter->apply($query);
					}
			}
		}

		foreach($query->execute() as $row) {
			$ids[] = array('ID' => $row['ID'], 'ParentID' => $row['ParentID']);
		}

		return $ids;
	}
}
