<?php

namespace SilverStripe\CMS\Tests\Controllers\LeftAndMainRecordIconsExtensionTest;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\TestOnly;

class ModuleIconC extends SiteTree implements TestOnly
{
    private static $extensions = [
        ModuleIconExtension::class,
    ];
}
