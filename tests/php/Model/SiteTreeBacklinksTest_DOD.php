<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Core\Extension;

class SiteTreeBacklinksTest_DOD extends Extension implements TestOnly
{
    private static $db = [
        'ExtraContent' => 'HTMLText',
    ];

    protected function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab("Root.Content", new HTMLEditorField("ExtraContent"));
    }
}
