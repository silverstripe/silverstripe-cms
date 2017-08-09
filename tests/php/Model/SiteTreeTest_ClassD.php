<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\Dev\TestOnly;
use Page;

class SiteTreeTest_ClassD extends Page implements TestOnly
{
    private static $table_name = 'SiteTreeTest_ClassD';

    // Only allows this class, no children classes
    private static $allowed_children = [
        '*' . SiteTreeTest_ClassC::class,
    ];

    private static $extensions = [
        SiteTreeTest_ExtensionA::class,
        SiteTreeTest_ExtensionB::class,
    ];

    public $canEditValue = null;

    public function canEdit($member = null)
    {
        return isset($this->canEditValue)
            ? $this->canEditValue
            : parent::canEdit($member);
    }
}
