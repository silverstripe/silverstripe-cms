<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\Dev\TestOnly;
use SilverStripe\View\Parsers\HTMLCleaner;

class SiteTreeTest_NullHtmlCleaner extends HTMLCleaner implements TestOnly
{
    public function cleanHTML($html)
    {
        return $html;
    }
}
