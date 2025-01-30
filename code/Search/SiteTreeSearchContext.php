<?php

namespace SilverStripe\CMS\Search;

use InvalidArgumentException;
use SilverStripe\CMS\Controllers\CMSSiteTreeFilter;
use SilverStripe\CMS\Controllers\CMSSiteTreeFilter_Search;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\Search\SearchContext;

/**
 * Manages search of SiteTree records.
 *
 * Allows for the `FilterClass` parameter to determine a CMSSiteTreeFilter class to apply to the search query.
 */
class SiteTreeSearchContext extends SearchContext
{
    public function getQuery($searchParams, $sort = false, int|array|null $limit = null, $existingQuery = null)
    {
        $origSearchParams = $searchParams;
        // Set default filter if other params are set
        if ($searchParams && empty($searchParams['FilterClass'])) {
            $searchParams['FilterClass'] = CMSSiteTreeFilter_Search::class;
        }
        $query = parent::getQuery($searchParams, $sort, $limit, $existingQuery);
        // We don't want the default filter class being display as a filter in the filters list
        // so we set the original search params after performing the filtering.
        $this->setSearchParams($origSearchParams);
        return $query;
    }

    protected function individualFieldSearch(DataList $query, array $searchableFields, string $searchField, $searchPhrase): DataList
    {
        if ($searchField === 'FilterClass') {
            $filter = $this->getQueryFilter($searchPhrase);
            return $filter->getFilteredPages($query);
        }

        return parent::individualFieldSearch($query, $searchableFields, $searchField, $searchPhrase);
    }

    private function getQueryFilter($filterClass): CMSSiteTreeFilter
    {
        if (!is_subclass_of($filterClass, CMSSiteTreeFilter::class)) {
            throw new InvalidArgumentException("Invalid filter class passed: {$filterClass}");
        }
        return $filterClass::create();
    }
}
