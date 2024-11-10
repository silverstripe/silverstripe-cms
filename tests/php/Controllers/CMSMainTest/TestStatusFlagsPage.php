<?php

namespace SilverStripe\CMS\Tests\Controllers\CMSMainTest;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\TestOnly;

class TestStatusFlagsPage extends SiteTree implements TestOnly
{
    public function getStatusFlags(bool $cached = true): array
    {
        $flags = parent::getStatusFlags($cached);
        $flags['my-flag'] = 'test-flag';
        return $flags;
    }
}
