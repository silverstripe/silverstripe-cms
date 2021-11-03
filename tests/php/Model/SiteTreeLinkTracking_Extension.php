<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataExtension;

class SiteTreeLinkTracking_Extension extends DataExtension implements TestOnly
{
    public function updateAnchorsOnPage(&$anchors)
    {
        array_push(
            $anchors,
            'extension-anchor',
            'extension-anchor-1'
        );
    }
}
