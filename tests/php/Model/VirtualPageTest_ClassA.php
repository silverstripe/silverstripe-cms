<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\Dev\TestOnly;
use SilverStripe\CMS\Model\SiteTree;

class VirtualPageTest_ClassA extends SiteTree implements TestOnly
{
    private static $table_name = 'VirtualPageTest_ClassA';

    private static $db = [
        'MyInitiallyCopiedField' => 'Text',
        'MyVirtualField' => 'Text',
        'MyNonVirtualField' => 'Text',
        'CastingTest' => VirtualPageTest_TestDBField::class,
    ];

    private static $allowed_children = [
        VirtualPageTest_ClassB::class,
    ];

    public function modelMethod()
    {
        return 'hi there';
    }
}
