<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\Dev\TestOnly;

class SiteTreeTest_ClassCext extends SiteTreeTest_ClassC implements TestOnly
{
    private static $table_name = 'SiteTreeTest_ClassCext';

    // Override SiteTreeTest_ClassC definitions
    private static $allowed_children = [
        SiteTreeTest_ClassB::class
    ];
}
