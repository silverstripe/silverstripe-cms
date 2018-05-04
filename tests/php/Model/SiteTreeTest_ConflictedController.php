<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\Dev\TestOnly;
use SilverStripe\CMS\Controllers\ContentController;

class SiteTreeTest_ConflictedController extends ContentController implements TestOnly
{
    private static $allowed_actions = [
        'conflicted-action',
    ];

    public function hasActionTemplate($template)
    {
        if ($template == 'conflicted-template') {
            return true;
        } else {
            return parent::hasActionTemplate($template);
        }
    }
}
