<?php

namespace SilverStripe\CMS\Tasks;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\Deprecation;

/**
 * @deprecated 4.13.0 Will be removed without equivalent functionality to replace it
 */
class SiteTreeMaintenanceTask extends Controller
{
    private static $allowed_actions = [
        '*' => 'ADMIN'
    ];

    public function __construct()
    {
        Deprecation::notice(
            '4.13.0',
            'Will be removed without equivalent functionality to replace it',
            Deprecation::SCOPE_CLASS
        );
    }

    public function makelinksunique()
    {
        $table = DataObject::singleton(SiteTree::class)->baseTable();
        $badURLs = "'" . implode("', '", DB::query("SELECT \"URLSegment\", count(*) FROM \"$table\" GROUP BY \"URLSegment\" HAVING count(*) > 1")->column()) . "'";
        $pages = DataObject::get(SiteTree::class, "\"$table\".\"URLSegment\" IN ($badURLs)");

        foreach ($pages as $page) {
            echo "<li>$page->Title: ";
            $urlSegment = $page->URLSegment;
            $page->write();
            if ($urlSegment != $page->URLSegment) {
                echo _t(
                    'SilverStripe\\CMS\\Model\\SiteTree.LINKSCHANGEDTO',
                    " changed {url1} -> {url2}",
                    ['url1' => $urlSegment, 'url2' => $page->URLSegment]
                );
            } else {
                echo _t(
                    'SilverStripe\\CMS\\Model\\SiteTree.LINKSALREADYUNIQUE',
                    " {url} is already unique",
                    ['url' => $urlSegment]
                );
            }
            die();
        }
    }

    public function Link($action = null)
    {
        /** @skipUpgrade */
        return Controller::join_links('SiteTreeMaintenanceTask', $action, '/');
    }
}
