<?php

namespace SilverStripe\CMS\Tests\Controllers\LeftAndMainpageIconsExtensionTest;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\TestOnly;

class ModuleIconC extends SiteTree implements TestOnly
{
    private static $extensions = [
        ModuleIconExtension::class,
    ];
}
