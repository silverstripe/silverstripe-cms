<?php

use SilverStripe\Dev\TestOnly;

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
