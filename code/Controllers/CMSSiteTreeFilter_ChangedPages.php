<?php

namespace SilverStripe\CMS\Controllers;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Versioned\Versioned;

/**
 * Gets all pages which have changed on stage.
 */
class CMSSiteTreeFilter_ChangedPages extends CMSSiteTreeFilter
{

    public static function title()
    {
        return _t(__CLASS__ . '.Title', "Modified pages");
    }

    public function getFilteredPages()
    {
        $pages = Versioned::get_by_stage(SiteTree::class, Versioned::DRAFT);
        $pages = $this->applyDefaultFilters($pages)
            ->leftJoin('SiteTree_Live', '"SiteTree_Live"."ID" = "SiteTree"."ID"')
            ->where('"SiteTree"."Version" <> "SiteTree_Live"."Version"');
        return $pages;
    }
}
