<?php

namespace SilverStripe\CMS\Tests;


use SilverStripe\Dev\TestOnly;
use Page;


class SiteTreeTest_ClassB extends Page implements TestOnly
{
    // Also allowed subclasses
    private static $allowed_children = array(SiteTreeTest_ClassC::class);
}
