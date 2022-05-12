<?php

namespace SilverStripe\CMS\Tests\Controllers\SilverStripeNavigatorTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\CMSPreviewable;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;

class VersionedRecord extends DataObject implements TestOnly, CMSPreviewable
{
    private static $table_name = 'SilverStripeNavigatorTest_VersionedRecord';

    private static $show_stage_link = true;

    private static $show_live_link = true;

    private static $show_unversioned_preview_link = true;

    private static $db = [
        'PreviewLinkTestProperty' => 'Text',
    ];

    private static $extensions = [
        Versioned::class,
    ];

    public function PreviewLink($action = null)
    {
        return $this->PreviewLinkTestProperty;
    }

    public function getMimeType()
    {
        return 'text/html';
    }

    public function CMSEditLink()
    {
        return null;
    }
}
