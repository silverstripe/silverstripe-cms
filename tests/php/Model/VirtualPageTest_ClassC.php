<?php

use SilverStripe\Dev\TestOnly;

class VirtualPageTest_ClassC extends Page implements TestOnly
{
    private static $allowed_children = array();
}
