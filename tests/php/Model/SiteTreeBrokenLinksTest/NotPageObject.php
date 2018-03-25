<?php

namespace SilverStripe\CMS\Tests\Model\SiteTreeBrokenLinksTest;

use SilverStripe\CMS\Model\SiteTreeLinkTracking;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

/**
 * @mixin SiteTreeLinkTracking
 * @property string $Content
 * @property string $AnotherContent
 */
class NotPageObject extends DataObject implements TestOnly
{
    private static $table_name = 'SiteTreeLinkTrackingTest_NotPageObject';

    private static $db = [
        'Content' => 'HTMLText',
        'AnotherContent' => 'HTMLText',
    ];
}
