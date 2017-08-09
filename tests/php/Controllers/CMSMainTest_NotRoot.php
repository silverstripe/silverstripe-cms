<?php

namespace SilverStripe\CMS\Tests\Controllers;

use SilverStripe\Dev\TestOnly;
use Page;

class CMSMainTest_NotRoot extends Page implements TestOnly
{
    private static $table_name = 'CMSMainTest_NotRoot';

    private static $can_be_root = false;
}
