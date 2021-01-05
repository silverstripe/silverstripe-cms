<?php

namespace SilverStripe\CMS\Reports;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\CMS\Model\VirtualPage;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DB;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Reports\Report;

class BrokenVirtualPagesReport extends Report
{

    public function title()
    {
        return _t(__CLASS__ . '.BROKENVIRTUALPAGES', 'VirtualPages pointing to deleted pages');
    }

    public function group()
    {
        return _t(__CLASS__ . '.BrokenLinksGroupTitle', "Broken links reports");
    }

    public function sourceRecords($params = null)
    {
        $classes = ClassInfo::subclassesFor(VirtualPage::class);
        $classParams = DB::placeholders($classes);
        $classFilter = array(
            "\"ClassName\" IN ($classParams) AND \"HasBrokenLink\" = 1" => $classes
        );
        $stage = isset($params['OnLive']) ? 'Live' : 'Stage';
        return Versioned::get_by_stage(SiteTree::class, $stage, $classFilter);
    }

    public function columns()
    {
        return array(
            "Title" => array(
                "title" => "Title", // todo: use NestedTitle(2)
                "link" => true,
            ),
        );
    }
}
