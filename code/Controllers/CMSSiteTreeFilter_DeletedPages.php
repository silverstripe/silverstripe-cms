<?php

namespace SilverStripe\CMS\Controllers;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Versioned\Versioned;

/**
 * Works a bit different than the other filters:
 * Shows all pages *including* those deleted from stage and live.
 * It does not filter out pages still existing in the different stages.
 */
class CMSSiteTreeFilter_DeletedPages extends CMSSiteTreeFilter
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
        return _t(__CLASS__ . '.Title', "All pages, including archived");
    }

    public function getFilteredPages()
    {
        $pages = Versioned::get_including_deleted(SiteTree::class);
        $pages = $this->applyDefaultFilters($pages);
        return $pages;
    }
}
