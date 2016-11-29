<?php

namespace SilverStripe\CMS\Reports;

use SilverStripe\CMS\Controllers\CMSPageEditController;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\Versioning\Versioned;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Reports\Report;

/**
 * Content side-report listing pages with broken links
 */
class BrokenLinksReport extends Report
{

    public function title()
    {
        return _t('BrokenLinksReport.BROKENLINKS', "Broken links report");
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
            $ret = Versioned::get_by_stage('SilverStripe\\CMS\\Model\\SiteTree', 'Live', $brokenFilter, $sort, $join, $limit);
        } else {
            $ret = DataObject::get('SilverStripe\\CMS\\Model\\SiteTree', $brokenFilter, $sort, $join, $limit);
        }

        $returnSet = new ArrayList();
        if ($ret) {
            foreach ($ret as $record) {
                $reason = false;
                $isRedirectorPage = in_array($record->ClassName, ClassInfo::subclassesFor('SilverStripe\\CMS\\Model\\RedirectorPage'));
                $isVirtualPage = in_array($record->ClassName, ClassInfo::subclassesFor('SilverStripe\\CMS\\Model\\VirtualPage'));

                $reasonCodes = [];
                if ($isVirtualPage) {
                    if ($record->HasBrokenLink) {
                        $reason = _t('BrokenLinksReport.VirtualPageNonExistent', "virtual page pointing to non-existent page");
                        $reasonCodes = array("VPBROKENLINK");
                    }
                } elseif ($isRedirectorPage) {
                    if ($record->HasBrokenLink) {
                        $reason = _t('BrokenLinksReport.RedirectorNonExistent', "redirector page pointing to non-existent page");
                        $reasonCodes = array("RPBROKENLINK");
                    }
                } else {
                    if ($record->HasBrokenLink && $record->HasBrokenFile) {
                        $reason = _t('BrokenLinksReport.HasBrokenLinkAndFile', "has broken link and file");
                        $reasonCodes = array("BROKENFILE", "BROKENLINK");
                    } elseif ($record->HasBrokenLink && !$record->HasBrokenFile) {
                        $reason = _t('BrokenLinksReport.HasBrokenLink', "has broken link");
                        $reasonCodes = array("BROKENLINK");
                    } elseif (!$record->HasBrokenLink && $record->HasBrokenFile) {
                        $reason = _t('BrokenLinksReport.HasBrokenFile', "has broken file");
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
            $dateTitle = _t('BrokenLinksReport.ColumnDateLastModified', 'Date last modified');
        } else {
            $dateTitle = _t('BrokenLinksReport.ColumnDateLastPublished', 'Date last published');
        }

        $linkBase = CMSPageEditController::singleton()->Link('show');
        $fields = array(
            "Title" => array(
                "title" => _t('BrokenLinksReport.PageName', 'Page name'),
                'formatting' => function ($value, $item) use ($linkBase) {
                    return sprintf(
                        '<a href="%s" title="%s">%s</a>',
                        Controller::join_links($linkBase, $item->ID),
                        _t('BrokenLinksReport.HoverTitleEditPage', 'Edit page'),
                        $value
                    );
                }
            ),
            "LastEdited" => array(
                "title" => $dateTitle,
                'casting' => 'DBDatetime->Full'
            ),
            "BrokenReason" => array(
                "title" => _t('BrokenLinksReport.ColumnProblemType', "Problem type")
            ),
            'AbsoluteLink' => array(
                'title' => _t('BrokenLinksReport.ColumnURL', 'URL'),
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
            new DropdownField('CheckSite', _t('BrokenLinksReport.CheckSite', 'Check site'), array(
                'Published' => _t('BrokenLinksReport.CheckSiteDropdownPublished', 'Published Site'),
                'Draft' => _t('BrokenLinksReport.CheckSiteDropdownDraft', 'Draft Site')
            )),
            new DropdownField(
                'Reason',
                _t('BrokenLinksReport.ReasonDropdown', 'Problem to check'),
                array(
                    '' => _t('BrokenLinksReport.Any', 'Any'),
                    'BROKENFILE' => _t('BrokenLinksReport.ReasonDropdownBROKENFILE', 'Broken file'),
                    'BROKENLINK' => _t('BrokenLinksReport.ReasonDropdownBROKENLINK', 'Broken link'),
                    'VPBROKENLINK' => _t('BrokenLinksReport.ReasonDropdownVPBROKENLINK', 'Virtual page pointing to non-existent page'),
                    'RPBROKENLINK' => _t('BrokenLinksReport.ReasonDropdownRPBROKENLINK', 'Redirector page pointing to non-existent page'),
                )
            )
        );
    }
}
