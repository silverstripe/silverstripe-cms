<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\Dev\TestOnly;
use Page;

class VirtualPageTest_ClassC extends Page implements TestOnly
{
    private static $table_name = 'VirtualPageTest_ClassC';

    private static $allowed_children = array();
}
