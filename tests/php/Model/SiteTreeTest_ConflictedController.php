<?php

use SilverStripe\Dev\TestOnly;

class SiteTreeTest_ConflictedController extends PageController implements TestOnly
{

    private static $allowed_actions = array(
        'conflicted-action'
    );

    public function hasActionTemplate($template)
    {
        if ($template == 'conflicted-template') {
            return true;
        } else {
            return parent::hasActionTemplate($template);
        }
    }
}
