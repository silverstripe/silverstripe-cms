<?php

namespace SilverStripe\CMS\Reports;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\DataObject;
use SilverStripe\Reports\Report;

class RecentlyEditedReport extends Report
{

    public function title()
    {
        return _t(__CLASS__.'.LAST2WEEKS', "Pages edited in the last 2 weeks");
    }

    public function group()
    {
        return _t(__CLASS__.'.ContentGroupTitle', "Content reports");
    }

    public function sort()
    {
        return 200;
    }

    public function sourceRecords($params = null)
    {
        $tableName = DataObject::singleton(SiteTree::class)->baseTable();
        $threshold = strtotime('-14 days', DBDatetime::now()->getTimestamp());
        return SiteTree::get()
            ->filter('LastEdited:GreaterThan', date("Y-m-d H:i:s", $threshold))
            ->orderBy("\"$tableName\".\"LastEdited\" DESC");
    }

    public function columns()
    {
        return [
            "Title" => [
                "title" => "Title",
                "link" => true,
            ],
        ];
    }
}
