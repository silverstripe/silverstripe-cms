<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\Dev\TestOnly;
use PageController;

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
