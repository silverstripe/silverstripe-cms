<?php

namespace SilverStripe\CMS\Controllers;

use SilverStripe\Admin\LeftAndMain_SearchFilter;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Forms\DateField;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\SS_List;
use SilverStripe\Versioned\Versioned;

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
 */
abstract class CMSSiteTreeFilter implements LeftAndMain_SearchFilter
{
    use Injectable;

    /**
     * Search parameters, mostly properties on {@link SiteTree}.
     * Caution: Unescaped data.
     *
     * @var array
     */
    protected $params = [];

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
     * @var array
     */
    protected $_cache_expanded = [];

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
    public static function get_all_filters()
    {
        // get all filter instances
        $filters = ClassInfo::subclassesFor(CMSSiteTreeFilter::class);

        // remove abstract CMSSiteTreeFilter class
        array_shift($filters);

        // add filters to map
        $filterMap = [];
        foreach ($filters as $filter) {
            $filterMap[$filter] = $filter::title();
        }

        // Ensure that 'all pages' filter is on top position and everything else is sorted alphabetically
        uasort($filterMap, function ($a, $b) {
            return ($a === CMSSiteTreeFilter_Search::title())
                ? -1
                : strcasecmp($a ?? '', $b ?? '');
        });

        return $filterMap;
    }

    public function __construct($params = null)
    {
        if ($params) {
            $this->params = $params;
        }
    }

    public function getChildrenMethod()
    {
        return $this->childrenMethod;
    }

    public function getNumChildrenMethod()
    {
        return $this->numChildrenMethod;
    }

    public function getPageClasses($page)
    {
        if ($this->_cache_ids === null) {
            $this->populateIDs();
        }

        // If directly selected via filter, apply highlighting
        if (!empty($this->_cache_highlight_ids[$page->ID])) {
            return 'filtered-item';
        }

        return null;
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
    public function pagesIncluded()
    {
        return $this->mapIDs($this->getFilteredPages());
    }

    /**
     * Populate the IDs of the pages returned by pagesIncluded(), also including
     * the necessary parent helper pages.
     */
    protected function populateIDs()
    {
        $parents = [];
        $this->_cache_ids = [];
        $this->_cache_highlight_ids = [];

        if ($pages = $this->pagesIncluded()) {
            // And keep a record of parents we don't need to get
            // parents of themselves, as well as IDs to mark
            foreach ($pages as $pageArr) {
                $parents[$pageArr['ParentID']] = true;
                $this->_cache_ids[$pageArr['ID']] = true;
                $this->_cache_highlight_ids[$pageArr['ID']] = true;
            }

            while (!empty($parents)) {
                $q = Versioned::get_including_deleted(SiteTree::class)
                    ->byIDs(array_keys($parents ?? []));
                $list = $q->map('ID', 'ParentID');
                $parents = [];
                foreach ($list as $id => $parentID) {
                    if ($parentID) {
                        $parents[$parentID] = true;
                    }
                    $this->_cache_ids[$id] = true;
                    $this->_cache_expanded[$id] = true;
                }
            }
        }
    }

    public function isPageIncluded($page)
    {
        if ($this->_cache_ids === null) {
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
    protected function applyDefaultFilters($query)
    {
        $sng = SiteTree::singleton();
        foreach ($this->params as $name => $val) {
            if (empty($val)) {
                continue;
            }

            switch ($name) {
                case 'Term':
                    $query = $query->filterAny([
                        'URLSegment:PartialMatch' => Convert::raw2url($val),
                        'Title:PartialMatch' => $val,
                        'MenuTitle:PartialMatch' => $val,
                        'Content:PartialMatch' => $val
                    ]);
                    break;

                case 'URLSegment':
                    $query = $query->filter([
                        'URLSegment:PartialMatch' => Convert::raw2url($val),
                    ]);
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
                    if ($val != 'All') {
                        $query = $query->filter('ClassName', $val);
                    }
                    break;

                default:
                    $field = $sng->dbObject($name);
                    if ($field) {
                        $filter = $field->defaultSearchFilter();
                        $filter->setValue($val);
                        $query = $query->alterDataQuery([$filter, 'apply']);
                    }
            }
        }
        return $query;
    }

    /**
     * Maps a list of pages to an array of associative arrays with ID and ParentID keys
     *
     * @param SS_List $pages
     * @return array
     */
    protected function mapIDs($pages)
    {
        $ids = [];
        if ($pages) {
            foreach ($pages as $page) {
                $ids[] = ['ID' => $page->ID, 'ParentID' => $page->ParentID];
            }
        }
        return $ids;
    }
}
