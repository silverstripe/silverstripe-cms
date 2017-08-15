<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\ORM\DataExtension;

class SiteTreeBacklinksTest_DOD extends DataExtension implements TestOnly
{
    private static $db = array(
        'ExtraContent' => 'HTMLText',
    );

    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab("Root.Content", new HTMLEditorField("ExtraContent"));
    }
}
