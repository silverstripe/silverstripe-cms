<?php
namespace SilverStripe\CMS\Model;

use PageController;

/**
 * Controller for the {@link RedirectorPage}.
 */
class RedirectorPageController extends PageController
{

    protected function init()
    {
        parent::init();

        // Check we don't already have a redirect code set
        /** @var RedirectorPage $page */
        $page = $this->data();
        if (!$this->getResponse()->isFinished() && $link = $page->redirectionLink()) {
            $this->redirect($link, 301);
        }
    }

    /**
     * If we ever get this far, it means that the redirection failed.
     */
    public function Content()
    {
        return "<p class=\"message-setupWithoutRedirect\">" .
        _t('SilverStripe\\CMS\\Model\\RedirectorPage.HASBEENSETUP', 'A redirector page has been set up without anywhere to redirect to.') .
        "</p>";
    }
}
