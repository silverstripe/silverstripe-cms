<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\Dev\TestOnly;
use PageController;

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
