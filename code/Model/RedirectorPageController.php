<?php
namespace SilverStripe\CMS\Model;

use SilverStripe\Control\HTTPRequest;
use PageController;

/**
 * Controller for the {@link RedirectorPage}.
 */
class RedirectorPageController extends PageController
{
    private static $allowed_actions = ['index'];

    /**
     * Check we don't already have a redirect code set
     *
     * @param  HTTPRequest $request
     * @return \SilverStripe\Control\HTTPResponse
     */
    public function index(HTTPRequest $request)
    {
        /** @var RedirectorPage $page */
        $page = $this->data();
        if (!$this->getResponse()->isFinished() && $link = $page->redirectionLink()) {
            $this->redirect($link, 301);
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
