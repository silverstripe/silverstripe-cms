<?php
namespace SilverStripe\CMS\Model;

use PageController;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;

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
     * @return HTTPResponse
     */
    public function handleRequest(HTTPRequest $request)
    {
        /** @var ErrorPage $page */
        $page = $this->data();
        $response = parent::handleRequest($request);
        $response->setStatusCode($page->ErrorCode);
        return $response;
    }
}
