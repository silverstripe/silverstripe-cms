<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\Dev\TestOnly;
use Page;

class SiteTreeTest_NotRoot extends Page implements TestOnly
{
    private static $table_name = 'SiteTreeTest_NotRoot';

    private static $can_be_root = false;
}
