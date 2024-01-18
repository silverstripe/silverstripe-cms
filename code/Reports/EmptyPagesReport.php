<?php

namespace SilverStripe\CMS\Reports;

use SilverStripe\CMS\Model\RedirectorPage;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\ORM\DataList;
use SilverStripe\Reports\Report;

class EmptyPagesReport extends Report
{
    public function title()
    {
        return _t(__CLASS__.'.EMPTYPAGES', "Pages without content");
    }

    public function group()
    {
        return _t(__CLASS__.'.ContentGroupTitle', "Content reports");
    }

    public function sort()
    {
        return 100;
    }

    /**
     * Gets the source records
     *
     * @param array $params
     * @return DataList<SiteTree>
     */
    public function sourceRecords($params = null)
    {
        return SiteTree::get()
            ->exclude('ClassName', RedirectorPage::class)
            ->filter('Content', [null, '', '<p></p>', '<p>&nbsp;</p>'])
            ->sort('Title');
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
