<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\Dev\TestOnly;
use SilverStripe\CMS\Model\SiteTree;

class SiteTreeTest_Conflicted extends SiteTree implements TestOnly
{
    private static $table_name = 'SiteTreeTest_Conflicted';
}
