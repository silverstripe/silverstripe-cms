<?php

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\ValidationException;

class CMSMainTest_ClassB extends Page implements TestOnly
{
    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if ($this->ClassName !== self::class) {
            throw new ValidationException("Class saved with incorrect ClassName");
        }
    }
}
