<?php

namespace SilverStripe\CMS\Tests\Controllers;

use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\Dev\TestOnly;

class ContentControllerTest_PageController extends ContentController implements TestOnly
{
    private static $allowed_actions = [
        'second_index',
    ];

    public function index()
    {
        return $this->Title;
    }

    public function second_index()
    {
        return $this->index();
    }
}
