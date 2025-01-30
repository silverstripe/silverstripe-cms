<?php

namespace SilverStripe\CMS\Controllers;

use SilverStripe\ORM\DataList;
use SilverStripe\Versioned\Versioned;

/**
 * Filters pages which have a status "Removed from Draft".
 */
class CMSSiteTreeFilter_StatusRemovedFromDraftPages extends CMSSiteTreeFilter
{
    public static function title(): string
    {
        return _t(__CLASS__ . '.Title', 'Live but removed from draft');
    }

    /**
     * Filters out all pages who's status is set to "Removed from draft".
     */
    public function getFilteredPages(DataList $list): DataList
    {
        // Get all pages removed from stage but not live
        // Don't just use withVersionedMode - that would just get the latest live versions
        // including records which were not removed from draft.
        return $list->setDataQueryParam([
            'Versioned.mode' => 'stage_unique',
            'Versioned.stage' => Versioned::LIVE,
        ]);
    }
}
