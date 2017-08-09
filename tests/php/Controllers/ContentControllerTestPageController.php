<?php

namespace SilverStripe\CMS\Tests\Controllers;

use SilverStripe\Dev\TestOnly;
use PageController;

class ContentControllerTestPageController extends PageController implements TestOnly
{
    private static $allowed_actions = array(
        'test',
        'testwithouttemplate'
    );

    public function testwithouttemplate()
    {
        return array();
    }
}
