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
abstract class CMSSiteTreeFilter extends Object implements LeftAndMain_SearchFilter {

	/**
	 * @var Array Search parameters, mostly properties on {@link SiteTree}.
	 * Caution: Unescaped data.
	 */
	protected $params = array();
	
	/**
	 * List of filtered items and all their parents
	 *
	 * @var array
	 */
	protected $_cache_ids = null;


	/**
	 * Subset of $_cache_ids which include only items that appear directly in search results.
	 * When highlighting these, item IDs in this subset should be visually distinguished from
	 * others in the complete set.
	 *
	 * @var array
	 */
	protected $_cache_highlight_ids = null;
	
	/**
	 * @var Array
	 */
	protected $_cache_expanded = array();
	
	/**
	 * @var string
	 */
	protected $childrenMethod = null;

	/**
	 * @var string
	 */
	protected $numChildrenMethod = 'numChildren';

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
	
	public function getChildrenMethod() {
		return $this->childrenMethod;
	}

	public function getNumChildrenMethod() {
		return $this->numChildrenMethod;
	}

	public function getPageClasses($page) {
		if($this->_cache_ids === NULL) {
			$this->populateIDs();
		}

		// If directly selected via filter, apply highlighting
		if(!empty($this->_cache_highlight_ids[$page->ID])) {
			return 'filtered-item';
		}
	}

	/**
	 * Gets the list of filtered pages
	 *
	 * @see {@link SiteTree::getStatusFlags()}
	 * @return SS_List
	 */
	abstract public function getFilteredPages();

	/**
	 * @return array Map of Page IDs to their respective ParentID values.
	 */
	public function pagesIncluded() {
		return $this->mapIDs($this->getFilteredPages());
	}
	
	/**
	 * Populate the IDs of the pages returned by pagesIncluded(), also including
	 * the necessary parent helper pages.
	 */
	protected function populateIDs() {
		$parents = array();
		$this->_cache_ids = array();
		$this->_cache_highlight_ids = array();
		
		if($pages = $this->pagesIncluded()) {
			
			// And keep a record of parents we don't need to get
			// parents of themselves, as well as IDs to mark
			foreach($pages as $pageArr) {
				$parents[$pageArr['ParentID']] = true;
				$this->_cache_ids[$pageArr['ID']] = true;
				$this->_cache_highlight_ids[$pageArr['ID']] = true;
			}

			while(!empty($parents)) {
				$q = Versioned::get_including_deleted('SiteTree', '"RecordID" in ('.implode(',',array_keys($parents)).')');
				$list = $q->map('ID', 'ParentID');
				$parents = array();
				foreach($list as $id => $parentID) {
					if ($parentID) $parents[$parentID] = true;
					$this->_cache_ids[$id] = true;
					$this->_cache_expanded[$id] = true;
				}
			}
		}
	}
	
	public function isPageIncluded($page) {
		if($this->_cache_ids === NULL) {
			$this->populateIDs();
		}

		return !empty($this->_cache_ids[$page->ID]);
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
					$query = $query->filter("LastEdited:GreaterThanOrEqual", $fromDate->dataValue().' 00:00:00');
					break;

				case 'LastEditedTo':
					$toDate = new DateField(null, null, $val);
					$query = $query->filter("LastEdited:LessThanOrEqual", $toDate->dataValue().' 23:59:59');
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
 * This filter will display the SiteTree as a site visitor might see the site, i.e only the
 * pages that is currently published.
 *
 * Note that this does not check canView permissions that might hide pages from certain visitors
 *
 * @package cms
 * @subpackage content
 */
class CMSSiteTreeFilter_PublishedPages extends CMSSiteTreeFilter {

	/**
	 * @return string
	 */
	static public function title() {
		return _t('CMSSIteTreeFilter_PublishedPages.Title', "Published pages");
	}

	/**
	 * @var string
	 */
	protected $childrenMethod = "AllHistoricalChildren";

	/**
	 * @var string
	 */
	protected $numChildrenMethod = 'numHistoricalChildren';

	/**
	 * Filters out all pages who's status who's status that doesn't exist on live
	 *
	 * @see {@link SiteTree::getStatusFlags()}
	 * @return SS_List
	 */
	public function getFilteredPages() {
		$pages = Versioned::get_including_deleted('SiteTree');
		$pages = $this->applyDefaultFilters($pages);
		$pages = $pages->filterByCallback(function($page) {
			return $page->getExistsOnLive();
		});
		return $pages;
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

	/**
	 * @var string
	 */
	protected $childrenMethod = "AllHistoricalChildren";

	/**
	 * @var string
	 */
	protected $numChildrenMethod = 'numHistoricalChildren';
	
	static public function title() {
		return _t('CMSSiteTreeFilter_DeletedPages.Title', "All pages, including archived");
	}
	
	public function getFilteredPages() {
		$pages = Versioned::get_including_deleted('SiteTree');
		$pages = $this->applyDefaultFilters($pages);
		return $pages;
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
		return _t('CMSSiteTreeFilter_ChangedPages.Title', "Modified pages");
	}
	
	public function getFilteredPages() {
		$pages = Versioned::get_by_stage('SiteTree', 'Stage');
		$pages = $this->applyDefaultFilters($pages)
			->leftJoin('SiteTree_Live', '"SiteTree_Live"."ID" = "SiteTree"."ID"')
			->where('"SiteTree"."Version" <> "SiteTree_Live"."Version"');
		return $pages;
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
	 * @return SS_List
	 */
	public function getFilteredPages() {
		$pages = Versioned::get_including_deleted('SiteTree');
		$pages = $this->applyDefaultFilters($pages);
		$pages = $pages->filterByCallback(function($page) {
			// If page is removed from stage but not live
			return $page->getIsDeletedFromStage() && $page->getExistsOnLive();
		});
		return $pages;
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
		return _t('CMSSiteTreeFilter_StatusDraftPages.Title', 'Draft pages');
	}
	
	/**
	 * Filters out all pages who's status is set to "Draft".
	 *
	 * @see {@link SiteTree::getStatusFlags()}
	 * @return SS_List
	 */
	public function getFilteredPages() {
		$pages = Versioned::get_by_stage('SiteTree', 'Stage');
		$pages = $this->applyDefaultFilters($pages);
		$pages = $pages->filterByCallback(function($page) {
			// If page exists on stage but not on live
			return (!$page->getIsDeletedFromStage() && $page->getIsAddedToStage());
		});
		return $pages;
	}	
}

/**
 * Filters pages which have a status "Deleted".
 *
 * @package cms
 * @subpackage content
 */
class CMSSiteTreeFilter_StatusDeletedPages extends CMSSiteTreeFilter {

	/**
	 * @var string
	 */
	protected $childrenMethod = "AllHistoricalChildren";

	/**
	 * @var string
	 */
	protected $numChildrenMethod = 'numHistoricalChildren';
	
	static public function title() {
		return _t('CMSSiteTreeFilter_StatusDeletedPages.Title', 'Archived pages');
	}
	
	/**
	 * Filters out all pages who's status is set to "Deleted".
	 *
	 * @see {@link SiteTree::getStatusFlags()}
	 * @return SS_List
	 */
	public function getFilteredPages() {
		$pages = Versioned::get_including_deleted('SiteTree');
		$pages = $this->applyDefaultFilters($pages);

		$pages = $pages->filterByCallback(function($page) {
			// Doesn't exist on either stage or live
			return $page->getIsDeletedFromStage() && !$page->getExistsOnLive();
		});
		return $pages;
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
	 * @return SS_List
	 */
	public function getFilteredPages() {
		// Filter default records
		$pages = Versioned::get_by_stage('SiteTree', 'Stage');
		$pages = $this->applyDefaultFilters($pages);
		return $pages;
	}
}
