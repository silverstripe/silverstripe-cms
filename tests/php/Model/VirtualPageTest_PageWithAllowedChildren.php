<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\CMS\Model\VirtualPage;
use SilverStripe\Dev\TestOnly;
use Page;

class VirtualPageTest_PageWithAllowedChildren extends Page implements TestOnly
{
    private static $table_name = 'VirtualPageTest_PageWithAllowedChildren';

    private static $allowed_children = array(
        VirtualPageTest_ClassA::class,
        VirtualPage::class,
    );
}
