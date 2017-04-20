<?php

namespace SilverStripe\CMS\Tasks;

use SilverStripe\Control\Controller;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\DataObject;

class SiteTreeMaintenanceTask extends Controller
{
    private static $allowed_actions = array(
        '*' => 'ADMIN'
    );

    public function makelinksunique()
    {
        $badURLs = "'" . implode("', '", DB::query("SELECT URLSegment, count(*) FROM SiteTree GROUP BY URLSegment HAVING count(*) > 1")->column()) . "'";
        $pages = DataObject::get("SilverStripe\\CMS\\Model\\SiteTree", "\"SiteTree\".\"URLSegment\" IN ($badURLs)");

        foreach ($pages as $page) {
            echo "<li>$page->Title: ";
            $urlSegment = $page->URLSegment;
            $page->write();
            if ($urlSegment != $page->URLSegment) {
                echo _t(
                    'SilverStripe\\CMS\\Model\\SiteTree.LINKSCHANGEDTO',
                    " changed {url1} -> {url2}",
                    array('url1' => $urlSegment, 'url2' => $page->URLSegment)
                );
            } else {
                echo _t(
                    'SilverStripe\\CMS\\Model\\SiteTree.LINKSALREADYUNIQUE',
                    " {url} is already unique",
                    array('url' => $urlSegment)
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
