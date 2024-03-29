<?php

namespace SilverStripe\CMS\Tests\Controllers;

use SilverStripe\CMS\Controllers\LeftAndMainPageIconsExtension;
use SilverStripe\Dev\SapphireTest;

class LeftAndMainPageIconsExtensionTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        LeftAndMainPageIconsExtensionTest\ModuleIconA::class,
        LeftAndMainPageIconsExtensionTest\ModuleIconB::class,
        LeftAndMainPageIconsExtensionTest\ModuleIconC::class,
    ];

    public function testGenerateIconCSS()
    {
        $extension = new LeftAndMainPageIconsExtension();
        $css = $extension->generatePageIconsCss();
        $this->assertStringNotContainsString('some invalid string', $css);
        $this->assertStringContainsString(
            'tests/php/Controllers/LeftAndMainPageIconsExtensionTest/icon_b.jpg?m=',
            $css
        );
        $this->assertStringContainsString(
            'tests/php/Controllers/LeftAndMainPageIconsExtensionTest/icon_c.jpg?m=',
            $css
        );
    }
}
