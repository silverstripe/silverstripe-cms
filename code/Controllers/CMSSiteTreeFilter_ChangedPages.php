<?php

namespace SilverStripe\CMS\Controllers;

use SilverStripe\Versioned\Versioned;

/**
 * Gets all pages which have changed on stage.
 */
class CMSSiteTreeFilter_ChangedPages extends CMSSiteTreeFilter
{

    public static function title()
    {
        return _t('CMSSiteTreeFilter_ChangedPages.Title', "Modified pages");
    }

    public function getFilteredPages()
    {
        $pages = Versioned::get_by_stage('SilverStripe\\CMS\\Model\\SiteTree', 'Stage');
        $pages = $this->applyDefaultFilters($pages)
            ->leftJoin('SiteTree_Live', '"SiteTree_Live"."ID" = "SiteTree"."ID"')
            ->where('"SiteTree"."Version" <> "SiteTree_Live"."Version"');
        return $pages;
    }
}
