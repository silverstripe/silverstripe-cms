<?php

namespace SilverStripe\CMS\Controllers;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\ORM\DataList;
use SilverStripe\Versioned\Versioned;

/**
 * Gets all pages which have changed on stage.
 */
class CMSSiteTreeFilter_ChangedPages extends CMSSiteTreeFilter
{
    public static function title(): string
    {
        return _t(__CLASS__ . '.Title', "Modified pages");
    }

    public function getFilteredPages(DataList $list): DataList
    {
        $table = SiteTree::singleton()->baseTable();
        $liveTable = SiteTree::singleton()->stageTable($table, Versioned::LIVE);
        $list = Versioned::updateListToAlsoIncludeStage($list, Versioned::DRAFT);
        $list = $list->leftJoin($liveTable, "\"$liveTable\".\"ID\" = \"$table\".\"ID\"")
            ->where("\"$table\".\"Version\" <> \"$liveTable\".\"Version\"");
        return $list;
    }
}
