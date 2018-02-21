<?php

namespace SilverStripe\CMS\Controllers;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\ORM\SS_List;
use SilverStripe\Versioned\Versioned;

/**
 * Filters pages which have a status "Draft".
 */
class CMSSiteTreeFilter_StatusDraftPages extends CMSSiteTreeFilter
{

    public static function title()
    {
        return _t(__CLASS__ . '.Title', 'Draft pages');
    }

    /**
     * Filters out all pages who's status is set to "Draft".
     *
     * @see {@link SiteTree::getStatusFlags()}
     * @return SS_List
     */
    public function getFilteredPages()
    {
        $pages = Versioned::get_by_stage(SiteTree::class, 'Stage');
        $pages = $this->applyDefaultFilters($pages);
        $pages = $pages->filterByCallback(function (SiteTree $page) {
            // If page exists on stage but not on live
            return $page->isOnDraftOnly();
        });
        return $pages;
    }
}
