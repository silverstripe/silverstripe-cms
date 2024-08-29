<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Core\Extension;

class VirtualPageTest_PageExtension extends Extension implements TestOnly
{
    private static $db = [
        // These fields are just on an extension to simulate shared properties between Page and VirtualPage.
        // Not possible through direct $db definitions due to VirtualPage inheriting from Page,
        // and Page being defined elsewhere.
        'MySharedVirtualField' => 'Text',
        'MySharedNonVirtualField' => 'Text',
    ];
}
