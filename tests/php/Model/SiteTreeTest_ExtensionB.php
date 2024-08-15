<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\CMS\Model\SiteTreeExtension;
use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;

class SiteTreeTest_ExtensionB extends SiteTreeExtension implements TestOnly
{
    public static $can_publish = true;

    protected function canPublish($member)
    {
        return static::$can_publish;
    }

    protected function updateLink(&$link, $action = null)
    {
        $link = Controller::join_links('http://www.updatedhost.com', $link);
    }
}
