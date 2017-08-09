<?php

namespace SilverStripe\CMS\Tests;


use SilverStripe\Dev\TestOnly;
use Page;


class SiteTreeTest_AdminDenied extends Page implements TestOnly
{
    private static $extensions = array(
        'SiteTreeTest_AdminDeniedExtension'
    );
}
