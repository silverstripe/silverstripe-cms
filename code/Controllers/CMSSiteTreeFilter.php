<?php

namespace SilverStripe\CMS\Controllers;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\DataList;

/**
 * Base class for filtering againt SiteTree for certain statuses (e.g. archived or draft only).
 */
abstract class CMSSiteTreeFilter
{
    use Injectable;

    /**
     * Returns a sorted array of all implementators of CMSSiteTreeFilter, suitable for use in a dropdown.
     *
     * @return array<string, CMSSiteTreeFilter>
     */
    public static function get_all_filters(): array
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

    /**
     * Get a title for this filter to display to the user (e.g. in a dropdown field).
     */
    abstract public static function title(): string;

    /**
     * Gets the list of filtered pages based on an existing list.
     */
    abstract public function getFilteredPages(DataList $list): DataList;
}
