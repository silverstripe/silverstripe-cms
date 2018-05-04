<?php

namespace SilverStripe\CMS\Tests\Controllers;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\TestOnly;

class CMSMainTest_NotRoot extends SiteTree implements TestOnly
{
    private static $table_name = 'CMSMainTest_NotRoot';

    private static $can_be_root = false;
}
