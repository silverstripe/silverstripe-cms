<?php
namespace SilverStripe\CMS\Model;

use SilverStripe\Control\HTTPRequest;
use PageController;
use SilverStripe\Control\HTTPResponse_Exception;

/**
 * Controller for the {@link RedirectorPage}.
 */
class RedirectorPageController extends PageController
{
    private static $allowed_actions = ['index'];

    /**
     * @var bool Should respond with HTTP 404
     */
    private static $should_respond_404 = false;

    /**
     * Check we don't already have a redirect code set
     *
     * @param  HTTPRequest $request
     * @return \SilverStripe\Control\HTTPResponse
     * @throws HTTPResponse_Exception
     */
    public function index(HTTPRequest $request)
    {
        /** @var RedirectorPage $page */
        $page = $this->data();
        if (!$this->getResponse()->isFinished() && $link = $page->redirectionLink()) {
            return $this->redirect($link, 301);
        } elseif ($page->LinkToID && $this->config()->get('should_respond_404') === true) {
            return $this->httpError(404);
        }

        return parent::handleAction($request, 'handleIndex');
    }

    /**
     * If we ever get this far, it means that the redirection failed.
     */
    public function getContent()
    {
        return "<p class=\"message-setupWithoutRedirect\">" .
        _t(__CLASS__ . '.HASBEENSETUP', 'A redirector page has been set up without anywhere to redirect to.') .
        "</p>";
    }
}
