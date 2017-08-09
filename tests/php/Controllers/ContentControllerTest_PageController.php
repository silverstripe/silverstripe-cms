<?php

namespace SilverStripe\CMS\Tests\Controllers;

use SilverStripe\Dev\TestOnly;
use PageController;

class ContentControllerTest_PageController extends PageController implements TestOnly
{

    private static $allowed_actions = array(
        'second_index'
    );

    public function index()
    {
        return $this->Title;
    }

    public function second_index()
    {
        return $this->index();
    }
}
