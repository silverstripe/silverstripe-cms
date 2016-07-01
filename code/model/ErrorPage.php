<?php

use SilverStripe\Filesystem\Storage\GeneratedAssetHandler;
use SilverStripe\ORM\DataModel;
use SilverStripe\ORM\Versioning\Versioned;
use SilverStripe\ORM\DB;

/**
 * ErrorPage holds the content for the page of an error response.
 * Renders the page on each publish action into a static HTML file
 * within the assets directory, after the naming convention
 * /assets/error-<statuscode>.html.
 * This enables us to show errors even if PHP experiences a recoverable error.
 * ErrorPages
 *
 * @see Debug::friendlyError()
 *
 * @property int $ErrorCode HTTP Error code
 * @package cms
 */
class ErrorPage extends Page {

	private static $db = array(
		"ErrorCode" => "Int",
	);

	private static $defaults = array(
		"ShowInMenus" => 0,
		"ShowInSearch" => 0
	);

	private static $allowed_children = array();

	private static $description = 'Custom content for different error cases (e.g. "Page not found")';

	/**
	 * Allows control over writing directly to the configured `GeneratedAssetStore`.
	 *
	 * @config
	 * @var bool
	 */
	private static $enable_static_file = true;

	/**
	 * Prefix for storing error files in the {@see GeneratedAssetHandler} store.
	 * Defaults to empty (top level directory)
	 *
	 * @config
	 * @var string
	 */
	private static $store_filepath = null;
	/**
	 * @param $member
	 *
	 * @return boolean
	 */
	public function canAddChildren($member = null) {
		return false;
	}

	/**
	 * Get a {@link SS_HTTPResponse} to response to a HTTP error code if an
	 * {@link ErrorPage} for that code is present. First tries to serve it
	 * through the standard SilverStripe request method. Falls back to a static
	 * file generated when the user hit's save and publish in the CMS
	 *
	 * @param int $statusCode
	 * @return SS_HTTPResponse
	 */
	public static function response_for($statusCode) {
		// first attempt to dynamically generate the error page
		$errorPage = ErrorPage::get()
			->filter(array(
				"ErrorCode" => $statusCode
			))->first();

		if($errorPage) {
			Requirements::clear();
			Requirements::clear_combined_files();

			return ModelAsController::controller_for($errorPage)
				->handleRequest(
					new SS_HTTPRequest('GET', ''),
					DataModel::inst()
				);
		}

		// then fall back on a cached version
		$content = self::get_content_for_errorcode($statusCode);
		if($content) {
			$response = new SS_HTTPResponse();
			$response->setStatusCode($statusCode);
			$response->setBody($content);
			return $response;
		}
	}

	/**
	 * Ensures that there is always a 404 page by checking if there's an
	 * instance of ErrorPage with a 404 and 500 error code. If there is not,
	 * one is created when the DB is built.
	 */
	public function requireDefaultRecords() {
		parent::requireDefaultRecords();

		if ($this->class === 'ErrorPage' && SiteTree::config()->create_default_pages) {

			$defaultPages = $this->getDefaultRecords();

			foreach($defaultPages as $defaultData) {
				$code = $defaultData['ErrorCode'];
				$page = ErrorPage::get()->filter('ErrorCode', $code)->first();
				$pageExists = !empty($page);
				if(!$pageExists) {
					$page = new ErrorPage($defaultData);
					$page->write();
					$page->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
				}

				// Check if static files are enabled
				if(!self::config()->enable_static_file) {
					continue;
				}

				// Ensure this page has cached error content
				$success = true;
				if(!$page->hasStaticPage()) {
					// Update static content
					$success = $page->writeStaticPage();
				} elseif($pageExists) {
					// If page exists and already has content, no alteration_message is displayed
					continue;
				}

				if($success) {
					DB::alteration_message(
						sprintf('%s error page created', $code),
						'created'
					);
				} else {
					DB::alteration_message(
						sprintf('%s error page could not be created. Please check permissions', $code),
						'error'
					);
				}
			}
		}
	}

	/**
	 * Returns an array of arrays, each of which defines properties for a new
	 * ErrorPage record.
	 *
	 * @return array
	 */
	protected function getDefaultRecords() {
		$data = array(
			array(
				'ErrorCode' => 404,
				'Title' => _t('ErrorPage.DEFAULTERRORPAGETITLE', 'Page not found'),
				'Content' => _t(
					'ErrorPage.DEFAULTERRORPAGECONTENT',
					'<p>Sorry, it seems you were trying to access a page that doesn\'t exist.</p>'
					. '<p>Please check the spelling of the URL you were trying to access and try again.</p>'
				)
			),
			array(
				'ErrorCode' => 500,
				'Title' => _t('ErrorPage.DEFAULTSERVERERRORPAGETITLE', 'Server error'),
				'Content' => _t(
					'ErrorPage.DEFAULTSERVERERRORPAGECONTENT',
					'<p>Sorry, there was a problem with handling your request.</p>'
				)
			)
		);

		$this->extend('getDefaultRecords', $data);

		return $data;
	}

	/**
	 * @return FieldList
	 */
	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->addFieldToTab(
			"Root.Main",
			new DropdownField(
				"ErrorCode",
				$this->fieldLabel('ErrorCode'),
				array(
					400 => _t('ErrorPage.400', '400 - Bad Request'),
					401 => _t('ErrorPage.401', '401 - Unauthorized'),
					403 => _t('ErrorPage.403', '403 - Forbidden'),
					404 => _t('ErrorPage.404', '404 - Not Found'),
					405 => _t('ErrorPage.405', '405 - Method Not Allowed'),
					406 => _t('ErrorPage.406', '406 - Not Acceptable'),
					407 => _t('ErrorPage.407', '407 - Proxy Authentication Required'),
					408 => _t('ErrorPage.408', '408 - Request Timeout'),
					409 => _t('ErrorPage.409', '409 - Conflict'),
					410 => _t('ErrorPage.410', '410 - Gone'),
					411 => _t('ErrorPage.411', '411 - Length Required'),
					412 => _t('ErrorPage.412', '412 - Precondition Failed'),
					413 => _t('ErrorPage.413', '413 - Request Entity Too Large'),
					414 => _t('ErrorPage.414', '414 - Request-URI Too Long'),
					415 => _t('ErrorPage.415', '415 - Unsupported Media Type'),
					416 => _t('ErrorPage.416', '416 - Request Range Not Satisfiable'),
					417 => _t('ErrorPage.417', '417 - Expectation Failed'),
					422 => _t('ErrorPage.422', '422 - Unprocessable Entity'),
					429 => _t('ErrorPage.429', '429 - Too Many Requests'),
					500 => _t('ErrorPage.500', '500 - Internal Server Error'),
					501 => _t('ErrorPage.501', '501 - Not Implemented'),
					502 => _t('ErrorPage.502', '502 - Bad Gateway'),
					503 => _t('ErrorPage.503', '503 - Service Unavailable'),
					504 => _t('ErrorPage.504', '504 - Gateway Timeout'),
					505 => _t('ErrorPage.505', '505 - HTTP Version Not Supported'),
				)
			),
			"Content"
		);

		return $fields;
	}

	/**
	 * When an error page is published, create a static HTML page with its
	 * content, so the page can be shown even when SilverStripe is not
	 * functioning correctly before publishing this page normally.
	 *
	 * @return bool True if published
	 */
	public function publishSingle() {
		if (!parent::publishSingle()) {
			return false;
		}
		return $this->writeStaticPage();
	}

	/**
	 * Determine if static content is cached for this page
	 *
	 * @return bool
	 */
	protected function hasStaticPage() {
		if(!self::config()->enable_static_file) {
			return false;
		}

		// Attempt to retrieve content from generated file handler
		$filename = $this->getErrorFilename();
		$storeFilename = File::join_paths(self::config()->store_filepath, $filename);
		$result = self::get_asset_handler()->getContent($storeFilename);
		return !empty($result);
	}

	/**
	 * Write out the published version of the page to the filesystem
	 *
	 * @return true if the page write was successful
	 */
	public function writeStaticPage() {
		if(!self::config()->enable_static_file) {
			return false;
		}

		// Run the page (reset the theme, it might've been disabled by LeftAndMain::init())
		Config::nest();
		Config::inst()->update('SSViewer', 'theme_enabled', true);
		$response = Director::test(Director::makeRelative($this->Link()));
		Config::unnest();
		$errorContent = $response->getBody();

		// Store file content in the default store
		$storeFilename = File::join_paths(
			self::config()->store_filepath,
			$this->getErrorFilename()
		);
		self::get_asset_handler()->setContent($storeFilename, $errorContent);

		// Success
		return true;
	}

	/**
	 * @param boolean $includerelations a boolean value to indicate if the labels returned include relation fields
	 *
	 * @return array
	 */
	public function fieldLabels($includerelations = true) {
		$labels = parent::fieldLabels($includerelations);
		$labels['ErrorCode'] = _t('ErrorPage.CODE', "Error code");

		return $labels;
	}

	/**
	 * Returns statically cached content for a given error code
	 *
	 * @param int $statusCode A HTTP Statuscode, typically 404 or 500
	 * @return string|null
	 */
	public static function get_content_for_errorcode($statusCode) {
		if(!self::config()->enable_static_file) {
			return null;
		}

		// Attempt to retrieve content from generated file handler
		$filename = self::get_error_filename($statusCode);
		$storeFilename = File::join_paths(
			self::config()->store_filepath,
			$filename
		);
		return self::get_asset_handler()->getContent($storeFilename);
	}

	/**
	 * Gets the filename identifier for the given error code.
	 * Used when handling responses under error conditions.
	 *
	 * @param int $statusCode A HTTP Statuscode, typically 404 or 500
	 * @param ErrorPage $instance Optional instance to use for name generation
	 * @return string
	 */
	protected static function get_error_filename($statusCode, $instance = null) {
		if(!$instance) {
			$instance = ErrorPage::singleton();
		}
		// Allow modules to extend this filename (e.g. for multi-domain, translatable)
		$name = "error-{$statusCode}.html";
		$instance->extend('updateErrorFilename', $name, $statusCode);
		return $name;
	}

	/**
	 * Get filename identifier for this record.
	 * Used for generating the filename for the current record.
	 *
	 * @return string
	 */
	protected function getErrorFilename() {
		return self::get_error_filename($this->ErrorCode, $this);
	}

	/**
	 * @return GeneratedAssetHandler
	 */
	protected static function get_asset_handler() {
		return Injector::inst()->get('GeneratedAssetHandler');
	}
}

/**
 * Controller for ErrorPages.
 *
 * @package cms
 */
class ErrorPage_Controller extends Page_Controller {

	/**
	 * Overload the provided {@link Controller::handleRequest()} to append the
	 * correct status code post request since otherwise permission related error
	 * pages such as 401 and 403 pages won't be rendered due to
	  * {@link SS_HTTPResponse::isFinished() ignoring the response body.
	 *
	 * @param SS_HTTPRequest $request
	 * @param DataModel $model
	 * @return SS_HTTPResponse
	 */
	public function handleRequest(SS_HTTPRequest $request, DataModel $model = NULL) {
		$response = parent::handleRequest($request, $model);
		$response->setStatusCode($this->ErrorCode);
		return $response;
	}
}

