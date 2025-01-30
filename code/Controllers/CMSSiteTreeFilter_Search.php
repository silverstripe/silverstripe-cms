<?php

namespace SilverStripe\CMS\Controllers;

use SilverStripe\ORM\DataList;
use SilverStripe\Versioned\Versioned;

class CMSSiteTreeFilter_Search extends CMSSiteTreeFilter
{
    public static function title(): string
    {
        return _t(__CLASS__ . '.Title', "All pages");
    }

    /**
     * Retun an array of maps containing the keys, 'ID' and 'ParentID' for each page to be displayed
     * in the search.
     */
    public function getFilteredPages(DataList $list): DataList
    {
        return Versioned::updateListToAlsoIncludeStage($list, Versioned::DRAFT);
    }
}
