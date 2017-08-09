<?php

namespace SilverStripe\CMS\Tests;


use SilverStripe\Dev\TestOnly;
use Page;


class VirtualPageTest_ClassC extends Page implements TestOnly
{
    private static $allowed_children = array();
}
