<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\CMS\Model\VirtualPage;
use SilverStripe\Dev\TestOnly;
use SilverStripe\CMS\Model\SiteTree;

class VirtualPageTest_PageWithAllowedChildren extends SiteTree implements TestOnly
{
    private static $table_name = 'VirtualPageTest_PageWithAllowedChildren';

    private static $allowed_children = [
        VirtualPageTest_ClassA::class,
        VirtualPage::class,
    ];
}
