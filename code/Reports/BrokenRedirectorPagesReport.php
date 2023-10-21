<?php

namespace SilverStripe\CMS\Reports;

use SilverStripe\CMS\Model\RedirectorPage;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DB;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Reports\Report;

class BrokenRedirectorPagesReport extends Report
{

    public function title()
    {
        return _t(__CLASS__.'.BROKENREDIRECTORPAGES', 'RedirectorPages pointing to deleted pages');
    }

    public function group()
    {
        return _t(__CLASS__.'.BrokenLinksGroupTitle', "Broken links reports");
    }

    public function sourceRecords($params = null)
    {
        $classes = ClassInfo::subclassesFor(RedirectorPage::class);
        $classParams = DB::placeholders($classes);
        $classFilter = [
            "\"ClassName\" IN ($classParams) AND \"HasBrokenLink\" = 1" => $classes
        ];
        $stage = isset($params['OnLive']) ? 'Live' : 'Stage';
        return Versioned::get_by_stage(SiteTree::class, $stage, $classFilter);
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

    public function getParameterFields()
    {
        return new FieldList(
            new CheckboxField('OnLive', _t(__CLASS__.'.ParameterLiveCheckbox', 'Check live site'))
        );
    }
}
