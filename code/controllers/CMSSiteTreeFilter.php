<?php
/**
 * Base class for filtering the subtree for certain node statuses.
 * 
 * The simplest way of building a CMSSiteTreeFilter is to create a pagesToBeShown() method that
 * returns an Iterator of maps, each entry containing the 'ID' and 'ParentID' of the pages to be
 * included in the tree. The result of a DB::query() can then be returned directly.
 *
 * If you wish to make a more complex tree, you can overload includeInTree($page) to return true/
 * false depending on whether the given page should be included. Note that you will need to include
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
			$filterMap[$filter] = $filter::title();
		}
		
		// Ensure that 'all pages' filter is on top position and everything else is sorted alphabetically
		uasort($filterMap, function($a, $b) {
			return ($a === CMSSiteTreeFilter_Search::title())
				? -1
				: strcasecmp($a, $b);
		});
		
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
	
	/**
	 * Applies the default filters to a specified DataList of pages
	 * 
	 * @param DataList $query Unfiltered query
	 * @return DataList Filtered query
	 */
	protected function applyDefaultFilters($query) {
		$sng = singleton('SiteTree');
		foreach($this->params as $name => $val) {
			if(empty($val)) continue;

			switch($name) {
				case 'Term':
					$query = $query->filterAny(array(
						'URLSegment:PartialMatch' => $val,
						'Title:PartialMatch' => $val,
						'MenuTitle:PartialMatch' => $val,
						'Content:PartialMatch' => $val
					));
					break;

				case 'LastEditedFrom':
					$fromDate = new DateField(null, null, $val);
					$query = $query->filter("LastEdited:GreaterThanOrEqual", $fromDate->dataValue());
					break;

				case 'LastEditedTo':
					$toDate = new DateField(null, null, $val);
					$query = $query->filter("LastEdited:LessThanOrEqual", $toDate->dataValue());
					break;

				case 'ClassName':
					if($val != 'All') {
						$query = $query->filter('ClassName', $val);
					}
					break;

				default:
					if($sng->hasDatabaseField($name)) {
						$filter = $sng->dbObject($name)->defaultSearchFilter();
						$filter->setValue($val);
						$query = $query->alterDataQuery(array($filter, 'apply'));
					}
			}
		}
		return $query;
	}
	
	/**
	 * Maps a list of pages to an array of associative arrays with ID and ParentID keys
	 * 
	 * @param DataList $pages
	 * @return array
	 */
	protected function mapIDs($pages) {
		$ids = array();
		if($pages) foreach($pages as $page) {
			$ids[] = array('ID' => $page->ID, 'ParentID' => $page->ParentID);
		}
		return $ids;
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
		$pages = Versioned::get_including_deleted('SiteTree');
		$pages = $this->applyDefaultFilters($pages);
		return $this->mapIDs($pages);
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
		$pages = Versioned::get_by_stage('SiteTree', 'Stage');
		$pages = $this->applyDefaultFilters($pages)
			->leftJoin('SiteTree_Live', '"SiteTree_Live"."ID" = "SiteTree"."ID"')
			->where('"SiteTree"."Version" > "SiteTree_Live"."Version"');
		return $this->mapIDs($pages);
	}	
}

/**
 * Filters pages which have a status "Removed from Draft".
 * 
 * @package cms
 * @subpackage content
 */
class CMSSiteTreeFilter_StatusRemovedFromDraftPages extends CMSSiteTreeFilter {
	
	static public function title() {
		return _t('CMSSiteTreeFilter_StatusRemovedFromDraftPages.Title', 'Live but removed from draft');
	}
	
	/**
	 * Filters out all pages who's status is set to "Removed from draft".
	 * 
	 * @see {@link SiteTree::getStatusFlags()}
	 * @return array
	 */
	public function pagesIncluded() {
		$pages = Versioned::get_including_deleted('SiteTree');
		$pages = $this->applyDefaultFilters($pages);
		$pages = $pages->filterByCallback(function($page) {
			// If page is removed from stage but not live
			return $page->IsDeletedFromStage && $page->ExistsOnLive;
		});
		return $this->mapIDs($pages);
	}	
}

/**
 * Filters pages which have a status "Draft".
 * 
 * @package cms
 * @subpackage content
 */
class CMSSiteTreeFilter_StatusDraftPages extends CMSSiteTreeFilter {
	
	static public function title() {
		return _t('CMSSiteTreeFilter_StatusDraftPages.Title', 'Draft unpublished pages');
	}
	
	/**
	 * Filters out all pages who's status is set to "Draft".
	 * 
	 * @see {@link SiteTree::getStatusFlags()}
	 * @return array
	 */
	public function pagesIncluded() {
		$pages = Versioned::get_by_stage('SiteTree', 'Stage');
		$pages = $this->applyDefaultFilters($pages);
		$pages = $pages->filterByCallback(function($page) {
			// If page exists on stage but not on live
			return (!$page->IsDeletedFromStage && $page->IsAddedToStage);
		});
		return $this->mapIDs($pages);
	}	
}

/**
 * Filters pages which have a status "Deleted".
 * 
 * @package cms
 * @subpackage content
 */
class CMSSiteTreeFilter_StatusDeletedPages extends CMSSiteTreeFilter {
	
	protected $childrenMethod = "AllHistoricalChildren";	
	
	static public function title() {
		return _t('CMSSiteTreeFilter_StatusDeletedPages.Title', 'Deleted pages');
	}
	
	/**
	 * Filters out all pages who's status is set to "Deleted".
	 * 
	 * @see {@link SiteTree::getStatusFlags()}
	 * @return array
	 */
	public function pagesIncluded() {
		$pages = Versioned::get_including_deleted('SiteTree');
		$pages = $this->applyDefaultFilters($pages);
		$pages = $pages->filterByCallback(function($page) {
			// Doesn't exist on either stage or live
			return $page->IsDeletedFromStage && !$page->ExistsOnLive;
		});
		return $this->mapIDs($pages);
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

		// Filter default records
		$pages = Versioned::get_by_stage('SiteTree', 'Stage');
		$pages = $this->applyDefaultFilters($pages);
		return $this->mapIDs($pages);
	}
}
