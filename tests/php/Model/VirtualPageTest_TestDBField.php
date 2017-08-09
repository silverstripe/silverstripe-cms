<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\FieldType\DBVarchar;

class VirtualPageTest_TestDBField extends DBVarchar implements TestOnly
{
    public function forTemplate()
    {
        return strtoupper($this->XML());
    }
}
