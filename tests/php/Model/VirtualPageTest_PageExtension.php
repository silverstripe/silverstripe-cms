<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataExtension;

class VirtualPageTest_PageExtension extends DataExtension implements TestOnly
{
    private static $db = array(
        // These fields are just on an extension to simulate shared properties between Page and VirtualPage.
        // Not possible through direct $db definitions due to VirtualPage inheriting from Page, and Page being defined elsewhere.
        'MySharedVirtualField' => 'Text',
        'MySharedNonVirtualField' => 'Text',
    );
}
