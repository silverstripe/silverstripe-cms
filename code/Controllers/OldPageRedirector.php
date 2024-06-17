<?php

namespace SilverStripe\CMS\Controllers;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Core\Extension;

/**
 * @extends Extension<ContentController|ModelAsController>
 */
class OldPageRedirector extends Extension
{
    /**
     * On every URL that generates a 404, we'll capture it here and see if we can
     * find an old URL that it should be redirecting to.
     *
     * @throws HTTPResponse_Exception
     */
    public function onBeforeHTTPError404(HTTPRequest $request)
    {
        // We need to get the URL ourselves because $request->allParams() only has a max of 4 params
        $params = preg_split('|/+|', $request->getURL() ?? '');
        $cleanURL = trim(Director::makeRelative($request->getURL(false)) ?? '', '/');

        $getvars = $request->getVars();
        unset($getvars['url']);

        $page = OldPageRedirector::find_old_page($params, 0);
        if (!$page) {
            $page = OldPageRedirector::find_old_page($params);
        }
        $cleanPage = trim(Director::makeRelative($page) ?? '', '/');
        if (!$cleanPage) {
            $cleanPage = Director::makeRelative(RootURLController::get_homepage_link());
        }

        if ($page && $cleanPage != $cleanURL) {
            $res = new HTTPResponse();
            $res->redirect(
                Controller::join_links(
                    $page,
                    ($getvars) ? '?' . http_build_query($getvars) : null
                ),
                301
            );
            throw new HTTPResponse_Exception($res);
        }
    }

    /**
     * Attempt to find an old/renamed page from some given the URL as an array
     *
     * @param array $params The array of URL, e.g. /foo/bar as ['foo', 'bar']
     * @param SiteTree|null $parent The current parent in the recursive flow
     * @param boolean $redirect Whether we've found an old page worthy of a redirect
     *
     * @return string|boolean False, or the new URL
     */
    public static function find_old_page($params, $parent = null, $redirect = false)
    {
        $parent = is_numeric($parent) && $parent > 0 ? SiteTree::get()->byID($parent) : $parent;
        $params = (array)$params;
        $URL = rawurlencode(array_shift($params) ?? '');
        if (empty($URL)) {
            return false;
        }
        $pages = SiteTree::get()->filter([
            'URLSegment' => $URL,
        ]);
        if ($parent || is_numeric($parent)) {
            $pages = $pages->filter([
                'ParentID' => is_numeric($parent) ? $parent : $parent->ID,
            ]);
        }
        $page = $pages->first();
        if (!$page) {
            // If we haven't found a candidate, lets resort to finding an old page with this URL segment
            $pages = $pages
                ->filter('WasPublished', 1)
                ->sort('LastEdited', 'DESC')
                ->setDataQueryParam("Versioned.mode", 'all_versions');

            $record = $pages->first();
            if ($record) {
                $page = SiteTree::get()->byID($record->ID);
                $redirect = true;
            }
        }

        if ($page && $page->canView()) {
            if (count($params ?? [])) {
                // We have to go deeper!
                $ret = OldPageRedirector::find_old_page($params, $page, $redirect);
                if ($ret) {
                    // A valid child page was found! We can return it
                    return $ret;
                } else {
                    // No valid page found.
                    if ($redirect) {
                        // If we had some redirect to be done, lets do it. imagine /foo/action -> /bar/action, we still want this redirect to happen if action isn't a page
                        return Controller::join_links($page->Link(), implode('/', $params));
                    }
                }
            } else {
                // We've found the final, end all, page.
                return $page->Link();
            }
        }

        return false;
    }
}
