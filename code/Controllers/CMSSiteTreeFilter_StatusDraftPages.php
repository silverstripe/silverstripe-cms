<?php

namespace SilverStripe\CMS\Controllers;

use SilverStripe\ORM\DataList;
use SilverStripe\Versioned\Versioned;

/**
 * Filters pages which have a status "Draft".
 */
class CMSSiteTreeFilter_StatusDraftPages extends CMSSiteTreeFilter
{
    public static function title(): string
    {
        return _t(__CLASS__ . '.Title', 'Draft pages');
    }

    /**
     * Filters out all pages who's status is set to "Draft".
     */
    public function getFilteredPages(DataList $list): DataList
    {
        // Get all pages existing in draft but not live
        // Don't just use withVersionedMode - that would just get the latest draft versions
        // including records which have since been published.
        return $list->setDataQueryParam([
            'Versioned.mode' => 'stage_unique',
            'Versioned.stage' => Versioned::DRAFT,
        ]);
    }
}
