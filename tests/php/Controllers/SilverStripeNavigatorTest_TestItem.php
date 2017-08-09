<?php

namespace SilverStripe\CMS\Tests;


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
