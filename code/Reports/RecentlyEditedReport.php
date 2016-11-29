<?php

namespace SilverStripe\CMS\Reports;

use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\DataObject;
use SilverStripe\Reports\Report;

class RecentlyEditedReport extends Report {

    public function title() {
        return _t('SideReport.LAST2WEEKS',"Pages edited in the last 2 weeks");
    }

    public function group() {
        return _t('SideReport.ContentGroupTitle', "Content reports");
    }

    public function sort() {
        return 200;
    }

    public function sourceRecords($params = null) {
        $threshold = strtotime('-14 days', DBDatetime::now()->Format('U'));
        return DataObject::get("SilverStripe\\CMS\\Model\\SiteTree", "\"SiteTree\".\"LastEdited\" > '".date("Y-m-d H:i:s", $threshold)."'", "\"SiteTree\".\"LastEdited\" DESC");
    }

    public function columns() {
        return array(
            "Title" => array(
                "title" => "Title", // todo: use NestedTitle(2)
                "link" => true,
            ),
        );
    }
}
