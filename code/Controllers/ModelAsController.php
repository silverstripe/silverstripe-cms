<?php

namespace SilverStripe\CMS\Controllers;

use Exception;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Control\NestedController;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\Debug;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\View\Parsers\URLSegmentFilter;

/**
 * ModelAsController deals with mapping the initial request to the first {@link SiteTree}/{@link ContentController}
 * pair, which are then used to handle the request.
 */
class ModelAsController extends Controller implements NestedController
{
    private static $extensions = [
        OldPageRedirector::class,
    ];

    /**
     * Get the appropriate {@link ContentController} for handling a {@link SiteTree} object, link it to the object and
     * return it.
     *
     * @param string $action
     */
    public static function controller_for(SiteTree $sitetree, $action = null): ContentController
    {
        $controller = $sitetree->getControllerName();

        if ($action && class_exists($controller . '_' . ucfirst($action ?? ''))) {
            $controller = $controller . '_' . ucfirst($action ?? '');
        }

        return Injector::inst()->create($controller, $sitetree);
    }

    protected function init()
    {
        singleton(SiteTree::class)->extend('modelascontrollerInit', $this);
        parent::init();
    }

    protected function beforeHandleRequest(HTTPRequest $request)
    {
        parent::beforeHandleRequest($request);
        // If the database has not yet been created, redirect to the build page.
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

    /**
     * @uses ModelAsController::getNestedController()
     */
    public function handleRequest(HTTPRequest $request): HTTPResponse
    {
        $this->beforeHandleRequest($request);

        // If we had a redirection or something, halt processing.
        if ($this->getResponse()->isFinished()) {
            $this->popCurrent();
            return $this->getResponse();
        }

        // If the database has not yet been created, redirect to the build page.
        if (!DB::is_active() || !ClassInfo::hasTable('SiteTree')) {
            $this->getResponse()->redirect(Controller::join_links(Director::absoluteBaseURL(), 'dev/build?returnURL=' . (isset($_GET['url']) ? urlencode($_GET['url']) : null)));
            $this->popCurrent();

            return $this->getResponse();
        }

        try {
            $result = $this->getNestedController()->handleRequest($this->getRequest());
            $result = $result;
        } catch (HTTPResponse_Exception $responseException) {
            $result = $responseException->getResponse();
        }

        $this->popCurrent();
        return $result;
    }

    /**
     * @throws Exception If URLSegment not passed in as a request parameter.
     */
    public function getNestedController(): ContentController
    {
        $request = $this->getRequest();
        $urlSegment = $request->param('URLSegment');

        if ($urlSegment === false || $urlSegment === null || $urlSegment === '') {
            throw new Exception('ModelAsController->getNestedController(): was not passed a URLSegment value.');
        }

        // url encode unless it's multibyte (already pre-encoded in the database)
        $filter = URLSegmentFilter::create();

        if (!$filter->getAllowMultibyte()) {
            $urlSegment = rawurlencode($urlSegment ?? '');
        }

        // Select child page
        $tableName = DataObject::singleton(SiteTree::class)->baseTable();
        $conditions = [sprintf('"%s"."URLSegment"', $tableName) => $urlSegment];
        if (SiteTree::config()->get('nested_urls')) {
            $conditions[] = [sprintf('"%s"."ParentID"', $tableName) => 0];
        }
        $sitetree = DataObject::get_one(SiteTree::class, $conditions);

        if (!$sitetree) {
            $this->httpError(404, 'The requested page could not be found.');
        }

        if (isset($_REQUEST['debug'])) {
            Debug::message("Using record #$sitetree->ID of type " . get_class($sitetree) . " with link {$sitetree->Link()}");
        }

        return ModelAsController::controller_for($sitetree, $this->getRequest()->param('Action'));
    }
}
