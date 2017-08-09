<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\Dev\TestOnly;
use Page;

class VirtualPageTest_ClassB extends Page implements TestOnly
{
    private static $table_name = 'VirtualPageTest_ClassB';

    private static $allowed_children = [
        VirtualPageTest_ClassC::class,
    ];
}
