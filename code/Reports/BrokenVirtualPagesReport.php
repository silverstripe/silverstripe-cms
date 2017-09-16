<?php

namespace SilverStripe\CMS\Reports;

use SilverStripe\CMS\Model\VirtualPage;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DB;
use SilverStripe\Reports\Report;
use SilverStripe\Versioned\Versioned;

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
        $classFilter = [
            "\"ClassName\" IN ($classParams)" => $classes,
            "\"VirtualPage\".\"CopyContentFromID\" NOT IN (SELECT \"ID\" FROM SiteTree)",
        ];

        $stage = isset($params['OnLive']) ? 'Live' : 'Stage';
        return Versioned::get_by_stage(VirtualPage::class, $stage, $classFilter);
    }

    public function columns()
    {
        return array(
            "Title" => array(
                "title" => "Title",
                "link" => true,
            ),
        );
    }

    public function getParameterFields()
    {
        return new FieldList(
            new CheckboxField('OnLive', _t(__CLASS__ . '.ParameterLiveCheckbox', 'Check live site'))
        );
    }
}
