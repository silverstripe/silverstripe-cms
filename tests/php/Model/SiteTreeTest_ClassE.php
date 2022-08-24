<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\HiddenClass;
use SilverStripe\CMS\Model\SiteTree;

class SiteTreeTest_ClassE extends SiteTree implements TestOnly, HiddenClass
{
    private static $table_name = 'SiteTreeTest_ClassE';
}
