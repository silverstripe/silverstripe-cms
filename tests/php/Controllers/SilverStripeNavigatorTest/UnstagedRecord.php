<?php

namespace SilverStripe\CMS\Tests\Controllers\SilverStripeNavigatorTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\CMSPreviewable;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;

/**
 * Versioned but not staged
 */
class UnstagedRecord extends DataObject implements TestOnly, CMSPreviewable
{
    private static $table_name = 'SilverStripeNavigatorTest_UnversionedRecord';

    private static $extensions = [
        Versioned::class . '.versioned',
    ];

    public function PreviewLink($action = null)
    {
        return null;
    }

    /**
     * To determine preview mechanism (e.g. embedded / iframe)
     *
     * @return string
     */
    public function getMimeType()
    {
        return 'text/html';
    }

    public function CMSEditLink()
    {
        return null;
    }
}
