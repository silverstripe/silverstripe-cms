<?php

use SilverStripe\Dev\TestOnly;

class VirtualPageTest_ClassAController extends PageController implements TestOnly
{
    private static $allowed_actions = [
        'testaction'
    ];

    public function testMethod()
    {
        return 'hello';
    }
}
