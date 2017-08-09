<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\TestOnly;

class SiteTreeTest_StageStatusInherit extends SiteTree implements TestOnly
{
    private static $table_name = 'SiteTreeTest_StageStatusInherit';

    public function getStatusFlags($cached = true)
    {
        $flags = parent::getStatusFlags($cached);
        $flags['inherited-class'] = "InheritedTitle";
        return $flags;
    }
}
