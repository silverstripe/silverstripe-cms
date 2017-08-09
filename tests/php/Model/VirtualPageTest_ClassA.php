<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\Dev\TestOnly;
use Page;

class VirtualPageTest_ClassA extends Page implements TestOnly
{
    private static $table_name = 'VirtualPageTest_ClassA';

    private static $db = array(
        'MyInitiallyCopiedField' => 'Text',
        'MyVirtualField' => 'Text',
        'MyNonVirtualField' => 'Text',
        'CastingTest' => VirtualPageTest_TestDBField::class,
    );

    private static $allowed_children = [
        VirtualPageTest_ClassB::class,
    ];

    public function modelMethod()
    {
        return 'hi there';
    }
}
