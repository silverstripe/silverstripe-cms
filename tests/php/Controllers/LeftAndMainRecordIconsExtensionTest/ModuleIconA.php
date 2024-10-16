<?php

namespace SilverStripe\CMS\Tests\Controllers\LeftAndMainRecordIconsExtensionTest;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\TestOnly;

class ModuleIconA extends SiteTree implements TestOnly
{
    private static $cms_icon = 'some invalid string';
}
