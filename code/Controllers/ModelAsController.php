<?php

namespace SilverStripe\CMS\Controllers;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\NestedController;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\Debug;
use SilverStripe\Dev\Deprecation;
use SilverStripe\ORM\DataModel;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use Exception;
use Translatable;

/**
 * ModelAsController deals with mapping the initial request to the first {@link SiteTree}/{@link ContentController}
 * pair, which are then used to handle the request.
 */
class ModelAsController extends Controller implements NestedController {

    private static $extensions = array('SilverStripe\\CMS\\Controllers\\OldPageRedirector');

    /**
     * Get the appropriate {@link ContentController} for handling a {@link SiteTree} object, link it to the object and
     * return it.
     *
     * @param SiteTree $sitetree
     * @param string $action
     * @return ContentController
     */
    public static function controller_for(SiteTree $sitetree, $action = null) {
        $controller = $sitetree->getControllerName();

        if ($action && class_exists($controller . '_' . ucfirst($action))) {
            $controller = $controller . '_' . ucfirst($action);
        }

        return Injector::inst()->create($controller, $sitetree);
    }

    public function init() {
        singleton('SilverStripe\\CMS\\Model\\SiteTree')->extend('modelascontrollerInit', $this);
        parent::init();
    }

    protected function beforeHandleRequest(HTTPRequest $request, DataModel $model) {
        parent::beforeHandleRequest($request, $model);
        // If the database has not yet been created, redirect to the build page.
        /** @skipUpgrade */
        if(!DB::is_active() || !ClassInfo::hasTable('SiteTree')) {
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
     * @uses ModelAsController::getNestedController()
     * @param HTTPRequest $request
     * @param DataModel $model
     * @return HTTPResponse
     */
    public function handleRequest(HTTPRequest $request, DataModel $model) {
        $this->beforeHandleRequest($request, $model);

        // If we had a redirection or something, halt processing.
        if($this->getResponse()->isFinished()) {
            $this->popCurrent();
            return $this->getResponse();
        }

        // If the database has not yet been created, redirect to the build page.
        /** @skipUpgrade */
        if(!DB::is_active() || !ClassInfo::hasTable('SiteTree')) {
            $this->getResponse()->redirect(Director::absoluteBaseURL() . 'dev/build?returnURL=' . (isset($_GET['url']) ? urlencode($_GET['url']) : null));
            $this->popCurrent();

            return $this->getResponse();
        }

        try {
            $result = $this->getNestedController();

            if($result instanceof RequestHandler) {
                $result = $result->handleRequest($this->getRequest(), $model);
            } else if(!($result instanceof HTTPResponse)) {
                user_error("ModelAsController::getNestedController() returned bad object type '" .
                    get_class($result)."'", E_USER_WARNING);
            }
        } catch(HTTPResponse_Exception $responseException) {
            $result = $responseException->getResponse();
        }

        $this->popCurrent();
        return $result;
    }

    /**
     * @return ContentController
     * @throws Exception If URLSegment not passed in as a request parameter.
     */
    public function getNestedController() {
        $request = $this->getRequest();

        if(!$URLSegment = $request->param('URLSegment')) {
            throw new Exception('ModelAsController->getNestedController(): was not passed a URLSegment value.');
        }

        // Find page by link, regardless of current locale settings
        if(class_exists('Translatable')) Translatable::disable_locale_filter();

        // Select child page
        $conditions = array('"SiteTree"."URLSegment"' => rawurlencode($URLSegment));
        if(SiteTree::config()->nested_urls) {
            $conditions[] = array('"SiteTree"."ParentID"' => 0);
        }
        /** @var SiteTree $sitetree */
        $sitetree = DataObject::get_one('SilverStripe\\CMS\\Model\\SiteTree', $conditions);

        // Check translation module
        // @todo Refactor out module specific code
        if(class_exists('Translatable')) Translatable::enable_locale_filter();

        if(!$sitetree) {
            $this->httpError(404, 'The requested page could not be found.');
        }

        // Enforce current locale setting to the loaded SiteTree object
        if(class_exists('Translatable') && $sitetree->Locale) Translatable::set_current_locale($sitetree->Locale);

        if(isset($_REQUEST['debug'])) {
            Debug::message("Using record #$sitetree->ID of type " . get_class($sitetree) . " with link {$sitetree->Link()}");
        }

        return self::controller_for($sitetree, $this->getRequest()->param('Action'));
    }

    /**
     * @deprecated 4.0 Use OldPageRedirector::find_old_page instead
     *
     * @param string $URLSegment A subset of the url. i.e in /home/contact/ home and contact are URLSegment.
     * @param int $parent The ID of the parent of the page the URLSegment belongs to.
     * @param bool $ignoreNestedURLs
     * @return SiteTree
     */
    static public function find_old_page($URLSegment, $parent = null, $ignoreNestedURLs = false) {
        Deprecation::notice('4.0', 'Use OldPageRedirector::find_old_page instead');
        if ($parent) {
            $parent = SiteTree::get()->byID($parent);
        }
        $url = OldPageRedirector::find_old_page(array($URLSegment), $parent);
        return SiteTree::get_by_link($url);
    }
}
