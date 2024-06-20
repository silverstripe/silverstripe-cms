<?php

namespace SilverStripe\CMS\Tests\Controllers;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\ValidationException;

class CMSMainTest_ClassB extends SiteTree implements TestOnly
{
    private static $table_name = 'CMSMainTest_ClassB';

    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if ($this->ClassName !== CMSMainTest_ClassB::class) {
            throw new ValidationException("Class saved with incorrect ClassName");
        }
    }
}
