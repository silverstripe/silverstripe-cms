<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\Dev\TestOnly;
use Page;

class SiteTreeTest_AdminDenied extends Page implements TestOnly
{
    private static $table_name = 'SiteTreeTest_AdminDenied';

    private static $extensions = [
        SiteTreeTest_AdminDeniedExtension::class,
    ];
}
