<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\HiddenClass;
use Page;

class SiteTreeTest_ClassE extends Page implements TestOnly, HiddenClass
{
    private static $table_name = 'SiteTreeTest_ClassE';
}
