<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\Dev\TestOnly;
use SilverStripe\CMS\Model\SiteTree;

class SiteTreeTest_ClassC extends SiteTree implements TestOnly
{
    private static $table_name = 'SiteTreeTest_ClassC';

    private static $allowed_children = [];
}
