<?php

namespace SilverStripe\CMS\Controllers;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Model\List\SS_List;
use SilverStripe\Versioned\Versioned;

/**
 * Filters pages which have a status "Deleted".
 */
class CMSSiteTreeFilter_StatusDeletedPages extends CMSSiteTreeFilter
{

    /**
     * @var string
     */
    protected $childrenMethod = "AllHistoricalChildren";

    /**
     * @var string
     */
    protected $numChildrenMethod = 'numHistoricalChildren';

    public static function title()
    {
        return _t(__CLASS__ . '.Title', 'Archived pages');
    }

    /**
     * Filters out all pages who's status is set to "Deleted".
     *
     * @see {@link ModelData::getStatusFlags()}
     * @return SS_List
     */
    public function getFilteredPages()
    {
        $pages = Versioned::getArchivedOnly(SiteTree::class);
        return $this->applyDefaultFilters($pages);
    }
}
