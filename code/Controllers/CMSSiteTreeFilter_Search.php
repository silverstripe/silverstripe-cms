<?php
namespace SilverStripe\CMS\Controllers;

use SilverStripe\ORM\SS_List;
use SilverStripe\ORM\Versioning\Versioned;

class CMSSiteTreeFilter_Search extends CMSSiteTreeFilter
{

    static public function title()
    {
        return _t('CMSSiteTreeFilter_Search.Title', "All pages");
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
        $pages = Versioned::get_by_stage('SilverStripe\\CMS\\Model\\SiteTree', 'Stage');
        $pages = $this->applyDefaultFilters($pages);
        return $pages;
    }
}
