<?php

namespace SilverStripe\CMS\Reports;

use SilverStripe\CMS\Model\RedirectorPage;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\CMS\Model\VirtualPage;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Reports\Report;
use SilverStripe\ORM\DB;
use SilverStripe\Versioned\Versioned;

class BrokenFilesReport extends Report
{

    public function title()
    {
        return _t(__CLASS__.'.BROKENFILES', "Pages with broken files");
    }

    public function group()
    {
        return _t(__CLASS__.'.BrokenLinksGroupTitle', "Broken links reports");
    }

    public function sourceRecords($params = null)
    {
        // Get class names for page types that are not virtual pages or redirector pages
        $classes = array_diff(
            ClassInfo::subclassesFor(SiteTree::class) ?? [],
            ClassInfo::subclassesFor(VirtualPage::class),
            ClassInfo::subclassesFor(RedirectorPage::class)
        );
        $classParams = DB::placeholders($classes);
        $classFilter = [
            "\"ClassName\" IN ($classParams) AND \"HasBrokenFile\" = 1" => $classes
        ];

        $stage = isset($params['OnLive']) ? Versioned::LIVE : Versioned::DRAFT;
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
