<?php

namespace SilverStripe\CMS\Tests\Controllers\LeftAndMainpageIconsExtensionTest;

use SilverStripe\CMS\Model\SiteTreeExtension;
use SilverStripe\Core\Manifest\ModuleResourceLoader;
use SilverStripe\Dev\TestOnly;

class ModuleIconExtension extends SiteTreeExtension implements TestOnly
{
    public static function get_extra_config()
    {
        // Mock a "fixed" path, but use a non-fixed resource url
        $path = ModuleResourceLoader::resourcePath(
            'silverstripe/cms:tests/php/Controllers/LeftAndMainPageIconsExtensionTest/icon_c.jpg'
        );
        return [
            'icon' => $path,
        ];
    }
}
