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
use Translatable;

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
        if (!self::$cached_homepage_link) {
            // @todo Move to 'homepagefordomain' module
            if (class_exists('HomepageForDomainExtension')) {
                $host       = str_replace('www.', null, $_SERVER['HTTP_HOST']);
                $candidates = SiteTree::get()->where(array(
                    '"SiteTree"."HomepageForDomain" LIKE ?' => "%$host%"
                ));
                if ($candidates) {
                    foreach ($candidates as $candidate) {
                        if (preg_match('/(,|^) *' . preg_quote($host) . ' *(,|$)/', $candidate->HomepageForDomain)) {
                            self::$cached_homepage_link = trim($candidate->RelativeLink(true), '/');
                        }
                    }
                }
            }

            if (!self::$cached_homepage_link) {
                // TODO Move to 'translatable' module
                if (class_exists('Translatable')
                    && SiteTree::has_extension('Translatable')
                    && $link = Translatable::get_homepage_link_by_locale(Translatable::get_current_locale())
                ) {
                    self::$cached_homepage_link = $link;
                } else {
                    self::$cached_homepage_link = Config::inst()->get('SilverStripe\\CMS\\Controllers\\RootURLController', 'default_homepage_link');
                }
            }
        }

        return self::$cached_homepage_link;
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
        if (!self::$is_at_root && self::get_homepage_link() == trim($page->RelativeLink(true), '/')) {
            return !(
                class_exists('Translatable')
                    && $page->hasExtension('Translatable')
                    && $page->Locale
                    && $page->Locale != Translatable::default_locale()
            );
        }

        return false;
    }

    /**
     * Resets the cached homepage link value - useful for testing.
     */
    public static function reset()
    {
        self::$cached_homepage_link = null;
    }

    protected function beforeHandleRequest(HTTPRequest $request)
    {
        parent::beforeHandleRequest($request);

        self::$is_at_root = true;

        /** @skipUpgrade */
        if (!DB::is_active() || !ClassInfo::hasTable('SiteTree')) {
            $this->getResponse()->redirect(Controller::join_links(
                Director::absoluteBaseURL(),
                'dev/build',
                '?' . http_build_query(array(
                    'returnURL' => isset($_GET['url']) ? $_GET['url'] : null,
                ))
            ));
        }
    }

    /**
     * @param HTTPRequest $request
     * @return HTTPResponse
     */
    public function handleRequest(HTTPRequest $request)
    {
        self::$is_at_root = true;
        $this->beforeHandleRequest($request);

        if (!$this->getResponse()->isFinished()) {
            /** @skipUpgrade */
            if (!DB::is_active() || !ClassInfo::hasTable('SiteTree')) {
                $this->getResponse()->redirect(Director::absoluteBaseURL() . 'dev/build?returnURL=' . (isset($_GET['url']) ? urlencode($_GET['url']) : null));
                return $this->getResponse();
            }

            $request->setUrl(self::get_homepage_link() . '/');
            $request->match('$URLSegment//$Action', true);
            $controller = new ModelAsController();

            $response = $controller->handleRequest($request);

            $this->prepareResponse($response);
        }

        $this->afterHandleRequest();

        return $this->getResponse();
    }
}
