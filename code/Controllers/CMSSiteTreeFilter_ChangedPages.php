<?php

namespace SilverStripe\CMS\Controllers;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;

/**
 * Gets all pages which have changed on stage.
 */
class CMSSiteTreeFilter_ChangedPages extends CMSSiteTreeFilter
{

    public static function title()
    {
        return _t(__CLASS__ . '.Title', "Modified pages");
    }

    public function getFilteredPages()
    {
        $table = DataObject::singleton(SiteTree::class)->baseTable();
        $liveTable = DataObject::singleton(SiteTree::class)->stageTable($table, Versioned::LIVE);
        $pages = Versioned::get_by_stage(SiteTree::class, Versioned::DRAFT);
        $pages = $this->applyDefaultFilters($pages)
            ->leftJoin($liveTable, "\"$liveTable\".\"ID\" = \"$table\".\"ID\"")
            ->where("\"$table\".\"Version\" <> \"$liveTable\".\"Version\"");
        return $pages;
    }
}
