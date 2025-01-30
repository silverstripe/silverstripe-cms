<?php

namespace SilverStripe\CMS\Controllers;

use SilverStripe\ORM\DataList;
use SilverStripe\Versioned\Versioned;

/**
 * Filters pages which have a status "Deleted".
 */
class CMSSiteTreeFilter_StatusDeletedPages extends CMSSiteTreeFilter
{
    public static function title(): string
    {
        return _t(__CLASS__ . '.Title', 'Archived pages');
    }

    /**
     * Filters out all pages who's status is set to "Deleted".
     */
    public function getFilteredPages(DataList $list): DataList
    {
        return Versioned::updateListToOnlyIncludeArchived($list);
    }
}
