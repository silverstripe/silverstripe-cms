<?php

namespace SilverStripe\CMS\Tests;


use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\TestOnly;



class SiteTreeTest_StageStatusInherit extends SiteTree implements TestOnly
{
    public function getStatusFlags($cached = true)
    {
        $flags = parent::getStatusFlags($cached);
        $flags['inherited-class'] = "InheritedTitle";
        return $flags;
    }
}
