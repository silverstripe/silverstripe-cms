<?php

namespace SilverStripe\CMS\Tests;


use SilverStripe\CMS\Model\SiteTreeExtension;
use SilverStripe\Dev\TestOnly;



class SiteTreeTest_ExtensionA extends SiteTreeExtension implements TestOnly
{
    public static $can_publish = true;

    public function canPublish($member)
    {
        return static::$can_publish;
    }
}
