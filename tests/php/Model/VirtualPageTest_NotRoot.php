<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\Dev\TestOnly;
use SilverStripe\CMS\Model\SiteTree;

class VirtualPageTest_NotRoot extends SiteTree implements TestOnly
{
    private static $table_name = 'VirtualPageTest_NotRoot';

    private static $can_be_root = false;
}
