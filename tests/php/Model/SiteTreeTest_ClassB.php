<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\Dev\TestOnly;
use Page;

class SiteTreeTest_ClassB extends Page implements TestOnly
{
    private static $table_name = 'SiteTreeTest_ClassB';

    // Also allowed subclasses
    private static $allowed_children = [
        SiteTreeTest_ClassC::class
    ];
}
