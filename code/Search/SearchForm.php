<?php

namespace SilverStripe\CMS\Search;

use BadMethodCallException;
use SilverStripe\Assets\File;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\SS_List;

/**
 * Standard basic search form which conducts a fulltext search on all {@link SiteTree}
 * objects.
 *
 * @see Use ModelController and SearchContext for a more generic search implementation based around DataObject
 */
class SearchForm extends Form
{
    /**
     * How many results are shown per page.
     * Relies on pagination being implemented in the search results template.
     *
     * @var int
     */
    protected $pageLength = 10;

    /**
     * Classes to search
     *
     * @var array
     */
    protected $classesToSearch = [
        SiteTree::class,
        File::class
    ];

    private static $casting = [
        'SearchQuery' => 'Text'
    ];

    /**
     * @param RequestHandler $controller
     * @param string $name The name of the form (used in URL addressing)
     * @param FieldList $fields Optional, defaults to a single field named "Search". Search logic needs to be customized
     *  if fields are added to the form.
     * @param FieldList $actions Optional, defaults to a single field named "Go".
     */
    public function __construct(
        RequestHandler $controller = null,
        $name = 'SearchForm',
        FieldList $fields = null,
        FieldList $actions = null
    ) {
        if (!$fields) {
            $fields = new FieldList(
                new TextField('Search', _t(__CLASS__.'.SEARCH', 'Search'))
            );
        }

        if (!$actions) {
            $actions = new FieldList(
                new FormAction("results", _t(__CLASS__.'.GO', 'Go'))
            );
        }

        parent::__construct($controller, $name, $fields, $actions);

        $this->setFormMethod('get');

        $this->disableSecurityToken();
    }

    /**
     * Set the classes to search.
     * Currently you can only choose from "SiteTree" and "File", but a future version might improve this.
     *
     * @param array $classes
     */
    public function classesToSearch($classes)
    {
        $supportedClasses = [SiteTree::class, File::class];
        $illegalClasses = array_diff($classes ?? [], $supportedClasses);
        if ($illegalClasses) {
            throw new BadMethodCallException(
                "SearchForm::classesToSearch() passed illegal classes '" . implode("', '", $illegalClasses)
                . "'.  At this stage, only File and SiteTree are allowed"
            );
        }
        $legalClasses = array_intersect($classes ?? [], $supportedClasses);
        $this->classesToSearch = $legalClasses;
    }

    /**
     * Get the classes to search
     *
     * @return array
     */
    public function getClassesToSearch()
    {
        return $this->classesToSearch;
    }

    /**
     * Return dataObjectSet of the results using current request to get info from form.
     * Wraps around {@link searchEngine()}.
     *
     * @return SS_List
     */
    public function getResults()
    {
        // Get request data from request handler
        $request = $this->getRequestHandler()->getRequest();

        $keywords = $request->requestVar('Search');

        $andProcessor = function ($matches) {
            return ' +' . $matches[2] . ' +' . $matches[4] . ' ';
        };
        $notProcessor = function ($matches) {
            return ' -' . $matches[3];
        };

        $keywords = preg_replace_callback('/()("[^()"]+")( and )("[^"()]+")()/i', $andProcessor, $keywords ?? '');
        $keywords = preg_replace_callback('/(^| )([^() ]+)( and )([^ ()]+)( |$)/i', $andProcessor, $keywords ?? '');
        $keywords = preg_replace_callback('/(^| )(not )("[^"()]+")/i', $notProcessor, $keywords ?? '');
        $keywords = preg_replace_callback('/(^| )(not )([^() ]+)( |$)/i', $notProcessor, $keywords ?? '');

        $keywords = $this->addStarsToKeywords($keywords);

        $pageLength = $this->getPageLength();
        $start = max(0, (int)$request->requestVar('start'));

        $booleanSearch =
            strpos($keywords ?? '', '"') !== false ||
            strpos($keywords ?? '', '+') !== false ||
            strpos($keywords ?? '', '-') !== false ||
            strpos($keywords ?? '', '*') !== false;
        $results = DB::get_conn()->searchEngine($this->classesToSearch, $keywords, $start, $pageLength, "\"Relevance\" DESC", "", $booleanSearch);

        // filter by permission
        if ($results) {
            foreach ($results as $result) {
                if (!$result->canView()) {
                    $results->remove($result);
                }
            }
        }

        return $results;
    }

    protected function addStarsToKeywords($keywords)
    {
        if (!trim($keywords ?? '')) {
            return "";
        }
        // Add * to each keyword
        $splitWords = preg_split("/ +/", trim($keywords ?? ''));
        $newWords = [];
        for ($i = 0; $i < count($splitWords ?? []); $i++) {
            $word = $splitWords[$i];
            if ($word[0] == '"') {
                while (++$i < count($splitWords ?? [])) {
                    $subword = $splitWords[$i];
                    $word .= ' ' . $subword;
                    if (substr($subword ?? '', -1) == '"') {
                        break;
                    }
                }
            } else {
                $word .= '*';
            }
            $newWords[] = $word;
        }
        return implode(" ", $newWords);
    }

    /**
     * Get the search query for display in a "You searched for ..." sentence.
     *
     * @return string
     */
    public function getSearchQuery()
    {
        return $this->getRequestHandler()->getRequest()->requestVar('Search');
    }

    /**
     * Set the maximum number of records shown on each page.
     *
     * @param int $length
     */
    public function setPageLength($length)
    {
        $this->pageLength = $length;
    }

    /**
     * @return int
     */
    public function getPageLength()
    {
        return $this->pageLength;
    }
}
