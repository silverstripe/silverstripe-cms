<?php

namespace SilverStripe\CMS\Controllers;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\ORM\SS_List;
use SilverStripe\Versioned\Versioned;

class CMSSiteTreeFilter_Search extends CMSSiteTreeFilter
{

    public static function title()
    {
        return _t(__CLASS__ . '.Title', "All pages");
    }

    /**
     * Retun an array of maps containing the keys, 'ID' and 'ParentID' for each page to be displayed
     * in the search.
     *
     * @return SS_List
     */
    public function getFilteredPages()
    {
        // Filter default records
        $pages = Versioned::get_by_stage(SiteTree::class, Versioned::DRAFT);
        $pages = $this->applyDefaultFilters($pages);
        return $pages;
    }
}
