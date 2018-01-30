<?php

namespace SilverStripe\CMS\Model;

use SilverStripe\Assets\File;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\FieldType\DBHTMLText;

/**
 * Extension applied to {@see FileFormFactory} to decorate with a "Used on:" information area.
 * Uses tracking provided by {@see SiteTreeFileExtension} to generate this.
 *
 * @property File $owner
 */
class SiteTreeFileFormFactoryExtension extends DataExtension
{
    public function updateFormFields(FieldList $fields, $controller, $formName, $context)
    {
        // Create field
        /** @var File|SiteTreeFileExtension $record */
        $record = $context['Record'];
        $usedOnField = ReadonlyField::create(
            'BackLinkCount',
            _t(__CLASS__.'.BACKLINKCOUNT', 'Used on:'),
            $record->BackLinkTrackingCount() . ' ' . _t(__CLASS__.'.PAGES', 'page(s)')
        )
            ->addExtraClass('cms-description-toggle');

        // Add table
        /** @var DBHTMLText $backlinkHTML */
        $backlinkHTML = $record->BackLinkHTMLList();
        if (trim($backlinkHTML->forTemplate())) {
            $usedOnField->setDescription($backlinkHTML);
        }

        /** @var TabSet $tabset */
        $tabset = $fields->fieldByName('Editor');
        if ($tabset) {
            // Add field to new tab
            /** @var Tab $tab */
            $tab = Tab::create('Usage', _t(__CLASS__.'.USAGE', 'Usage'), $usedOnField);
            $tabset->push($tab);
        }
    }
}
