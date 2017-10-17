<?php

namespace SilverStripe\CMS\Tests\Controllers\LeftAndMainpageIconsExtensionTest;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\TestOnly;

class ModuleIconB extends SiteTree implements TestOnly
{
    private static $icon = 'silverstripe/cms:tests/php/Controllers/LeftAndMainPageIconsExtensionTest/icon_b.jpg';
}
