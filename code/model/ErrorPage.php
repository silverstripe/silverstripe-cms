<?php
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
	 * @config
	 */
	private static $static_filepath = ASSETS_PATH;

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
	 *
	 * @return SS_HTTPResponse
	 */
	public static function response_for($statusCode) {
		// first attempt to dynamically generate the error page
		$errorPage = ErrorPage::get()->filter(array(
			"ErrorCode" => $statusCode
		))->first();

		if($errorPage) {
			Requirements::clear();
			Requirements::clear_combined_files();

			return ModelAsController::controller_for($errorPage)->handleRequest(
				new SS_HTTPRequest('GET', ''), DataModel::inst()
			);
		}

		// then fall back on a cached version
		$cachedPath = self::get_filepath_for_errorcode(
			$statusCode,
			class_exists('Translatable') ? Translatable::get_current_locale() : null
		);

		if(file_exists($cachedPath)) {
			$response = new SS_HTTPResponse();

			$response->setStatusCode($statusCode);
			$response->setBody(file_get_contents($cachedPath));

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

		if ($this->class == 'ErrorPage' && SiteTree::config()->create_default_pages) {
			// Ensure that an assets path exists before we do any error page creation
			if(!file_exists(ASSETS_PATH)) {
				mkdir(ASSETS_PATH);
			}

			$defaultPages = $this->getDefaultRecords();

			foreach($defaultPages as $defaultData) {
				$code = $defaultData['ErrorCode'];
				$page = DataObject::get_one(
					'ErrorPage',
					sprintf("\"ErrorPage\".\"ErrorCode\" = '%s'", $code)
				);
				$pageExists = ($page && $page->exists());
				$pagePath = self::get_filepath_for_errorcode($code);
				if(!($pageExists && file_exists($pagePath))) {
					if(!$pageExists) {
						$page = new ErrorPage($defaultData);
						$page->write();
						$page->publish('Stage', 'Live');
					}

					// Ensure a static error page is created from latest error page content
					$response = Director::test(Director::makeRelative($page->Link()));
					$written = null;
					if($fh = fopen($pagePath, 'w')) {
						$written = fwrite($fh, $response->getBody());
						fclose($fh);
					}

					if($written) {
						DB::alteration_message(
							sprintf('%s error page created', $code),
							'created'
						);
					} else {
						DB::alteration_message(
							sprintf(
								'%s error page could not be created at %s. Please check permissions',
								$code,
								$pagePath
							),
							'error'
						);
					}
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
	 * @return bool
	 */
	public function doPublish() {
		if (!parent::doPublish()) return false;
		return $this->writeStaticPage();
	}

	/**
	 * Write out the published version of the page to the filesystem
	 *
	 * @return mixed Either true, or an error
	 */
	public function writeStaticPage() {
		// Run the page (reset the theme, it might've been disabled by LeftAndMain::init())
		$oldEnabled = Config::inst()->get('SSViewer', 'theme_enabled');
		Config::inst()->update('SSViewer', 'theme_enabled', true);
		$response = Director::test(Director::makeRelative($this->Link()));
		Config::inst()->update('SSViewer', 'theme_enabled', $oldEnabled);
		$errorContent = $response->getBody();

		// Check we have an assets base directory, creating if it we don't
		if(!file_exists(ASSETS_PATH)) {
			mkdir(ASSETS_PATH, 02775);
		}

		// if the page is published in a language other than default language,
		// write a specific language version of the HTML page
		$filePath = self::get_filepath_for_errorcode($this->ErrorCode, $this->Locale);
		if (file_put_contents($filePath, $errorContent) === false) {
			$fileErrorText = _t(
				'ErrorPage.ERRORFILEPROBLEM',
				'Error opening file "{filename}" for writing. Please check file permissions.',
				array('filename' => $filePath)
			);
			user_error($fileErrorText, E_USER_WARNING);
			return false;
		}
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
	 * Returns an absolute filesystem path to a static error file
	 * which is generated through {@link publish()}.
	 *
	 * @param int $statusCode A HTTP Statuscode, mostly 404 or 500
	 * @param string $locale A locale, e.g. 'de_DE' (Optional)
	 *
	 * @return string
	 */
	public static function get_filepath_for_errorcode($statusCode, $locale = null) {
		if (singleton('ErrorPage')->hasMethod('alternateFilepathForErrorcode')) {
			return singleton('ErrorPage')-> alternateFilepathForErrorcode($statusCode, $locale);
		}

		if(class_exists('Translatable') && singleton('SiteTree')->hasExtension('Translatable') && $locale && $locale != Translatable::default_locale()) {
			return self::config()->static_filepath . "/error-{$statusCode}-{$locale}.html";
		} else {
			return self::config()->static_filepath . "/error-{$statusCode}.html";
		}
	}

	/**
	 * Set the path where static error files are saved through {@link publish()}.
	 * Defaults to /assets.
	 *
	 * @deprecated 4.0 Use "ErrorPage.static_file_path" instead
	 * @param string $path
	 */
	static public function set_static_filepath($path) {
		Deprecation::notice('4.0', 'Use "ErrorPage.static_file_path" instead');
		self::config()->static_filepath = $path;
	}

	/**
	 * @deprecated 4.0 Use "ErrorPage.static_file_path" instead
	 * @return string
	 */
	static public function get_static_filepath() {
		Deprecation::notice('4.0', 'Use "ErrorPage.static_file_path" instead');
		return self::config()->static_filepath;
	}
}

/**
 * Controller for ErrorPages.
 *
 * @package cms
 */
class ErrorPage_Controller extends Page_Controller {
}

