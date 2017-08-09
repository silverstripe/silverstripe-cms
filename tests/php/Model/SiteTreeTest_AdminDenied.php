<?php

use SilverStripe\Dev\TestOnly;

class SiteTreeTest_AdminDenied extends Page implements TestOnly
{
    private static $extensions = array(
        'SiteTreeTest_AdminDeniedExtension'
    );
}
