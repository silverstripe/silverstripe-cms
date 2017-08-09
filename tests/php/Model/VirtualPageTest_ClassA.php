<?php

namespace SilverStripe\CMS\Tests;


use SilverStripe\Dev\TestOnly;
use Page;


class VirtualPageTest_ClassA extends Page implements TestOnly
{
    private static $db = array(
        'MyInitiallyCopiedField' => 'Text',
        'MyVirtualField' => 'Text',
        'MyNonVirtualField' => 'Text',
        'CastingTest' => 'VirtualPageTest_TestDBField'
    );

    private static $allowed_children = array('VirtualPageTest_ClassB');

    public function modelMethod()
    {
        return 'hi there';
    }
}
