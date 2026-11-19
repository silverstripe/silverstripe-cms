<?php

namespace SilverStripe\CMS\Tests\Controllers\PageTreeControllerTest;

use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;

class DenyAccessExtension extends Extension implements TestOnly
{
    public function alternateAccessCheck(): bool
    {
        return false;
    }
}
