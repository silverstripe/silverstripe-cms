<?php

namespace SilverStripe\CMS\Tests\Controllers\LeftAndMainpageIconsExtensionTest;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\TestOnly;

class ModuleIconA extends SiteTree implements TestOnly
{
    private static $icon = 'some invalid string';
}
