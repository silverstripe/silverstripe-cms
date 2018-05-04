<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\Dev\TestOnly;
use SilverStripe\CMS\Model\SiteTree;

class SiteTreeTest_ClassB extends SiteTree implements TestOnly
{
    private static $table_name = 'SiteTreeTest_ClassB';

    // Also allowed subclasses
    private static $allowed_children = [
        SiteTreeTest_ClassC::class,
    ];
}
