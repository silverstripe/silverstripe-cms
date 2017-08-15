<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\Dev\TestOnly;
use Page;

class SiteTreeTest_ClassC extends Page implements TestOnly
{
    private static $table_name = 'SiteTreeTest_ClassC';

    private static $allowed_children = array();
}
