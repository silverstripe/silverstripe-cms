<?php

use SilverStripe\Dev\TestOnly;

class SiteTreeTest_ClassA extends Page implements TestOnly
{
    private static $need_permission = [
        'ADMIN',
        'CMS_ACCESS_CMSMain'
    ];

    private static $allowed_children = [
        SiteTreeTest_ClassB::class
    ];
}
