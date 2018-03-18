<?php

namespace SilverStripe\CMS\Search;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\Search\FulltextSearchable;

/**
 * Extension to provide a search interface when applied to ContentController
 */
class ContentControllerSearchExtension extends Extension
{
    private static $allowed_actions = array(
        'SearchForm',
    );

    /**
     * Site search form
     *
     * @return SearchForm
     */
    public function SearchForm()
    {
        $searchText =  _t('SilverStripe\\CMS\\Search\\SearchForm.SEARCH', 'Search');

        if ($this->owner->getRequest() && $this->owner->getRequest()->getVar('Search')) {
            $searchText = $this->owner->getRequest()->getVar('Search');
        }

        $fields = new FieldList(
            new TextField('Search', false, $searchText)
        );
        $actions = new FieldList(
            new FormAction('results', _t('SilverStripe\\CMS\\Search\\SearchForm.GO', 'Go'))
        );
        /** @skipUpgrade */
        $form = SearchForm::create($this->owner, 'SearchForm', $fields, $actions);
        $form->classesToSearch(FulltextSearchable::get_searchable_classes());
        return $form;
    }

    /**
     * Process and render search results.
     *
     * @param array $data The raw request data submitted by user
     * @param SearchForm $form The form instance that was submitted
     * @param HTTPRequest $request Request generated for this action
     */
    public function results($data, $form, $request)
    {
        $data = array(
            'Results' => $form->getResults(),
            'Query' => DBField::create_field('Text', $form->getSearchQuery()),
            'Title' => _t('SilverStripe\\CMS\\Search\\SearchForm.SearchResults', 'Search Results')
        );
        return $this->owner->customise($data)->renderWith(array('Page_results', 'Page'));
    }
}
