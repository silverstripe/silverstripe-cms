<?php

namespace SilverStripe\CMS\Tests\Controllers\PageTreeControllerTest;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\TestOnly;

class NoChildrenPage extends SiteTree implements TestOnly
{
    private static string $allowed_children = 'none';
}
