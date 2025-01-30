<?php

namespace SilverStripe\CMS\Controllers;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\ORM\DataList;
use SilverStripe\Versioned\Versioned;

/**
 * This filter will display the SiteTree as a site visitor might see the site, i.e only the
 * pages that is currently published.
 *
 * Note that this does not check canView permissions that might hide pages from certain visitors
 */
class CMSSiteTreeFilter_PublishedPages extends CMSSiteTreeFilter
{
    public static function title(): string
    {
        return _t(__CLASS__ . '.Title', "Published pages");
    }

    /**
     * Filters out all pages who's status who's status that doesn't exist on live
     */
    public function getFilteredPages(DataList $list): DataList
    {
        $list = Versioned::updateListToAlsoIncludeDeleted($list);
        $baseTable = SiteTree::singleton()->baseTable();
        $liveTable = SiteTree::singleton()->stageTable($baseTable, Versioned::LIVE);
        return $list->innerJoin(
            $liveTable,
            "\"{$baseTable}_Versions\".\"RecordID\" = \"$liveTable\".\"ID\""
        );
    }
}
