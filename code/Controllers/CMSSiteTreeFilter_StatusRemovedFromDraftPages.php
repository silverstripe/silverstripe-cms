<?php

namespace SilverStripe\CMS\Controllers;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Model\List\SS_List;
use SilverStripe\Versioned\Versioned;

/**
 * Filters pages which have a status "Removed from Draft".
 */
class CMSSiteTreeFilter_StatusRemovedFromDraftPages extends CMSSiteTreeFilter
{

    public static function title()
    {
        return _t(__CLASS__ . '.Title', 'Live but removed from draft');
    }

    /**
     * Filters out all pages who's status is set to "Removed from draft".
     *
     * @return SS_List
     */
    public function getFilteredPages()
    {
        $pages = SiteTree::get();
        // Get all pages removed from stage but not live
        // Don't just use withVersionedMode - that would just get the latest live versions
        // including records which were not removed from draft.
        $pages = $pages->setDataQueryParam([
            'Versioned.mode' => 'stage_unique',
            'Versioned.stage' => Versioned::LIVE,
        ]);
        return $this->applyDefaultFilters($pages);
    }
}
