<?php

namespace SilverStripe\CMS\Tests\Controllers\CMSMainTreeControllerTest;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\TestOnly;

class NoChildrenPage extends SiteTree implements TestOnly
{
    private static $allowed_children = 'none';
}
