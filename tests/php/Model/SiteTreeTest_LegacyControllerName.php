<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\Dev\TestOnly;
use Page;

/**
 * An empty SiteTree instance with a controller to test that legacy controller names can still be loaded
 */
class SiteTreeTest_LegacyControllerName extends Page implements TestOnly
{
    private static $table_name = 'SiteTreeTest_LegacyControllerName';
}
