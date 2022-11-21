<?php

namespace SilverStripe\CMS\Model;

use SilverStripe\Assets\File;
use SilverStripe\Dev\Deprecation;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Admin\Forms\UsedOnTable;
use SilverStripe\Versioned\RecursivePublishable;

/**
 * @deprecated 5.0
 * No longer required - superceded by {@see UsedOnTable}
 *
 * Extension applied to {@see FileFormFactory} to decorate with a "Used on:" information area.
 * Uses tracking provided by {@see SiteTreeFileExtension} to generate this.
 *
 * @property File $owner
 */
class SiteTreeFileFormFactoryExtension extends DataExtension
{
    public function updateFormFields(FieldList $fields, $controller, $formName, $context)
    {
        /** @var TabSet $tabset */
        $tabset = $fields->fieldByName('Editor');
        if (!$tabset) {
            return;
        }
        $class = UsedOnTable::class;
        Deprecation::notice('5.0', "Use the $class to show this table");

        $usedOnField = UsedOnTable::create('UsedOnTableReplacement');
        $usedOnField->setRecord($context['Record']);

        // Add field to new tab
        /** @var Tab $tab */
        $tab = Tab::create('Usage', _t(__CLASS__ . '.USAGE', 'Usage'), $usedOnField);
        $tabset->push($tab);
    }
}
