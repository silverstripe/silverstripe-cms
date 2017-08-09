<?php

namespace SilverStripe\CMS\Tests;


use SilverStripe\Dev\TestOnly;
use Page;


class VirtualPageTest_ClassB extends Page implements TestOnly
{
    private static $allowed_children = array('VirtualPageTest_ClassC');
}
