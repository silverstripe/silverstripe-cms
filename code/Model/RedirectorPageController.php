<?php
namespace SilverStripe\CMS\Model;

use SilverStripe\Control\HTTPRequest;
use PageController;
use SilverStripe\Control\HTTPResponse_Exception;

/**
 * Controller for the {@link RedirectorPage}.
 *
 * @extends PageController<RedirectorPage>
 */
class RedirectorPageController extends PageController
{
    private static $allowed_actions = ['index'];

    /**
     * Should respond with HTTP 404 if the page or file being redirected to is missing
     */
    private static bool $missing_redirect_is_404 = true;

    /**
     * Check we don't already have a redirect code set
     *
     * @param  HTTPRequest $request
     * @return \SilverStripe\Control\HTTPResponse
     * @throws HTTPResponse_Exception
     */
    public function index(HTTPRequest $request)
    {
        $page = $this->data();

        // Redirect if we can
        if (!$this->getResponse()->isFinished() && $link = $page->redirectionLink()) {
            return $this->redirect($link, 301);
        }

        // Respond with 404 if redirecting to a missing file or page
        if (($this->RedirectionType === 'Internal' && !$page->LinkTo()?->exists())
            || ($this->RedirectionType === 'File' && !$page->LinkToFile()?->exists())
        ) {
            if (static::config()->get('missing_redirect_is_404')) {
                $this->httpError(404);
            }
        }

        // Fall back to a message about the bad setup
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
