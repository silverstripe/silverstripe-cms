<?php

namespace SilverStripe\CMS\Tests\Controllers;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\ValidationException;
use Page;

class CMSMainTest_ClassA extends Page implements TestOnly
{
    private static $table_name = 'CMSMainTest_ClassA';

    private static $allowed_children = array(CMSMainTest_ClassB::class);

    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if ($this->ClassName !== self::class) {
            throw new ValidationException("Class saved with incorrect ClassName");
        }
    }
}
