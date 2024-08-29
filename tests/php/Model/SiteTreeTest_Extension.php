<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Core\Extension;

class SiteTreeTest_Extension extends Extension implements TestOnly
{
    protected function augmentValidURLSegment()
    {
        return false;
    }
}
