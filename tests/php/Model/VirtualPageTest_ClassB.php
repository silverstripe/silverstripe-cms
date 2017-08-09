<?php

use SilverStripe\Dev\TestOnly;

class VirtualPageTest_ClassB extends Page implements TestOnly
{
    private static $allowed_children = array('VirtualPageTest_ClassC');
}
