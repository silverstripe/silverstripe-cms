<?php

namespace SilverStripe\CMS\Tests\Controllers;

use SilverStripe\CMS\Controllers\SilverStripeNavigatorItem;
use SilverStripe\Dev\TestOnly;

class SilverStripeNavigatorTest_TestItem extends SilverStripeNavigatorItem implements TestOnly
{
    public function getTitle()
    {
        return self::class;
    }

    public function getHTML()
    {
        return null;
    }
}
