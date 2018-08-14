<?php

namespace SilverStripe\CMS\Reports;

use SilverStripe\CMS\Controllers\CMSPageEditController;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Reports\Report;
use SilverStripe\Security\Member;
use SilverStripe\Versioned\Versioned;

/**
 * Provides some common functionality for all CMS module reports
 */
abstract class AbstractCMSReport extends Report
{
    /**
     * By default we provide the page title with a clickable link, the last edited date and the member's name
     * who last edited the page.
     *
     * @return array[]
     */
    public function columns()
    {
        // @todo remove coupling to global state
        $isDraft = isset($_REQUEST['filters']['CheckSite']) && $_REQUEST['filters']['CheckSite'] === 'Draft';
        if ($isDraft) {
            $dateTitle = _t(__CLASS__ . '.ColumnDateLastModified', 'Date last modified');
        } else {
            $dateTitle = _t(__CLASS__ . '.ColumnDateLastPublished', 'Date last published');
        }
        $linkBase = CMSPageEditController::singleton()->Link('show');

        return [
            'Title' => [
                'title' => _t(__CLASS__ . '.PageName', 'Page name'),
                'formatting' => function ($value, $item) use ($linkBase) {
                    return sprintf(
                        '<a href="%s" title="%s">%s</a>',
                        Controller::join_links($linkBase, $item->ID),
                        _t(__CLASS__ . '.HoverTitleEditPage', 'Edit page'),
                        $value
                    );
                },
            ],
            'LastEdited' => [
                'title' => $dateTitle,
                'casting' => 'DBDatetime->Full',
            ],
            'AuthorID' => [
                'title' =>  _t(__CLASS__ . '.LastEditor', 'Last Editor'),
                'formatting' => function ($value, SiteTree $item) {
                    $latestVersion = Versioned::get_latest_version(SiteTree::class, $item->ID);
                    if (!$latestVersion) {
                        return '';
                    }

                    /** @var Member $member */
                    $member = Member::get()->byID($latestVersion->AuthorID);
                    if ($member) {
                        return sprintf('%s %s', $member->FirstName, $member->Surname);
                    }
                    return '';
                },
            ],
        ];
    }
}
