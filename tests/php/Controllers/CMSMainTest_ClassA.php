<?php

namespace SilverStripe\CMS\Tests\Controllers;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\ValidationException;

class CMSMainTest_ClassA extends SiteTree implements TestOnly
{
    private static $table_name = 'CMSMainTest_ClassA';

    private static $allowed_children = [CMSMainTest_ClassB::class];

    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if ($this->ClassName !== self::class) {
            throw new ValidationException("Class saved with incorrect ClassName");
        }
    }
}
