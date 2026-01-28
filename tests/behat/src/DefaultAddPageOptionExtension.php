<?php

namespace SilverStripe\CMS\Tests\Behaviour;

use SilverStripe\CMS\Model\VirtualPage;
use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;

/**
 * Sets the default page type for adding new pages to VirtualPage
 */
class DefaultAddPageOptionExtension extends Extension implements TestOnly
{
    protected function updatePageOptions(FieldList $fields)
    {
        $fields->dataFieldByName('PageType')->setValue(VirtualPage::class);
    }
}
