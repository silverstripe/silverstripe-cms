<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Core\Extension;

class SiteTreeLinkTracking_Extension extends Extension implements TestOnly
{
    protected function updateAnchorsOnPage(&$anchors)
    {
        array_push(
            $anchors,
            'extension-anchor',
            'extension-anchor-1'
        );
    }
}
