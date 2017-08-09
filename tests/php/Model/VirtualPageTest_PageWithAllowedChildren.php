<?php

use SilverStripe\CMS\Model\VirtualPage;
use SilverStripe\Dev\TestOnly;

class VirtualPageTest_PageWithAllowedChildren extends Page implements TestOnly
{
    private static $allowed_children = array(
        VirtualPageTest_ClassA::class,
        VirtualPage::class,
    );
}
