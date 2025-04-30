<?php

namespace SilverStripe\CMS\Controllers;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\ORM\SS_List;
use SilverStripe\Versioned\Versioned;

/**
 * Filters pages which have a status "Deleted".
 */
class CMSSiteTreeFilter_StatusDeletedPages extends CMSSiteTreeFilter
{

    /**
     * @var string
     * @deprecated 5.4.0 Will be removed without equivalent functionality to replace it in a future major release.
     */
    protected $childrenMethod = "AllHistoricalChildren";

    /**
     * @var string
     * @deprecated 5.4.0 Will be removed without equivalent functionality to replace it in a future major release.
     */
    protected $numChildrenMethod = 'numHistoricalChildren';

    public static function title()
    {
        return _t(__CLASS__ . '.Title', 'Archived pages');
    }

    /**
     * Filters out all pages who's status is set to "Deleted".
     *
     * @see {@link SiteTree::getStatusFlags()}
     * @return SS_List
     */
    public function getFilteredPages()
    {
        $pages = Versioned::get_including_deleted(SiteTree::class);
        $pages = $this->applyDefaultFilters($pages);

        $pages = $pages->filterByCallback(function (SiteTree $page) {
            // Doesn't exist on either stage or live
            return $page->isArchived();
        });
        return $pages;
    }
}
