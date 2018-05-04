<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\Dev\TestOnly;
use SilverStripe\CMS\Controllers\ContentController;

class VirtualPageTest_ClassAController extends ContentController implements TestOnly
{
    private static $allowed_actions = [
        'testaction',
    ];

    public function testMethod()
    {
        return 'hello';
    }
}
