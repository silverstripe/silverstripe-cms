<?php

namespace SilverStripe\CMS\Tests\CMSEditLinkExtensionTest;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;

class PageWithChild extends SiteTree implements TestOnly
{
    private static $has_many = [
        'ChildObjects' => BelongsToPage::class,
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldToTab(
            'Root.ChildObjects',
            GridField::create('ChildObjects', 'ChildObjects', $this->ChildObjects(), GridFieldConfig_RelationEditor::create())
        );
        return $fields;
    }
}
