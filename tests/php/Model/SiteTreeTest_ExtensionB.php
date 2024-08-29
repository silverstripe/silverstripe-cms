<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Core\Extension;

class SiteTreeTest_ExtensionB extends Extension implements TestOnly
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
