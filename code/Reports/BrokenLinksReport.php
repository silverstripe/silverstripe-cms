<?php

namespace SilverStripe\CMS\Reports;

use SilverStripe\CMS\Controllers\CMSPageEditController;
use SilverStripe\CMS\Model\RedirectorPage;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\CMS\Model\VirtualPage;
use SilverStripe\Control\Controller;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Versioned\Versioned;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Reports\Report;

if (!class_exists(Report::class)) {
    return;
}

/**
 * Content side-report listing pages with broken links
 */
class BrokenLinksReport extends Report
{

    public function title()
    {
        return _t(__CLASS__ . '.BROKENLINKS', "Broken links report");
    }

    public function sourceRecords($params, $sort, $limit)
    {
        $join = '';
        $sortBrokenReason = false;
        if ($sort) {
            $parts = explode(' ', $sort);
            $field = $parts[0];
            $direction = $parts[1];

            if ($field == 'AbsoluteLink') {
                $sort = 'URLSegment ' . $direction;
            } elseif ($field == 'Subsite.Title') {
                $join = 'LEFT JOIN "Subsite" ON "Subsite"."ID" = "SiteTree"."SubsiteID"';
            } elseif ($field == 'BrokenReason') {
                $sortBrokenReason = true;
                $sort = '';
            }
        }
        $brokenFilter = array(
            '"SiteTree"."HasBrokenLink" = ? OR "SiteTree"."HasBrokenFile" = ?' => array(true, true)
        );
        $isLive = !isset($params['CheckSite']) || $params['CheckSite'] == 'Published';
        if ($isLive) {
            $ret = Versioned::get_by_stage(SiteTree::class, 'Live', $brokenFilter, $sort, $join, $limit);
        } else {
            $ret = DataObject::get(SiteTree::class, $brokenFilter, $sort, $join, $limit);
        }

        $returnSet = new ArrayList();
        if ($ret) {
            foreach ($ret as $record) {
                $reason = false;
                $isRedirectorPage = $record instanceof RedirectorPage;
                $isVirtualPage = $record instanceof VirtualPage;
                $reasonCodes = [];
                if ($isVirtualPage) {
                    if ($record->HasBrokenLink) {
                        $reason = _t(__CLASS__ . '.VirtualPageNonExistent', "virtual page pointing to non-existent page");
                        $reasonCodes = array("VPBROKENLINK");
                    }
                } elseif ($isRedirectorPage) {
                    if ($record->HasBrokenLink) {
                        $reason = _t(__CLASS__ . '.RedirectorNonExistent', "redirector page pointing to non-existent page");
                        $reasonCodes = array("RPBROKENLINK");
                    }
                } else {
                    if ($record->HasBrokenLink && $record->HasBrokenFile) {
                        $reason = _t(__CLASS__ . '.HasBrokenLinkAndFile', "has broken link and file");
                        $reasonCodes = array("BROKENFILE", "BROKENLINK");
                    } elseif ($record->HasBrokenLink && !$record->HasBrokenFile) {
                        $reason = _t(__CLASS__ . '.HasBrokenLink', "has broken link");
                        $reasonCodes = array("BROKENLINK");
                    } elseif (!$record->HasBrokenLink && $record->HasBrokenFile) {
                        $reason = _t(__CLASS__ . '.HasBrokenFile', "has broken file");
                        $reasonCodes = array("BROKENFILE");
                    }
                }

                if ($reason) {
                    if (isset($params['Reason']) && $params['Reason'] && !in_array($params['Reason'], $reasonCodes)) {
                        continue;
                    }
                    $record->BrokenReason = $reason;
                    $returnSet->push($record);
                }
            }
        }

        if ($sortBrokenReason) {
            $returnSet = $returnSet->sort('BrokenReason', $direction);
        }

        return $returnSet;
    }
    public function columns()
    {
        if (isset($_REQUEST['filters']['CheckSite']) && $_REQUEST['filters']['CheckSite'] == 'Draft') {
            $dateTitle = _t(__CLASS__ . '.ColumnDateLastModified', 'Date last modified');
        } else {
            $dateTitle = _t(__CLASS__ . '.ColumnDateLastPublished', 'Date last published');
        }

        $linkBase = CMSPageEditController::singleton()->Link('show');
        $fields = array(
            "Title" => array(
                "title" => _t(__CLASS__ . '.PageName', 'Page name'),
                'formatting' => function ($value, $item) use ($linkBase) {
                    return sprintf(
                        '<a href="%s" title="%s">%s</a>',
                        Controller::join_links($linkBase, $item->ID),
                        _t(__CLASS__ . '.HoverTitleEditPage', 'Edit page'),
                        $value
                    );
                }
            ),
            "LastEdited" => array(
                "title" => $dateTitle,
                'casting' => 'DBDatetime->Full'
            ),
            "BrokenReason" => array(
                "title" => _t(__CLASS__ . '.ColumnProblemType', "Problem type")
            ),
            'AbsoluteLink' => array(
                'title' => _t(__CLASS__ . '.ColumnURL', 'URL'),
                'formatting' => function ($value, $item) {
                    /** @var SiteTree $item */
                    $liveLink = $item->AbsoluteLiveLink;
                    $stageLink = $item->AbsoluteLink();
                    return sprintf(
                        '%s <a href="%s">%s</a>',
                        $stageLink,
                        $liveLink ? $liveLink : Controller::join_links($stageLink, '?stage=Stage'),
                        $liveLink ? '(live)' : '(draft)'
                    );
                }
            )
        );

        return $fields;
    }
    public function parameterFields()
    {
        return new FieldList(
            new DropdownField('CheckSite', _t(__CLASS__ . '.CheckSite', 'Check site'), array(
                'Published' => _t(__CLASS__ . '.CheckSiteDropdownPublished', 'Published Site'),
                'Draft' => _t(__CLASS__ . '.CheckSiteDropdownDraft', 'Draft Site')
            )),
            new DropdownField(
                'Reason',
                _t(__CLASS__ . '.ReasonDropdown', 'Problem to check'),
                array(
                    '' => _t(__CLASS__ . '.Any', 'Any'),
                    'BROKENFILE' => _t(__CLASS__ . '.ReasonDropdownBROKENFILE', 'Broken file'),
                    'BROKENLINK' => _t(__CLASS__ . '.ReasonDropdownBROKENLINK', 'Broken link'),
                    'VPBROKENLINK' => _t(__CLASS__ . '.ReasonDropdownVPBROKENLINK', 'Virtual page pointing to non-existent page'),
                    'RPBROKENLINK' => _t(__CLASS__ . '.ReasonDropdownRPBROKENLINK', 'Redirector page pointing to non-existent page'),
                )
            )
        );
    }
}
