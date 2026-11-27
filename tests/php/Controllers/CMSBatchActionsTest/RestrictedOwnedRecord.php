<?php

namespace SilverStripe\CMS\Tests\Controllers\CMSBatchActionsTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use SilverStripe\Versioned\Versioned;

/**
 * @property string $Title
 */
class RestrictedOwnedRecord extends DataObject implements TestOnly
{
    private static $table_name = 'CMSBatchActionsTest_RestrictedOwnedRecord';

    private static $extensions = [
        Versioned::class,
    ];

    private static $db = [
        'Title' => 'Varchar',
    ];

    public function canPublish($member = null)
    {
        return Permission::checkMember($member, 'OWNED_RECORD_PUBLISH');
    }
}
