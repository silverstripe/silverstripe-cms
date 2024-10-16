<?php

namespace SilverStripe\CMS\Tests\Controllers\LeftAndMainRecordIconsExtensionTest;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\TestOnly;

class ModuleIconD extends SiteTree implements TestOnly
{
    private static $cms_icon = 'silverstripe/cms:tests/php/Controllers/LeftAndMainRecordIconsExtensionTest/icon_d.jpg';

    private static $cms_icon_class = 'some-class';
}
