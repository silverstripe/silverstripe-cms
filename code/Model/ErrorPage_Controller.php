<?php
namespace SilverStripe\CMS\Model;

use SilverStripe\ORM\DataModel;
use SilverStripe\Control\SS_HTTPRequest;
use SilverStripe\Control\SS_HTTPResponse;
use Page_Controller;


/**
 * Controller for ErrorPages.
 */
class ErrorPage_Controller extends Page_Controller
{

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
	public function handleRequest(SS_HTTPRequest $request, DataModel $model = null)
	{
		$response = parent::handleRequest($request, $model);
		$response->setStatusCode($this->ErrorCode);
		return $response;
	}
}
