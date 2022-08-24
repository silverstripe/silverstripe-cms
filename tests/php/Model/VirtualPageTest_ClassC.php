<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\Dev\TestOnly;
use SilverStripe\CMS\Model\SiteTree;

class VirtualPageTest_ClassC extends SiteTree implements TestOnly
{
    private static $table_name = 'VirtualPageTest_ClassC';

    private static $allowed_children = [];
}
