<?php

namespace SilverStripe\CMS\Controllers;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Model\List\SS_List;
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
     * @see {@link ModelData::getStatusFlags()}
     * @return SS_List
     */
    public function getFilteredPages()
    {
        $pages = SiteTree::get();
        // Get all pages existing in draft but not live
        // Don't just use withVersionedMode - that would just get the latest draft versions
        // including records which have since been published.
        $pages = $pages->setDataQueryParam([
            'Versioned.mode' => 'stage_unique',
            'Versioned.stage' => Versioned::DRAFT,
        ]);
        return $this->applyDefaultFilters($pages);
    }
}
