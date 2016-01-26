<?php

/**
 * Enhances error handling for a controller with ErrorPage generated output
 *
 * @package cms
 * @subpackage controller
 */
class ErrorPageControllerExtension extends Extension {

	/**
	 * Used by {@see RequestHandler::httpError}
	 *
	 * @param int $statusCode
	 * @param SS_HTTPRequest $request
	 * @throws SS_HTTPResponse_Exception
	 */
	public function onBeforeHTTPError($statusCode, $request) {
		$response = ErrorPage::response_for($statusCode);
		if($response) {
			throw new SS_HTTPResponse_Exception($response, $statusCode);
		}
	}
}
