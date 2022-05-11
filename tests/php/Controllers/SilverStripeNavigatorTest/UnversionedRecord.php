<?php

namespace SilverStripe\CMS\Tests\Controllers\SilverStripeNavigatorTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\CMSPreviewable;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;

class UnversionedRecord extends DataObject implements TestOnly, CMSPreviewable
{
    private static $table_name = 'SilverStripeNavigatorTest_UnversionedRecord';

    private static $show_stage_link = true;

    private static $show_live_link = true;

    private static $show_unversioned_preview_link = true;

    public $previewLinkTestProperty = null;

    public function PreviewLink($action = null)
    {
        return $this->previewLinkTestProperty;
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
