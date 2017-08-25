<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataExtension;

class SiteTreeTest_Extension extends DataExtension implements TestOnly
{
    public function augmentValidURLSegment()
    {
        return false;
    }
}
