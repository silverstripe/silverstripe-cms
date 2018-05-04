<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\Dev\TestOnly;
use SilverStripe\CMS\Model\SiteTree;

class VirtualPageTest_ClassB extends SiteTree implements TestOnly
{
    private static $table_name = 'VirtualPageTest_ClassB';

    private static $allowed_children = [
        VirtualPageTest_ClassC::class,
    ];
}
