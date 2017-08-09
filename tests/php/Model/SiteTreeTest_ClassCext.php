<?php

use SilverStripe\Dev\TestOnly;

class SiteTreeTest_ClassCext extends SiteTreeTest_ClassC implements TestOnly
{
    // Override SiteTreeTest_ClassC definitions
    private static $allowed_children = array(SiteTreeTest_ClassB::class);
}
