<?php

namespace SilverStripe\CMS\Forms;

use SilverStripe\Admin\Forms\LinkFormFactory;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\Forms\RequiredFields;

/**
 * Provides a form factory for inserting internal page links in a HTML editor
 */
class InternalLinkFormFactory extends LinkFormFactory
{
    /**
     * @param RequestHandler $controller
     * @param string $name
     * @param array $context
     * @return FieldList
     */
    protected function getFormFields($controller, $name, $context)
    {
        $fields = FieldList::create([
            TreeDropdownField::create(
                'PageID',
                _t(__CLASS__.'.SELECT_PAGE', 'Select a page'),
                SiteTree::class,
                'ID',
                'TreeTitle'
            )
                ->setTitleField('MenuTitle')
                ->setHasEmptyDefault(true),
            TextField::create(
                'Description',
                _t(__CLASS__.'.LINKDESCR', 'Link description')
            ),
            CheckboxField::create(
                'TargetBlank',
                _t(__CLASS__.'.LINKOPENNEWWIN', 'Open in new window/tab')
            ),
        ]);

        if ($context['RequireLinkText']) {
            $fields->insertAfter('PageID', TextField::create('Text', _t(__CLASS__.'.LINKTEXT', 'Link text')));
        }

        $this->extend('updateFormFields', $fields, $controller, $name, $context);

        return $fields;
    }

    protected function getValidator($controller, $name, $context)
    {
        if ($context['RequireLinkText']) {
            return RequiredFields::create('Text');
        }

        return null;
    }
}
