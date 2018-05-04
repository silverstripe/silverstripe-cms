<?php

namespace SilverStripe\CMS\Tests\Controllers;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\TestOnly;

class ContentControllerTestPage extends SiteTree implements TestOnly
{
    private static $table_name = 'ContentControllerTestPage';
}
