<?php
namespace SilverStripe\CMS\Model;

use SilverStripe\ORM\DataModel;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use PageController;

/**
 * Controller for ErrorPages.
 */
class ErrorPageController extends PageController
{

    /**
     * Overload the provided {@link Controller::handleRequest()} to append the
     * correct status code post request since otherwise permission related error
     * pages such as 401 and 403 pages won't be rendered due to
     * {@link HTTPResponse::isFinished() ignoring the response body.
     *
     * @param HTTPRequest $request
     * @param DataModel $model
     * @return HTTPResponse
     */
    public function handleRequest(HTTPRequest $request, DataModel $model = null)
    {
        $response = parent::handleRequest($request, $model);
        $response->setStatusCode($this->ErrorCode);
        return $response;
    }
}
