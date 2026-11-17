<?php

namespace SilverStripe\CMS\Tests\Controllers\PageTreeControllerTest;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\TestOnly;

class EmptyTreeTitlePage extends SiteTree implements TestOnly
{
    public function getTreeTitle(): string
    {
        return '';
    }
}
