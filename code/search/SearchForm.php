<?php
/**
 * Standard basic search form which conducts a fulltext search on all {@link SiteTree}
 * objects.
 *
 * If multilingual content is enabled through the {@link Translatable} extension,
 * only pages the currently set language on the holder for this searchform are found.
 * The language is set through a hidden field in the form, which is prepoluated
 * with {@link Translatable::get_current_locale()} when then form is constructed.
 *
 * @see Use ModelController and SearchContext for a more generic search implementation based around DataObject
 * @package cms
 * @subpackage search
 */
class SearchForm extends Form {
	
	/**
	 * @var int $pageLength How many results are shown per page.
	 * Relies on pagination being implemented in the search results template.
	 */
	protected $pageLength = 10;
	
	/**
	 * Classes to search
	 */	
 	protected $classesToSearch = array(
		"SiteTree", "File"
	);
	
	private static $casting = array(
		'SearchQuery' => 'Text'
	);
	
	/**
	 *
	 * @param Controller $controller
	 * @param string $name The name of the form (used in URL addressing)
	 * @param FieldList $fields Optional, defaults to a single field named "Search". Search logic needs to be customized
	 *  if fields are added to the form.
	 * @param FieldList $actions Optional, defaults to a single field named "Go".
	 */
	public function __construct($controller, $name, $fields = null, $actions = null) {
		if(!$fields) {
			$fields = new FieldList(
				new TextField('Search', _t('SearchForm.SEARCH', 'Search')
			));
		}
		
		if(class_exists('Translatable') && singleton('SiteTree')->hasExtension('Translatable')) {
			$fields->push(new HiddenField('searchlocale', 'searchlocale', Translatable::get_current_locale()));
		}
		
		if(!$actions) {
			$actions = new FieldList(
				new FormAction("getResults", _t('SearchForm.GO', 'Go'))
			);
		}
		
		parent::__construct($controller, $name, $fields, $actions);
		
		$this->setFormMethod('get');
		
		$this->disableSecurityToken();
	}
	
	
	/**
	 * Return a rendered version of this form.
	 *
	 * This is returned when you access a form as $FormObject rather
	 * than <% with FormObject %>
	 */
	public function forTemplate() {
		$return = $this->renderWith(array_merge(
			(array)$this->getTemplate(),
			array('SearchForm', 'Form')
		));

		// Now that we're rendered, clear message
		$this->clearMessage();

		return $return;
	}

	/**
	 * Set the classes to search.
	 * Currently you can only choose from "SiteTree" and "File", but a future version might improve this.
 	 */
	public function classesToSearch($classes) {
		$illegalClasses = array_diff($classes, array('SiteTree', 'File'));
		if($illegalClasses) {
			user_error("SearchForm::classesToSearch() passed illegal classes '" . implode("', '", $illegalClasses) . "'.  At this stage, only File and SiteTree are allowed", E_USER_WARNING);
		}
		$legalClasses = array_intersect($classes, array('SiteTree', 'File'));		
		$this->classesToSearch = $legalClasses;
	}
	
	/**
	 * Get the classes to search
	 *
	 * @return array
	 */
	public function getClassesToSearch() {
		return $this->classesToSearch;
	}

	/**
	 * Return dataObjectSet of the results using $_REQUEST to get info from form.
	 * Wraps around {@link searchEngine()}.
	 *
	 * @param int $pageLength DEPRECATED 2.3 Use SearchForm->pageLength
	 * @param array $data Request data as an associative array. Should contain at least a key 'Search' with all searched keywords.
	 * @return SS_List
	 */
	public function getResults($pageLength = null, $data = null){
	 	// legacy usage: $data was defaulting to $_REQUEST, parameter not passed in doc.silverstripe.org tutorials
		if(!isset($data) || !is_array($data)) $data = $_REQUEST;
		
		// set language (if present)
		if(class_exists('Translatable')) {
			if(singleton('SiteTree')->hasExtension('Translatable') && isset($data['searchlocale'])) {
				if($data['searchlocale'] == "ALL") {
					Translatable::disable_locale_filter();
				} else {
					$origLocale = Translatable::get_current_locale();

					Translatable::set_current_locale($data['searchlocale']);
				}
			}
		}

		$keywords = $data['Search'];

	 	$andProcessor = create_function('$matches','
	 		return " +" . $matches[2] . " +" . $matches[4] . " ";
	 	');
	 	$notProcessor = create_function('$matches', '
	 		return " -" . $matches[3];
	 	');

	 	$keywords = preg_replace_callback('/()("[^()"]+")( and )("[^"()]+")()/i', $andProcessor, $keywords);
	 	$keywords = preg_replace_callback('/(^| )([^() ]+)( and )([^ ()]+)( |$)/i', $andProcessor, $keywords);
		$keywords = preg_replace_callback('/(^| )(not )("[^"()]+")/i', $notProcessor, $keywords);
		$keywords = preg_replace_callback('/(^| )(not )([^() ]+)( |$)/i', $notProcessor, $keywords);
		
		$keywords = $this->addStarsToKeywords($keywords);

		if(!$pageLength) $pageLength = $this->pageLength;
		$start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
		
		if(strpos($keywords, '"') !== false || strpos($keywords, '+') !== false || strpos($keywords, '-') !== false || strpos($keywords, '*') !== false) {
			$results = DB::get_conn()->searchEngine($this->classesToSearch, $keywords, $start, $pageLength, "\"Relevance\" DESC", "", true);
		} else {
			$results = DB::get_conn()->searchEngine($this->classesToSearch, $keywords, $start, $pageLength);
		}
		
		// filter by permission
		if($results) foreach($results as $result) {
			if(!$result->canView()) $results->remove($result);
		}
		
		// reset locale
		if(class_exists('Translatable')) {
			if(singleton('SiteTree')->hasExtension('Translatable') && isset($data['searchlocale'])) {
				if($data['searchlocale'] == "ALL") {
					Translatable::enable_locale_filter();
				} else {
					Translatable::set_current_locale($origLocale);
				}
			}
		}

		return $results;
	}

	protected function addStarsToKeywords($keywords) {
		if(!trim($keywords)) return "";
		// Add * to each keyword
		$splitWords = preg_split("/ +/" , trim($keywords));
		while(list($i,$word) = each($splitWords)) {
			if($word[0] == '"') {
				while(list($i,$subword) = each($splitWords)) {
					$word .= ' ' . $subword;
					if(substr($subword,-1) == '"') break;
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
	 * @param array $data
	 * @return string
	 */
	public function getSearchQuery($data = null) {
		// legacy usage: $data was defaulting to $_REQUEST, parameter not passed in doc.silverstripe.org tutorials
		if(!isset($data)) $data = $_REQUEST;
		
		// The form could be rendered without the search being done, so check for that.
		if (isset($data['Search'])) return $data['Search'];
	}
	
	/**
	 * Set the maximum number of records shown on each page.
	 *
	 * @param int $length
	 */
	public function setPageLength($length) {
		$this->pageLength = $length;
	}
	
	/**
	 * @return int
	 */
	public function getPageLength() {
		return $this->pageLength;
	}

}


