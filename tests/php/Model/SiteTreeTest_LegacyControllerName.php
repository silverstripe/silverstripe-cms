<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\Dev\TestOnly;
use SilverStripe\CMS\Model\SiteTree;

/**
 * An empty SiteTree instance with a controller to test that legacy controller names can still be loaded
 */
class SiteTreeTest_LegacyControllerName extends SiteTree implements TestOnly
{
    private static $table_name = 'SiteTreeTest_LegacyControllerName';
}
