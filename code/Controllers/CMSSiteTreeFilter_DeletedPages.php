<?php

namespace SilverStripe\CMS\Controllers;

use SilverStripe\ORM\DataList;
use SilverStripe\Versioned\Versioned;

/**
 * Works a bit different than the other filters:
 * Shows all pages *including* those deleted from stage and live.
 * It does not filter out pages still existing in the different stages.
 */
class CMSSiteTreeFilter_DeletedPages extends CMSSiteTreeFilter
{
    public static function title(): string
    {
        return _t(__CLASS__ . '.Title', "All pages, including archived");
    }

    public function getFilteredPages(DataList $list): DataList
    {
        return Versioned::updateListToAlsoIncludeDeleted($list);
    }
}
