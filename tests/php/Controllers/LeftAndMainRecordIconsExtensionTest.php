<?php

namespace SilverStripe\CMS\Tests\Controllers;

use SilverStripe\CMS\Controllers\LeftAndMainRecordIconsExtension;
use SilverStripe\Dev\SapphireTest;

class LeftAndMainRecordIconsExtensionTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        LeftAndMainRecordIconsExtensionTest\ModuleIconA::class,
        LeftAndMainRecordIconsExtensionTest\ModuleIconB::class,
        LeftAndMainRecordIconsExtensionTest\ModuleIconC::class,
        LeftAndMainRecordIconsExtensionTest\ModuleIconD::class,
    ];

    public function testGenerateIconCSS()
    {
        $extension = new LeftAndMainRecordIconsExtension();
        $css = $extension->generateRecordIconsCss();
        $this->assertStringNotContainsString('some invalid string', $css);
        $this->assertStringContainsString(
            'tests/php/Controllers/LeftAndMainRecordIconsExtensionTest/icon_b.jpg?m=',
            $css
        );
        $this->assertStringContainsString(
            'tests/php/Controllers/LeftAndMainRecordIconsExtensionTest/icon_c.jpg?m=',
            $css
        );
        $this->assertStringNotContainsString(
            'tests/php/Controllers/LeftAndMainRecordIconsExtensionTest/icon_d.jpg?m=',
            $css
        );
    }
}
