<?php

namespace SilverStripe\CMS\Model;

use SilverStripe\Assets\File;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Versioned\Versioned;

/**
 * Extension applied to {@see FileFormFactoryExtension} object to track links to {@see SiteTree} records.
 *
 * {@see SiteTreeLinkTracking} for the extension applied to {@see SiteTree}
 *
 * Note that since both SiteTree and File are versioned, LinkTracking and ImageTracking will
 * only be enabled for the Stage record.
 *
 * @property File $owner
 */
class SiteTreeFileFormFactoryExtension extends DataExtension
{
    public function updateForm($form, $controller, $name, $context)
    {
        $record = $context['Record'];
        $fields = $form->Fields();

        $fields->insertAfter(
            'LastEdited',
            ReadonlyField::create(
                'BackLinkCount',
                _t(__CLASS__.'.BACKLINKCOUNT', 'Used on:'),
                $record->BackLinkTrackingCount() . ' ' . _t(__CLASS__.'.PAGES', 'page(s)')
            )
                ->addExtraClass('cms-description-toggle')
                ->setDescription($record->BackLinkHTMLList())
        );
    }
}
