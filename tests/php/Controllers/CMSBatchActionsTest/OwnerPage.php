<?php

namespace SilverStripe\CMS\Tests\Controllers\CMSBatchActionsTest;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\TestOnly;

/**
 * @property int $OwnedID
 * @method RestrictedOwnedRecord Owned()
 */
class OwnerPage extends SiteTree implements TestOnly
{
    private static $table_name = 'CMSBatchActionsTest_OwnerPage';

    private static $has_one = [
        'Owned' => RestrictedOwnedRecord::class,
    ];

    private static $owns = [
        'Owned',
    ];
}
