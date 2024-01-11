<?php

namespace SilverStripe\CMS\Controllers;

use SilverStripe\Admin\Navigator\SilverStripeNavigator;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Control\Middleware\HTTPCacheControlMiddleware;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Manifest\ModuleManifest;
use SilverStripe\i18n\i18n;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\FieldType\DBVarchar;
use SilverStripe\ORM\SS_List;
use SilverStripe\Security\MemberAuthenticator\MemberAuthenticator;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Versioned\Versioned;
use SilverStripe\View\ArrayData;
use SilverStripe\View\Parsers\URLSegmentFilter;
use SilverStripe\View\Requirements;
use SilverStripe\View\SSViewer;

/**
 * The most common kind of controller; effectively a controller linked to a {@link DataObject}.
 *
 * ContentControllers are most useful in the content-focused areas of a site.  This is generally
 * the bulk of a site; however, they may be less appropriate in, for example, the user management
 * section of an application.
 *
 * On its own, content controller does very little.  Its constructor is passed a {@link DataObject}
 * which is stored in $this->dataRecord.  Any unrecognised method calls, for example, Title()
 * and Content(), will be passed along to the data record,
 *
 * Subclasses of ContentController are generally instantiated by ModelAsController; this will create
 * a controller based on the URLSegment action variable, by looking in the SiteTree table.
 *
 * @template T of SiteTree
 */
class ContentController extends Controller
{
    /**
     * @var T
     */
    protected $dataRecord;

    private static $extensions = [
        OldPageRedirector::class,
    ];

    private static $allowed_actions = [
        'successfullyinstalled',
        'deleteinstallfiles', // secured through custom code
        'LoginForm',
    ];

    private static $casting = [
        'SilverStripeNavigator' => 'HTMLFragment',
    ];

    /**
     * The ContentController will take the URLSegment parameter from the URL and use that to look
     * up a SiteTree record.
     *
     * @param T|null $dataRecord
     */
    public function __construct($dataRecord = null)
    {
        if (!$dataRecord) {
            $dataRecord = new SiteTree();
            if ($this->hasMethod("Title")) {
                $dataRecord->Title = $this->Title();
            }
            $dataRecord->URLSegment = static::class;
            $dataRecord->ID = -1;
        }

        $this->dataRecord = $dataRecord;

        parent::__construct();

        $this->setFailover($this->dataRecord);
    }

    /**
     * Return the link to this controller, but force the expanded link to be returned so that form methods and
     * similar will function properly.
     *
     * @param string|null $action Action to link to.
     * @return string
     */
    public function Link($action = null)
    {
        return $this->data()->Link(($action ? $action : true));
    }

    //----------------------------------------------------------------------------------//
    // These flexible data methods remove the need for custom code to do simple stuff

    /**
     * Return the children of a given page. The parent reference can either be a page link or an ID.
     *
     * @param string|int $parentRef
     * @return SS_List<SiteTree>
     */
    public function ChildrenOf($parentRef)
    {
        $parent = SiteTree::get_by_link($parentRef);

        if (!$parent && is_numeric($parentRef)) {
            $parent = DataObject::get_by_id(SiteTree::class, $parentRef);
        }

        if ($parent) {
            return $parent->Children();
        }
        return null;
    }

    /**
     * @param string $link
     * @return SiteTree
     */
    public function Page($link)
    {
        return SiteTree::get_by_link($link);
    }

    protected function init()
    {
        parent::init();

        // In the CMS Preview or draft contexts, we never want to cache page output.
        if ($this->getRequest()->getVar('CMSPreview') === '1'
            || $this->getRequest()->getVar('stage') === Versioned::DRAFT
        ) {
            HTTPCacheControlMiddleware::singleton()->disableCache(true);
        }

        // If we've accessed the homepage as /home/, then we should redirect to /.
        if ($this->dataRecord instanceof SiteTree
            && RootURLController::should_be_on_root($this->dataRecord)
            && (!isset($this->urlParams['Action']) || !$this->urlParams['Action'])
            && !$_POST && !$_FILES && !$this->redirectedTo()
        ) {
            $getVars = $_GET;
            unset($getVars['url']);
            if ($getVars) {
                $url = "?" . http_build_query($getVars ?? []);
            } else {
                $url = "";
            }
            $this->redirect($url, 301);
            return;
        }

        if ($this->dataRecord) {
            $this->dataRecord->extend('contentcontrollerInit', $this);
        } else {
            SiteTree::singleton()->extend('contentcontrollerInit', $this);
        }

        if ($this->redirectedTo()) {
            return;
        }

        // Check page permissions
        if ($this->dataRecord && $this->URLSegment != 'Security' && !$this->dataRecord->canView()) {
            Security::permissionFailure($this);
            return;
        }
    }

    /**
     * This acts the same as {@link Controller::handleRequest()}, but if an action cannot be found this will attempt to
     * fall over to a child controller in order to provide functionality for nested URLs.
     *
     * @throws HTTPResponse_Exception
     */
    public function handleRequest(HTTPRequest $request): HTTPResponse
    {
        $child = null;
        $action = $request->param('Action');

        // If nested URLs are enabled, and there is no action handler for the current request then attempt to pass
        // control to a child controller. This allows for the creation of chains of controllers which correspond to a
        // nested URL.
        if ($action && SiteTree::config()->nested_urls && !$this->hasAction($action)) {
            $filter = URLSegmentFilter::create();

            // look for a page with this URLSegment
            $child = SiteTree::get()->filter([
                'ParentID' => $this->ID,
                // url encode unless it's multibyte (already pre-encoded in the database)
                'URLSegment' => $filter->getAllowMultibyte() ? $action : rawurlencode($action),
            ])->first();
        }

        // we found a page with this URLSegment.
        if ($child) {
            $request->shiftAllParams();
            $request->shift();

            $response = ModelAsController::controller_for($child)->handleRequest($request);
        } else {
            Director::set_current_page($this->data());

            try {
                $response = parent::handleRequest($request);

                Director::set_current_page(null);
            } catch (HTTPResponse_Exception $e) {
                $this->popCurrent();

                Director::set_current_page(null);

                throw $e;
            }
        }

        return $response;
    }

    /**
     * Get the project name
     *
     * @return string
     */
    public function project()
    {
        return ModuleManifest::config()->get('project');
    }

    /**
     * Returns the associated database record
     * @return T
     */
    public function data()
    {
        return $this->dataRecord;
    }

    /*--------------------------------------------------------------------------------*/

    /**
     * Returns a fixed navigation menu of the given level.
     * @param int $level Menu level to return.
     * @return ArrayList<SiteTree>
     */
    public function getMenu($level = 1)
    {
        if ($level == 1) {
            $result = SiteTree::get()->filter([
                "ShowInMenus" => 1,
                "ParentID" => 0,
            ]);
        } else {
            $parent = $this->data();
            $stack = [$parent];

            if ($parent) {
                while (($parent = $parent->Parent()) && $parent->exists()) {
                    array_unshift($stack, $parent);
                }
            }

            if (isset($stack[$level - 2])) {
                $result = $stack[$level - 2]->Children();
            }
        }

        $visible = [];

        // Remove all entries the can not be viewed by the current user
        // We might need to create a show in menu permission
        if (isset($result)) {
            foreach ($result as $page) {
                if ($page->canView()) {
                    $visible[] = $page;
                }
            }
        }

        return new ArrayList($visible);
    }

    /**
     * @return ArrayList<SiteTree>
     */
    public function Menu($level)
    {
        return $this->getMenu($level);
    }

    /**
     * Returns the default log-in form.
     *
     * @return \SilverStripe\Security\MemberAuthenticator\MemberLoginForm
     */
    public function LoginForm()
    {
        return Injector::inst()->get(MemberAuthenticator::class)->getLoginHandler($this->Link())->loginForm();
    }

    public function SilverStripeNavigator()
    {
        $member = Security::getCurrentUser();
        $items = '';
        $message = '';

        if (Director::isDev() || Permission::check('CMS_ACCESS_CMSMain') || Permission::check('VIEW_DRAFT_CONTENT')) {
            if ($this->dataRecord) {
                Requirements::css('silverstripe/cms: client/dist/styles/SilverStripeNavigator.css');
                Requirements::javascript('silverstripe/cms: client/dist/js/SilverStripeNavigator.js');

                $return = $nav = SilverStripeNavigator::get_for_record($this->dataRecord);
                $items = $return['items'];
                $message = $return['message'];
            }

            if ($member) {
                $firstname = Convert::raw2xml($member->FirstName);
                $surname = Convert::raw2xml($member->Surname);
                $logInMessage = _t(__CLASS__ . '.LOGGEDINAS', 'Logged in as') . " {$firstname} {$surname} - <a href=\"Security/logout\">" . _t(__CLASS__ . '.LOGOUT', 'Log out') . "</a>";
            } else {
                $logInMessage = sprintf(
                    '%s - <a href="%s">%s</a>',
                    _t(__CLASS__ . '.NOTLOGGEDIN', 'Not logged in'),
                    Security::config()->login_url,
                    _t(__CLASS__ . '.LOGIN', 'Login') . "</a>"
                );
            }
            $viewPageIn = _t(__CLASS__ . '.VIEWPAGEIN', 'View Page in:');

            return <<<HTML
				<div id="SilverStripeNavigator">
					<div class="holder">
					<div id="logInStatus">
						$logInMessage
					</div>

					<div id="switchView" class="bottomTabs">
						$viewPageIn
						$items
					</div>
					</div>
				</div>
					$message
HTML;

            // On live sites we should still see the archived message
        } else {
            if ($date = Versioned::current_archived_date()) {
                Requirements::css('silverstripe/cms: client/dist/styles/SilverStripeNavigator.css');
                /** @var DBDatetime $dateObj */
                $dateObj = DBField::create_field('Datetime', $date);
                // $dateObj->setVal($date);
                return "<div id=\"SilverStripeNavigatorMessage\">" .
                    _t(__CLASS__ . '.ARCHIVEDSITEFROM', 'Archived site from') .
                    "<br>" . $dateObj->Nice() . "</div>";
            }
        }
        return null;
    }

    public function SiteConfig()
    {
        if (method_exists($this->dataRecord, 'getSiteConfig')) {
            return $this->dataRecord->getSiteConfig();
        } else {
            return SiteConfig::current_site_config();
        }
    }

    /**
     * Returns an RFC1766 compliant locale string, e.g. 'fr-CA'.
     *
     * Suitable for insertion into lang= and xml:lang=
     * attributes in HTML or XHTML output.
     *
     * @return string
     */
    public function ContentLocale()
    {
        $locale = i18n::get_locale();
        return i18n::convert_rfc1766($locale);
    }


    /**
     * Return an SSViewer object to render the template for the current page.
     *
     * @param $action string
     *
     * @return SSViewer
     */
    public function getViewer($action)
    {
        // Manually set templates should be dealt with by Controller::getViewer()
        if (!empty($this->templates[$action])
            || !empty($this->templates['index'])
            || $this->template
        ) {
            return parent::getViewer($action);
        }

        // Prepare action for template search
        $action = $action === 'index' ? '' : '_' . $action;

        $templatesFound = [];
        // Find templates for the record + action together - e.g. Page_action.ss
        if ($this->dataRecord instanceof SiteTree) {
            $templatesFound[] = $this->dataRecord->getViewerTemplates($action);
        }

        // Find templates for the controller + action together - e.g. PageController_action.ss
        $templatesFound[] = SSViewer::get_templates_by_class(static::class, $action, Controller::class);

        // Find templates for the record without an action - e.g. Page.ss
        if ($this->dataRecord instanceof SiteTree) {
            $templatesFound[] = $this->dataRecord->getViewerTemplates();
        }

        // Find the templates for the controller without an action - e.g. PageController.ss
        $templatesFound[] = SSViewer::get_templates_by_class(static::class, "", Controller::class);

        $templates = array_merge(...$templatesFound);
        return SSViewer::create($templates);
    }


    /**
     * This action is called by the installation system
     */
    public function successfullyinstalled()
    {
        // Return 410 Gone if this site is not actually a fresh installation
        if (!file_exists(PUBLIC_PATH . '/install.php')) {
            $this->httpError(410);
        }

        if (isset($_SESSION['StatsID']) && $_SESSION['StatsID']) {
            $url = 'http://ss2stat.silverstripe.com/Installation/installed?ID=' . $_SESSION['StatsID'];
            @file_get_contents($url ?? '');
        }

        global $project;
        $data = new ArrayData([
            'Project' => Convert::raw2xml($project),
            'Username' => Convert::raw2xml($this->getRequest()->getSession()->get('username')),
            'Password' => Convert::raw2xml($this->getRequest()->getSession()->get('password')),
        ]);

        return [
            "Title" =>  _t(__CLASS__ . ".INSTALL_SUCCESS", "Installation Successful!"),
            "Content" => $data->renderWith([
                'type' => 'Includes',
                'Install_successfullyinstalled',
            ]),
        ];
    }

    public function deleteinstallfiles()
    {
        if (!Permission::check("ADMIN")) {
            return Security::permissionFailure($this);
        }

        $title = new DBVarchar("Title");
        $content = new DBHTMLText('Content');

        // As of SS4, index.php is required and should never be deleted.
        $installfiles = [
            'install.php',
            'install-frameworkmissing.html',
            'index.html'
        ];

        $unsuccessful = new ArrayList();
        foreach ($installfiles as $installfile) {
            $installfilepath = PUBLIC_PATH . '/' . $installfile;
            if (file_exists($installfilepath ?? '')) {
                @unlink($installfilepath ?? '');
            }

            if (file_exists($installfilepath ?? '')) {
                $unsuccessful->push(new ArrayData(['File' => $installfile]));
            }
        }

        $data = new ArrayData([
            'Username' => Convert::raw2xml($this->getRequest()->getSession()->get('username')),
            'Password' => Convert::raw2xml($this->getRequest()->getSession()->get('password')),
            'UnsuccessfulFiles' => $unsuccessful,
        ]);
        $content->setValue($data->renderWith([
            'type' => 'Includes',
            'Install_deleteinstallfiles',
        ]));

        return [
            "Title" => $title,
            "Content" => $content,
        ];
    }
}
