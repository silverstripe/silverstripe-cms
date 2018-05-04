<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\Dev\TestOnly;
use SilverStripe\CMS\Model\SiteTree;

class SiteTreeTest_AdminDenied extends SiteTree implements TestOnly
{
    private static $table_name = 'SiteTreeTest_AdminDenied';

    private static $extensions = [
        SiteTreeTest_AdminDeniedExtension::class,
    ];
}
