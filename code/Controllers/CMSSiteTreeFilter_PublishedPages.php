<?php

namespace SilverStripe\CMS\Controllers;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\ORM\SS_List;
use SilverStripe\Versioned\Versioned;

/**
 * This filter will display the SiteTree as a site visitor might see the site, i.e only the
 * pages that is currently published.
 *
 * Note that this does not check canView permissions that might hide pages from certain visitors
 */
class CMSSiteTreeFilter_PublishedPages extends CMSSiteTreeFilter
{

    /**
     * @return string
     */
    public static function title()
    {
        return _t(__CLASS__ . '.Title', "Published pages");
    }

    /**
     * @var string
     * @deprecated 5.4.0 Will be removed without equivalent functionality to replace it in a future major release.
     */
    protected $childrenMethod = "AllHistoricalChildren";

    /**
     * @var string
     * @deprecated 5.4.0 Will be removed without equivalent functionality to replace it in a future major release.
     */
    protected $numChildrenMethod = 'numHistoricalChildren';

    /**
     * Filters out all pages who's status who's status that doesn't exist on live
     *
     * @see {@link SiteTree::getStatusFlags()}
     * @return SS_List
     */
    public function getFilteredPages()
    {
        $pages = Versioned::get_including_deleted(SiteTree::class)
            ->innerJoin(
                'SiteTree_Live',
                '"SiteTree_Versions"."RecordID" = "SiteTree_Live"."ID"'
            );

        $pages = $this->applyDefaultFilters($pages);

        return $pages;
    }
}
