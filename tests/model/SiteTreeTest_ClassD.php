<?php

use SilverStripe\Dev\TestOnly;

class SiteTreeTest_ClassD extends Page implements TestOnly
{
    // Only allows this class, no children classes
    private static $allowed_children = array('*SiteTreeTest_ClassC');

    private static $extensions = [
        'SiteTreeTest_ExtensionA',
        'SiteTreeTest_ExtensionB',
    ];

    public $canEditValue = null;

    public function canEdit($member = null)
    {
        return isset($this->canEditValue)
            ? $this->canEditValue
            : parent::canEdit($member);
    }
}
