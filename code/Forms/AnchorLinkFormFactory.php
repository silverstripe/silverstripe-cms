<?php

namespace SilverStripe\CMS\Forms;

class AnchorLinkFormFactory extends InternalLinkFormFactory
{
    protected function getFormFields($controller, $name, $context)
    {
        $fields = parent::getFormFields($controller, $name, $context);

        // Ensure current page is selected
        $pageIDField = $fields->dataFieldByName('PageID');
        $pageIDField->setValue((int)$context['PageID']);

        // Get anchor selector field
        $fields->insertAfter(
            'PageID',
            AnchorSelectorField::create('Anchor', _t(__CLASS__.'.ANCHORVALUE', 'Anchor'))
        );
        return $fields;
    }

    public function getRequiredContext()
    {
        return array_merge(parent::getRequiredContext(), [ 'PageID' ]);
    }
}
