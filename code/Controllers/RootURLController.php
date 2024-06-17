<?php

namespace SilverStripe\CMS\Controllers;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Resettable;
use SilverStripe\ORM\DB;

class RootURLController extends Controller implements Resettable
{

    /**
     * @var bool
     */
    protected static $is_at_root = false;

    /**
     * @config
     * @var string
     */
    private static $default_homepage_link = 'home';

    /**
     * @var string
     */
    protected static $cached_homepage_link;

    /**
     * Get the full form (e.g. /home/) relative link to the home page for the current HTTP_HOST value. Note that the
     * link is trimmed of leading and trailing slashes before returning to ensure consistency.
     *
     * @return string
     */
    public static function get_homepage_link()
    {
        if (!RootURLController::$cached_homepage_link) {
            $link = Config::inst()->get(__CLASS__, 'default_homepage_link');
            singleton(__CLASS__)->extend('updateHomepageLink', $link);
            RootURLController::$cached_homepage_link = $link;
        }
        return RootURLController::$cached_homepage_link;
    }

    /**
     * Returns TRUE if a request to a certain page should be redirected to the site root (i.e. if the page acts as the
     * home page).
     *
     * @param SiteTree $page
     * @return bool
     */
    public static function should_be_on_root(SiteTree $page)
    {
        return (!RootURLController::$is_at_root && RootURLController::get_homepage_link() == trim($page->RelativeLink(true) ?? '', '/'));
    }

    /**
     * Resets the cached homepage link value - useful for testing.
     */
    public static function reset()
    {
        RootURLController::$cached_homepage_link = null;
    }

    protected function beforeHandleRequest(HTTPRequest $request)
    {
        parent::beforeHandleRequest($request);

        RootURLController::$is_at_root = true;

        if (!DB::is_active() || !ClassInfo::hasTable('SiteTree')) {
            $this->getResponse()->redirect(Controller::join_links(
                Director::absoluteBaseURL(),
                'dev/build',
                '?' . http_build_query([
                    'returnURL' => isset($_GET['url']) ? $_GET['url'] : null,
                ])
            ));
        }
    }

    public function handleRequest(HTTPRequest $request): HTTPResponse
    {
        RootURLController::$is_at_root = true;
        $this->beforeHandleRequest($request);

        if (!$this->getResponse()->isFinished()) {
            if (!DB::is_active() || !ClassInfo::hasTable('SiteTree')) {
                $this->getResponse()->redirect(Director::absoluteBaseURL() . 'dev/build?returnURL=' . (isset($_GET['url']) ? urlencode($_GET['url']) : null));
                return $this->getResponse();
            }

            $request->setUrl(RootURLController::get_homepage_link() . '/');
            $request->match('$URLSegment//$Action', true);
            $controller = new ModelAsController();

            $response = $controller->handleRequest($request);

            $this->prepareResponse($response);
        }

        $this->afterHandleRequest();

        return $this->getResponse();
    }
}
