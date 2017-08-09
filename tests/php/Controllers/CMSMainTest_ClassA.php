<?php

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\ValidationException;

class CMSMainTest_ClassA extends Page implements TestOnly
{
    private static $allowed_children = array('CMSMainTest_ClassB');

    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if ($this->ClassName !== self::class) {
            throw new ValidationException("Class saved with incorrect ClassName");
        }
    }
}
