<?php
/**
 * @package cms
 * @subpackage control
 */
class RootURLController extends Controller {
	
	/**
	 * @var bool
	 */
	protected static $is_at_root = false;
	
	/**
	 * @config
	 * @var string
	 */
	private static $default_homepage_link = 'home';
	
	/**
	 * @var string
	 */
	protected static $cached_homepage_link;
	
	/**
	 * Get the full form (e.g. /home/) relative link to the home page for the current HTTP_HOST value. Note that the
	 * link is trimmed of leading and trailing slashes before returning to ensure consistency.
	 *
	 * @return string
	 */
	static public function get_homepage_link() {
		if(!self::$cached_homepage_link) {
			// TODO Move to 'homepagefordomain' module
			if(class_exists('HomepageForDomainExtension')) {
				$host       = str_replace('www.', null, $_SERVER['HTTP_HOST']);
				$SQL_host   = Convert::raw2sql($host);
				$candidates = DataObject::get('SiteTree', "\"HomepageForDomain\" LIKE '%$SQL_host%'");
				if($candidates) foreach($candidates as $candidate) {
					if(preg_match('/(,|^) *' . preg_quote($host) . ' *(,|$)/', $candidate->HomepageForDomain)) {
						self::$cached_homepage_link = trim($candidate->RelativeLink(true), '/');
					}
				}
			}
			
			if(!self::$cached_homepage_link) {
				// TODO Move to 'translatable' module
				if (
					class_exists('Translatable')
					&& SiteTree::has_extension('Translatable')
					&& $link = Translatable::get_homepage_link_by_locale(Translatable::get_current_locale())
				) {
					self::$cached_homepage_link = $link;
				} else {
					self::$cached_homepage_link = Config::inst()->get('RootURLController', 'default_homepage_link');
				}
			}
		}
		
		return self::$cached_homepage_link;
	}
	
	/**
	 * Set the URL Segment used for your homepage when it is created by dev/build.
	 * This allows you to use home page URLs other than the default "home".
	 *
	 * @deprecated 3.2 Use the "RootURLController.default_homepage_link" config setting instead
	 * @param string $urlsegment the URL segment for your home page
	 */
	static public function set_default_homepage_link($urlsegment = "home") {
		Deprecation::notice('3.2', 'Use the "RootURLController.default_homepage_link" config setting instead');
		Config::inst()->update('RootURLController', 'default_homepage_link', $urlsegment);
	}

	/**
	 * Gets the link that denotes the homepage if there is not one explicitly defined for this HTTP_HOST value.
	 *
	 * @deprecated 3.2 Use the "RootURLController.default_homepage_link" config setting instead
	 * @return string
	 */
	static public function get_default_homepage_link() {
		Deprecation::notice('3.2', 'Use the "RootURLController.default_homepage_link" config setting instead');
		return Config::inst()->get('RootURLController', 'default_homepage_link');
	}
	
	/**
	 * Returns TRUE if a request to a certain page should be redirected to the site root (i.e. if the page acts as the
	 * home page).
	 *
	 * @param SiteTree $page
	 * @return bool
	 */
	static public function should_be_on_root(SiteTree $page) {
		if(!self::$is_at_root && self::get_homepage_link() == trim($page->RelativeLink(true), '/')) {
			return !(
				class_exists('Translatable') && $page->hasExtension('Translatable') && $page->Locale && $page->Locale != Translatable::default_locale()
			);
		}
		
		return false;
	}
	
	/**
	 * Resets the cached homepage link value - useful for testing.
	 */
	static public function reset() {
		self::$cached_homepage_link = null;
	}
	
	/**
	 * @param SS_HTTPRequest $request
	 * @param DataModel|null $model
	 * @return SS_HTTPResponse
	 */
	public function handleRequest(SS_HTTPRequest $request, DataModel $model = null) {
		self::$is_at_root = true;
		$this->setDataModel($model);
		
		$this->pushCurrent();
		$this->init();
		
		if(!DB::isActive() || !ClassInfo::hasTable('SiteTree')) {
			$this->response = new SS_HTTPResponse();
			$this->response->redirect(Director::absoluteBaseURL() . 'dev/build?returnURL=' . (isset($_GET['url']) ? urlencode($_GET['url']) : null));
			return $this->response;
		}
			
		$request->setUrl(self::get_homepage_link() . '/');
		$request->match('$URLSegment//$Action', true);
		$controller = new ModelAsController();

		$result     = $controller->handleRequest($request, $model);
		
		$this->popCurrent();
		return $result;
	}
	
}
