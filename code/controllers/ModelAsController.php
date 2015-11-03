<?php
/**
 * ModelAsController deals with mapping the initial request to the first {@link SiteTree}/{@link ContentController}
 * pair, which are then used to handle the request.
 *
 * @package cms
 * @subpackage control
 */
class ModelAsController extends Controller implements NestedController {
	private static $extensions = array('OldPageRedirector');

	/**
	 * Get the appropriate {@link ContentController} for handling a {@link SiteTree} object, link it to the object and
	 * return it.
	 *
	 * @param SiteTree $sitetree
	 * @param string $action
	 * @return ContentController
	 */
	public static function controller_for(SiteTree $sitetree, $action = null) {
		if ($sitetree->class == 'SiteTree') {
			$controller = "ContentController";
		} else {
			$ancestry = ClassInfo::ancestry($sitetree->class);
			while ($class = array_pop($ancestry)) {
				if (class_exists($class . "_Controller")) break;
			}
			$controller = ($class !== null) ? "{$class}_Controller" : "ContentController";
		}

		if($action && class_exists($controller . '_' . ucfirst($action))) {
			$controller = $controller . '_' . ucfirst($action);
		}

		return class_exists($controller) ? Injector::inst()->create($controller, $sitetree) : $sitetree;
	}

	public function init() {
		singleton('SiteTree')->extend('modelascontrollerInit', $this);
		parent::init();
	}

	/**
	 * @uses ModelAsController::getNestedController()
	 * @param SS_HTTPRequest $request
	 * @param DataModel $model
	 * @return SS_HTTPResponse
	 */
	public function handleRequest(SS_HTTPRequest $request, DataModel $model) {
		$this->setRequest($request);
		$this->setDataModel($model);

		$this->pushCurrent();
		$this->getResponse();
		$this->init();

		// If we had a redirection or something, halt processing.
		if($this->getResponse()->isFinished()) {
			$this->popCurrent();
			return $this->getResponse();
		}

		// If the database has not yet been created, redirect to the build page.
		if(!DB::is_active() || !ClassInfo::hasTable('SiteTree')) {
			$this->getResponse()->redirect(Director::absoluteBaseURL() . 'dev/build?returnURL=' . (isset($_GET['url']) ? urlencode($_GET['url']) : null));
			$this->popCurrent();

			return $this->getResponse();
		}

		try {
			$result = $this->getNestedController();

			if($result instanceof RequestHandler) {
				$result = $result->handleRequest($this->getRequest(), $model);
			} else if(!($result instanceof SS_HTTPResponse)) {
				user_error("ModelAsController::getNestedController() returned bad object type '" .
					get_class($result)."'", E_USER_WARNING);
			}
		} catch(SS_HTTPResponse_Exception $responseException) {
			$result = $responseException->getResponse();
		}

		$this->popCurrent();
		return $result;
	}

	/**
	 * @return ContentController
	 * @throws Exception If URLSegment not passed in as a request parameter.
	 */
	public function getNestedController() {
		$request = $this->getRequest();

		if(!$URLSegment = $request->param('URLSegment')) {
			throw new Exception('ModelAsController->getNestedController(): was not passed a URLSegment value.');
		}

		// Find page by link, regardless of current locale settings
		if(class_exists('Translatable')) Translatable::disable_locale_filter();

		// Select child page
		$conditions = array('"SiteTree"."URLSegment"' => rawurlencode($URLSegment));
		if(SiteTree::config()->nested_urls) {
			$conditions[] = array('"SiteTree"."ParentID"' => 0);
		}
		$sitetree = DataObject::get_one('SiteTree', $conditions);

		// Check translation module
		// @todo Refactor out module specific code
		if(class_exists('Translatable')) Translatable::enable_locale_filter();

		if(!$sitetree) {
			$response = ErrorPage::response_for(404);
			$this->httpError(404, $response ? $response : 'The requested page could not be found.');
		}

		// Enforce current locale setting to the loaded SiteTree object
		if(class_exists('Translatable') && $sitetree->Locale) Translatable::set_current_locale($sitetree->Locale);

		if(isset($_REQUEST['debug'])) {
			Debug::message("Using record #$sitetree->ID of type $sitetree->class with link {$sitetree->Link()}");
		}

		return self::controller_for($sitetree, $this->getRequest()->param('Action'));
	}

	/**
	 * @deprecated 4.0 Use OldPageRedirector::find_old_page instead
	 *
	 * @param string $URLSegment A subset of the url. i.e in /home/contact/ home and contact are URLSegment.
	 * @param int $parent The ID of the parent of the page the URLSegment belongs to.
	 * @param bool $ignoreNestedURLs
	 * @return SiteTree
	 */
	static public function find_old_page($URLSegment, $parent = null, $ignoreNestedURLs = false) {
		Deprecation::notice('4.0', 'Use OldPageRedirector::find_old_page instead');
		if ($parent) {
			$parent = SiteTree::get()->byId($parent);
		}
		$url = OldPageRedirector::find_old_page(array($URLSegment), $parent);
		return SiteTree::get_by_link($url);
	}
}
