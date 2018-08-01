<?php

namespace SilverStripe\CMS\Controllers;

use InvalidArgumentException;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\Admin\AdminRootController;
use SilverStripe\Admin\CMSBatchActionHandler;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Admin\LeftAndMainFormRequestHandler;
use SilverStripe\CMS\BatchActions\CMSBatchAction_Archive;
use SilverStripe\CMS\BatchActions\CMSBatchAction_Publish;
use SilverStripe\CMS\BatchActions\CMSBatchAction_Restore;
use SilverStripe\CMS\BatchActions\CMSBatchAction_Unpublish;
use SilverStripe\CMS\Model\CurrentPageIdentifier;
use SilverStripe\CMS\Model\RedirectorPage;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\CMS\Model\VirtualPage;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldLevelup;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridFieldSortableHeader;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\LabelField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\CMSPreviewable;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\HiddenClass;
use SilverStripe\ORM\Hierarchy\MarkedSet;
use SilverStripe\ORM\SS_List;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Security\InheritedPermissions;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Security\Security;
use SilverStripe\Security\SecurityToken;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Versioned\ChangeSet;
use SilverStripe\Versioned\ChangeSetItem;
use SilverStripe\Versioned\Versioned;
use SilverStripe\View\ArrayData;
use SilverStripe\View\Requirements;
use SilverStripe\Core\Flushable;
use SilverStripe\Core\Cache\MemberCacheFlusher;
use Translatable;

/**
 * The main "content" area of the CMS.
 *
 * This class creates a 2-frame layout - left-tree and right-form - to sit beneath the main
 * admin menu.
 *
 * @todo Create some base classes to contain the generic functionality that will be replicated.
 *
 * @mixin LeftAndMainPageIconsExtension
 */
class CMSMain extends LeftAndMain implements CurrentPageIdentifier, PermissionProvider, Flushable, MemberCacheFlusher
{
    /**
     * Unique ID for page icons CSS block
     */
    const PAGE_ICONS_ID = 'PageIcons';

    private static $url_segment = 'pages';

    private static $url_rule = '/$Action/$ID/$OtherID';

    // Maintain a lower priority than other administration sections
    // so that Director does not think they are actions of CMSMain
    private static $url_priority = 39;

    private static $menu_title = 'Edit Page';

    private static $menu_icon_class = 'font-icon-sitemap';

    private static $menu_priority = 10;

    private static $tree_class = SiteTree::class;

    private static $subitem_class = Member::class;

    private static $session_namespace = self::class;

    private static $required_permission_codes = 'CMS_ACCESS_CMSMain';

    /**
     * Amount of results showing on a single page.
     *
     * @config
     * @var int
     */
    private static $page_length = 15;

    private static $allowed_actions = array(
        'archive',
        'deleteitems',
        'DeleteItemsForm',
        'dialog',
        'duplicate',
        'duplicatewithchildren',
        'publishall',
        'publishitems',
        'PublishItemsForm',
        'submit',
        'EditForm',
        'SearchForm',
        'SiteTreeAsUL',
        'getshowdeletedsubtree',
        'savetreenode',
        'getsubtree',
        'updatetreenodes',
        'batchactions',
        'treeview',
        'listview',
        'ListViewForm',
        'childfilter',
    );

    private static $url_handlers = [
        'EditForm/$ID' => 'EditForm',
    ];

    private static $casting = array(
        'TreeIsFiltered' => 'Boolean',
        'AddForm' => 'HTMLFragment',
        'LinkPages' => 'Text',
        'Link' => 'Text',
        'ListViewForm' => 'HTMLFragment',
        'ExtraTreeTools' => 'HTMLFragment',
        'PageList' => 'HTMLFragment',
        'PageListSidebar' => 'HTMLFragment',
        'SiteTreeHints' => 'HTMLFragment',
        'SecurityID' => 'Text',
        'SiteTreeAsUL' => 'HTMLFragment',
    );

    private static $dependencies = [
        'HintsCache' => '%$' . CacheInterface::class . '.CMSMain_SiteTreeHints',
    ];

    /**
     * @var CacheInterface
     */
    protected $hintsCache;

    protected function init()
    {
        // set reading lang
        if (SiteTree::has_extension('Translatable') && !$this->getRequest()->isAjax()) {
            Translatable::choose_site_locale(array_keys(Translatable::get_existing_content_languages(SiteTree::class)));
        }

        parent::init();

        Requirements::javascript('silverstripe/cms: client/dist/js/bundle.js');
        Requirements::javascript('silverstripe/cms: client/dist/js/SilverStripeNavigator.js');
        Requirements::css('silverstripe/cms: client/dist/styles/bundle.css');
        Requirements::customCSS($this->generatePageIconsCss(), self::PAGE_ICONS_ID);

        Requirements::add_i18n_javascript('silverstripe/cms: client/lang', false, true);

        CMSBatchActionHandler::register('restore', CMSBatchAction_Restore::class);
        CMSBatchActionHandler::register('archive', CMSBatchAction_Archive::class);
        CMSBatchActionHandler::register('unpublish', CMSBatchAction_Unpublish::class);
        CMSBatchActionHandler::register('publish', CMSBatchAction_Publish::class);
    }

    public function index($request)
    {
        // In case we're not showing a specific record, explicitly remove any session state,
        // to avoid it being highlighted in the tree, and causing an edit form to show.
        if (!$request->param('Action')) {
            $this->setCurrentPageID(null);
        }

        return parent::index($request);
    }

    public function getResponseNegotiator()
    {
        $negotiator = parent::getResponseNegotiator();

        // ListViewForm
        $negotiator->setCallback('ListViewForm', function () {
            return $this->ListViewForm()->forTemplate();
        });

        return $negotiator;
    }

    /**
     * Get pages listing area
     *
     * @return DBHTMLText
     */
    public function PageList()
    {
        return $this->renderWith($this->getTemplatesWithSuffix('_PageList'));
    }

    /**
     * Page list view for edit-form
     *
     * @return DBHTMLText
     */
    public function PageListSidebar()
    {
        return $this->renderWith($this->getTemplatesWithSuffix('_PageList_Sidebar'));
    }

    /**
     * If this is set to true, the "switchView" context in the
     * template is shown, with links to the staging and publish site.
     *
     * @return boolean
     */
    public function ShowSwitchView()
    {
        return true;
    }

    /**
     * Overloads the LeftAndMain::ShowView. Allows to pass a page as a parameter, so we are able
     * to switch view also for archived versions.
     *
     * @param SiteTree $page
     * @return array
     */
    public function SwitchView($page = null)
    {
        if (!$page) {
            $page = $this->currentPage();
        }

        if ($page) {
            $nav = SilverStripeNavigator::get_for_record($page);
            return $nav['items'];
        }
    }

    //------------------------------------------------------------------------------------------//
    // Main controllers

    //------------------------------------------------------------------------------------------//
    // Main UI components

    /**
     * Override {@link LeftAndMain} Link to allow blank URL segment for CMSMain.
     *
     * @param string|null $action Action to link to.
     * @return string
     */
    public function Link($action = null)
    {
        $link = Controller::join_links(
            AdminRootController::admin_url(),
            $this->config()->get('url_segment'), // in case we want to change the segment
            '/', // trailing slash needed if $action is null!
            "$action"
        );
        $this->extend('updateLink', $link);
        return $link;
    }

    public function LinkPages()
    {
        return CMSPagesController::singleton()->Link();
    }

    public function LinkPagesWithSearch()
    {
        return $this->LinkWithSearch($this->LinkPages());
    }

    /**
     * Get link to tree view
     *
     * @return string
     */
    public function LinkTreeView()
    {
        // Tree view is just default link to main pages section (no /treeview suffix)
        return CMSMain::singleton()->Link();
    }

    /**
     * Get link to list view
     *
     * @return string
     */
    public function LinkListView()
    {
        // Note : Force redirect to top level page controller (no parentid)
        return $this->LinkWithSearch(CMSMain::singleton()->Link('listview'));
    }

    /**
     * Link to list view for children of a parent page
     *
     * @param int|string $parentID Literal parentID, or placeholder (e.g. '%d') for
     * client side substitution
     * @return string
     */
    public function LinkListViewChildren($parentID)
    {
        return sprintf(
            '%s?ParentID=%s',
            CMSMain::singleton()->Link(),
            $parentID
        );
    }

    /**
     * @return string
     */
    public function LinkListViewRoot()
    {
        return $this->LinkListViewChildren(0);
    }

    /**
     * Link to lazy-load deferred tree view
     *
     * @return string
     */
    public function LinkTreeViewDeferred()
    {
        return $this->Link('treeview');
    }

    /**
     * Link to lazy-load deferred list view
     *
     * @return string
     */
    public function LinkListViewDeferred()
    {
        return $this->Link('listview');
    }

    public function LinkPageEdit($id = null)
    {
        if (!$id) {
            $id = $this->currentPageID();
        }
        return $this->LinkWithSearch(
            Controller::join_links(CMSPageEditController::singleton()->Link('show'), $id)
        );
    }

    public function LinkPageSettings()
    {
        if ($id = $this->currentPageID()) {
            return $this->LinkWithSearch(
                Controller::join_links(CMSPageSettingsController::singleton()->Link('show'), $id)
            );
        } else {
            return null;
        }
    }

    public function LinkPageHistory()
    {
        if ($id = $this->currentPageID()) {
            return $this->LinkWithSearch(
                Controller::join_links(CMSPageHistoryController::singleton()->Link('show'), $id)
            );
        } else {
            return null;
        }
    }

    /**
     * Return the active tab identifier for the CMS. Used by templates to decide which tab to give the active state.
     * The default value is "edit", as the primary content tab. Child controllers will override this.
     *
     * @return string
     */
    public function getTabIdentifier()
    {
        return 'edit';
    }

    /**
     * @param CacheInterface $cache
     * @return $this
     */
    public function setHintsCache(CacheInterface $cache)
    {
        $this->hintsCache = $cache;

        return $this;
    }

    /**
     * @return CacheInterface $cache
     */
    public function getHintsCache()
    {
        return $this->hintsCache;
    }

    /**
     * Clears all dependent cache backends
     */
    public function clearCache()
    {
        $this->getHintsCache()->clear();
    }

    public function LinkWithSearch($link)
    {
        // Whitelist to avoid side effects
        $params = array(
            'q' => (array)$this->getRequest()->getVar('q'),
            'ParentID' => $this->getRequest()->getVar('ParentID')
        );
        $link = Controller::join_links(
            $link,
            array_filter(array_values($params)) ? '?' . http_build_query($params) : null
        );
        $this->extend('updateLinkWithSearch', $link);
        return $link;
    }

    public function LinkPageAdd($extra = null, $placeholders = null)
    {
        $link = CMSPageAddController::singleton()->Link();
        $this->extend('updateLinkPageAdd', $link);

        if ($extra) {
            $link = Controller::join_links($link, $extra);
        }

        if ($placeholders) {
            $link .= (strpos($link, '?') === false ? "?$placeholders" : "&$placeholders");
        }

        return $link;
    }

    /**
     * @return string
     */
    public function LinkPreview()
    {
        $record = $this->getRecord($this->currentPageID());
        $baseLink = Director::absoluteBaseURL();
        if ($record && $record instanceof SiteTree) {
            // if we are an external redirector don't show a link
            if ($record instanceof RedirectorPage && $record->RedirectionType == 'External') {
                $baseLink = false;
            } else {
                $baseLink = $record->Link('?stage=Stage');
            }
        }
        return $baseLink;
    }

    /**
     * Return the entire site tree as a nested set of ULs
     */
    public function SiteTreeAsUL()
    {
        // Pre-cache sitetree version numbers for querying efficiency
        Versioned::prepopulate_versionnumber_cache(SiteTree::class, Versioned::DRAFT);
        Versioned::prepopulate_versionnumber_cache(SiteTree::class, Versioned::LIVE);
        $html = $this->getSiteTreeFor($this->config()->get('tree_class'));

        $this->extend('updateSiteTreeAsUL', $html);

        return $html;
    }

    /**
     * Get a site tree HTML listing which displays the nodes under the given criteria.
     *
     * @param string $className The class of the root object
     * @param string $rootID The ID of the root object.  If this is null then a complete tree will be
     *  shown
     * @param string $childrenMethod The method to call to get the children of the tree. For example,
     *  Children, AllChildrenIncludingDeleted, or AllHistoricalChildren
     * @param string $numChildrenMethod
     * @param callable $filterFunction
     * @param int $nodeCountThreshold
     * @return string Nested unordered list with links to each page
     */
    public function getSiteTreeFor(
        $className,
        $rootID = null,
        $childrenMethod = null,
        $numChildrenMethod = null,
        $filterFunction = null,
        $nodeCountThreshold = null
    ) {
        $nodeCountThreshold = is_null($nodeCountThreshold) ? Config::inst()->get($className, 'node_threshold_total') : $nodeCountThreshold;
        // Provide better defaults from filter
        $filter = $this->getSearchFilter();
        if ($filter) {
            if (!$childrenMethod) {
                $childrenMethod = $filter->getChildrenMethod();
            }
            if (!$numChildrenMethod) {
                $numChildrenMethod = $filter->getNumChildrenMethod();
            }
            if (!$filterFunction) {
                $filterFunction = function ($node) use ($filter) {
                    return $filter->isPageIncluded($node);
                };
            }
        }

        // Build set from node and begin marking
        $record = ($rootID) ? $this->getRecord($rootID) : null;
        $rootNode = $record ? $record : DataObject::singleton($className);
        $markingSet = MarkedSet::create($rootNode, $childrenMethod, $numChildrenMethod, $nodeCountThreshold);

        // Set filter function
        if ($filterFunction) {
            $markingSet->setMarkingFilterFunction($filterFunction);
        }

        // Mark tree from this node
        $markingSet->markPartialTree();

        // Ensure current page is exposed
        $currentPage = $this->currentPage();
        if ($currentPage) {
            $markingSet->markToExpose($currentPage);
        }

        // Pre-cache permissions
        $checker = SiteTree::getPermissionChecker();
        if ($checker instanceof InheritedPermissions) {
            $checker->prePopulatePermissionCache(
                InheritedPermissions::EDIT,
                $markingSet->markedNodeIDs()
            );
        }

        // Render using full-subtree template
        return $markingSet->renderChildren(
            [ self::class . '_SubTree', 'type' => 'Includes' ],
            $this->getTreeNodeCustomisations()
        );
    }


    /**
     * Get callback to determine template customisations for nodes
     *
     * @return callable
     */
    protected function getTreeNodeCustomisations()
    {
        $rootTitle = $this->getCMSTreeTitle();
        return function (SiteTree $node) use ($rootTitle) {
            return [
                'listViewLink' => $this->LinkListViewChildren($node->ID),
                'rootTitle' => $rootTitle,
                'extraClass' => $this->getTreeNodeClasses($node),
                'Title' => _t(
                    self::class . '.PAGETYPE_TITLE',
                    '(Page type: {type}) {title}',
                    [
                        'type' => $node->i18n_singular_name(),
                        'title' => $node->Title,
                    ]
                )
            ];
        };
    }

    /**
     * Get extra CSS classes for a page's tree node
     *
     * @param SiteTree $node
     * @return string
     */
    public function getTreeNodeClasses(SiteTree $node)
    {
        // Get classes from object
        $classes = $node->CMSTreeClasses();

        // Get status flag classes
        $flags = $node->getStatusFlags();
        if ($flags) {
            $statuses = array_keys($flags);
            foreach ($statuses as $s) {
                $classes .= ' status-' . $s;
            }
        }

        // Get additional filter classes
        $filter = $this->getSearchFilter();
        if ($filter && ($filterClasses = $filter->getPageClasses($node))) {
            if (is_array($filterClasses)) {
                $filterClasses = implode(' ', $filterClasses);
            }
            $classes .= ' ' . $filterClasses;
        }

        return trim($classes);
    }

    /**
     * Get a subtree underneath the request param 'ID'.
     * If ID = 0, then get the whole tree.
     *
     * @param HTTPRequest $request
     * @return string
     */
    public function getsubtree($request)
    {
        $html = $this->getSiteTreeFor(
            $this->config()->get('tree_class'),
            $request->getVar('ID'),
            null,
            null,
            null,
            $request->getVar('minNodeCount')
        );

        // Trim off the outer tag
        $html = preg_replace('/^[\s\t\r\n]*<ul[^>]*>/', '', $html);
        $html = preg_replace('/<\/ul[^>]*>[\s\t\r\n]*$/', '', $html);

        return $html;
    }

    /**
     * Allows requesting a view update on specific tree nodes.
     * Similar to {@link getsubtree()}, but doesn't enforce loading
     * all children with the node. Useful to refresh views after
     * state modifications, e.g. saving a form.
     *
     * @param HTTPRequest $request
     * @return HTTPResponse
     */
    public function updatetreenodes($request)
    {
        $data = array();
        $ids = explode(',', $request->getVar('ids'));
        foreach ($ids as $id) {
            if ($id === "") {
                continue; // $id may be a blank string, which is invalid and should be skipped over
            }

            $record = $this->getRecord($id);
            if (!$record) {
                continue; // In case a page is no longer available
            }

            // Create marking set with sole marked root
            $markingSet = MarkedSet::create($record);
            $markingSet->setMarkingFilterFunction(function () {
                return false;
            });
            $markingSet->markUnexpanded($record);

            // Find the next & previous nodes, for proper positioning (Sort isn't good enough - it's not a raw offset)
            // TODO: These methods should really be in hierarchy - for a start it assumes Sort exists
            $prev = null;

            $className = $this->config()->get('tree_class');
            $next = DataObject::get($className)
                ->filter('ParentID', $record->ParentID)
                ->filter('Sort:GreaterThan', $record->Sort)
                ->first();

            if (!$next) {
                $prev = DataObject::get($className)
                    ->filter('ParentID', $record->ParentID)
                    ->filter('Sort:LessThan', $record->Sort)
                    ->reverse()
                    ->first();
            }

            // Render using single node template
            $html = $markingSet->renderChildren(
                [ self::class . '_TreeNode', 'type' => 'Includes'],
                $this->getTreeNodeCustomisations()
            );

            $data[$id] = array(
                'html' => $html,
                'ParentID' => $record->ParentID,
                'NextID' => $next ? $next->ID : null,
                'PrevID' => $prev ? $prev->ID : null
            );
        }
        return $this
            ->getResponse()
            ->addHeader('Content-Type', 'application/json')
            ->setBody(Convert::raw2json($data));
    }

    /**
     * Update the position and parent of a tree node.
     * Only saves the node if changes were made.
     *
     * Required data:
     * - 'ID': The moved node
     * - 'ParentID': New parent relation of the moved node (0 for root)
     * - 'SiblingIDs': Array of all sibling nodes to the moved node (incl. the node itself).
     *   In case of a 'ParentID' change, relates to the new siblings under the new parent.
     *
     * @param HTTPRequest $request
     * @return HTTPResponse JSON string with a
     * @throws HTTPResponse_Exception
     */
    public function savetreenode($request)
    {
        if (!SecurityToken::inst()->checkRequest($request)) {
            return $this->httpError(400);
        }
        if (!Permission::check('SITETREE_REORGANISE') && !Permission::check('ADMIN')) {
            return $this->httpError(
                403,
                _t(
                    __CLASS__.'.CANT_REORGANISE',
                    "You do not have permission to rearange the site tree. Your change was not saved."
                )
            );
        }

        $className = $this->config()->get('tree_class');
        $id = $request->requestVar('ID');
        $parentID = $request->requestVar('ParentID');
        if (!is_numeric($id) || !is_numeric($parentID)) {
            return $this->httpError(400);
        }

        // Check record exists in the DB
        /** @var SiteTree $node */
        $node = DataObject::get_by_id($className, $id);
        if (!$node) {
            return $this->httpError(
                500,
                _t(
                    __CLASS__.'.PLEASESAVE',
                    "Please Save Page: This page could not be updated because it hasn't been saved yet."
                )
            );
        }

        // Check top level permissions
        $root = $node->getParentType();
        if (($parentID == '0' || $root == 'root') && !SiteConfig::current_site_config()->canCreateTopLevel()) {
            return $this->httpError(
                403,
                _t(
                    __CLASS__.'.CANT_REORGANISE',
                    "You do not have permission to alter Top level pages. Your change was not saved."
                )
            );
        }

        $siblingIDs = $request->requestVar('SiblingIDs');
        $statusUpdates = array('modified'=>array());

        if (!$node->canEdit()) {
            return Security::permissionFailure($this);
        }

        // Update hierarchy (only if ParentID changed)
        if ($node->ParentID != $parentID) {
            $node->ParentID = (int)$parentID;
            $node->write();

            $statusUpdates['modified'][$node->ID] = array(
                'TreeTitle' => $node->TreeTitle
            );

            // Update all dependent pages
            $virtualPages = VirtualPage::get()->filter("CopyContentFromID", $node->ID);
            foreach ($virtualPages as $virtualPage) {
                $statusUpdates['modified'][$virtualPage->ID] = array(
                    'TreeTitle' => $virtualPage->TreeTitle()
                );
            }

            $this->getResponse()->addHeader(
                'X-Status',
                rawurlencode(_t(__CLASS__.'.REORGANISATIONSUCCESSFUL', 'Reorganised the site tree successfully.'))
            );
        }

        // Update sorting
        if (is_array($siblingIDs)) {
            $counter = 0;
            foreach ($siblingIDs as $id) {
                if ($id == $node->ID) {
                    $node->Sort = ++$counter;
                    $node->write();
                    $statusUpdates['modified'][$node->ID] = array(
                        'TreeTitle' => $node->TreeTitle
                    );
                } elseif (is_numeric($id)) {
                    // Nodes that weren't "actually moved" shouldn't be registered as
                    // having been edited; do a direct SQL update instead
                    ++$counter;
                    $table = DataObject::getSchema()->baseDataTable($className);
                    DB::prepared_query(
                        "UPDATE \"$table\" SET \"Sort\" = ? WHERE \"ID\" = ?",
                        array($counter, $id)
                    );
                }
            }

            $this->getResponse()->addHeader(
                'X-Status',
                rawurlencode(_t(__CLASS__.'.REORGANISATIONSUCCESSFUL', 'Reorganised the site tree successfully.'))
            );
        }

        return $this
            ->getResponse()
            ->addHeader('Content-Type', 'application/json')
            ->setBody(Convert::raw2json($statusUpdates));
    }

    public function CanOrganiseSitetree()
    {
        return !Permission::check('SITETREE_REORGANISE') && !Permission::check('ADMIN') ? false : true;
    }

    /**
     * @return boolean
     */
    public function TreeIsFiltered()
    {
        $query = $this->getRequest()->getVar('q');
        return !empty($query);
    }

    public function ExtraTreeTools()
    {
        $html = '';
        $this->extend('updateExtraTreeTools', $html);
        return $html;
    }

    /**
     * Returns a Form for page searching for use in templates.
     *
     * Can be modified from a decorator by a 'updateSearchForm' method
     *
     * @return Form
     */
    public function SearchForm()
    {
        // Create the fields
        $content = new TextField('q[Term]', _t('SilverStripe\\CMS\\Search\\SearchForm.FILTERLABELTEXT', 'Search'));
        $dateFrom = new DateField(
            'q[LastEditedFrom]',
            _t('SilverStripe\\CMS\\Search\\SearchForm.FILTERDATEFROM', 'From')
        );
        $dateTo = new DateField(
            'q[LastEditedTo]',
            _t('SilverStripe\\CMS\\Search\\SearchForm.FILTERDATETO', 'To')
        );
        $pageFilter = new DropdownField(
            'q[FilterClass]',
            _t('SilverStripe\\CMS\\Controllers\\CMSMain.PAGES', 'Page status'),
            CMSSiteTreeFilter::get_all_filters()
        );
        $pageClasses = new DropdownField(
            'q[ClassName]',
            _t('SilverStripe\\CMS\\Controllers\\CMSMain.PAGETYPEOPT', 'Page type', 'Dropdown for limiting search to a page type'),
            $this->getPageTypes()
        );
        $pageClasses->setEmptyString(_t('SilverStripe\\CMS\\Controllers\\CMSMain.PAGETYPEANYOPT', 'Any'));

        // Group the Datefields
        $dateGroup = new FieldGroup(
            $dateFrom,
            $dateTo
        );
        $dateGroup->setTitle(_t('SilverStripe\\CMS\\Search\\SearchForm.PAGEFILTERDATEHEADING', 'Last edited'));

        // Create the Field list
        $fields = new FieldList(
            $content,
            $pageFilter,
            $pageClasses,
            $dateGroup
        );

        // Create the Search and Reset action
        $actions = new FieldList(
            FormAction::create('doSearch', _t('SilverStripe\\CMS\\Controllers\\CMSMain.APPLY_FILTER', 'Search'))
                ->addExtraClass('btn btn-primary'),
            FormAction::create('clear', _t('SilverStripe\\CMS\\Controllers\\CMSMain.CLEAR_FILTER', 'Clear'))
                ->setAttribute('type', 'reset')
                ->addExtraClass('btn btn-secondary')
        );

        // Use <button> to allow full jQuery UI styling on the all of the Actions
        /** @var FormAction $action */
        foreach ($actions->dataFields() as $action) {
            /** @var FormAction $action */
            $action->setUseButtonTag(true);
        }

        // Create the form
        /** @skipUpgrade */
        $form = Form::create($this, 'SearchForm', $fields, $actions)
            ->addExtraClass('cms-search-form')
            ->setFormMethod('GET')
            ->setFormAction(CMSMain::singleton()->Link())
            ->disableSecurityToken()
            ->unsetValidator();

        // Load the form with previously sent search data
        $form->loadDataFrom($this->getRequest()->getVars());

        // Allow decorators to modify the form
        $this->extend('updateSearchForm', $form);

        return $form;
    }

    /**
     * Returns a sorted array suitable for a dropdown with pagetypes and their translated name
     *
     * @return array
     */
    protected function getPageTypes()
    {
        $pageTypes = array();
        foreach (SiteTree::page_type_classes() as $pageTypeClass) {
            $pageTypes[$pageTypeClass] = SiteTree::singleton($pageTypeClass)->i18n_singular_name();
        }
        asort($pageTypes);
        return $pageTypes;
    }

    public function doSearch($data, $form)
    {
        return $this->getsubtree($this->getRequest());
    }

    /**
     * Get "back" url for breadcrumbs
     *
     * @return string
     */
    public function getBreadcrumbsBackLink()
    {
        $breadcrumbs = $this->Breadcrumbs();
        if ($breadcrumbs->count() < 2) {
            return $this->LinkPages();
        }
        // Get second from end breadcrumb
        return $breadcrumbs
            ->offsetGet($breadcrumbs->count() - 2)
            ->Link;
    }

    /**
     * @param bool $unlinked
     * @return ArrayList
     */
    public function Breadcrumbs($unlinked = false)
    {
        $items = new ArrayList();

        // Check if we are editing a page
        /** @var SiteTree $record */
        $record = $this->currentPage();
        if (!$record) {
            $items->push(new ArrayData(array(
                'Title' => CMSPagesController::menu_title(),
                'Link' => ($unlinked) ? false : $this->LinkPages()
            )));

            $this->extend('updateBreadcrumbs', $items);

            return $items;
        }

        // Add all ancestors
        $ancestors = $record->getAncestors();
        $ancestors = new ArrayList(array_reverse($ancestors->toArray()));
        $ancestors->push($record);
        /** @var SiteTree $ancestor */
        foreach ($ancestors as $ancestor) {
            $items->push(new ArrayData(array(
                'Title' => $ancestor->getMenuTitle(),
                'Link' => ($unlinked)
                    ? false
                    : $ancestor->CMSEditLink()
            )));
        }

        $this->extend('updateBreadcrumbs', $items);

        return $items;
    }

    /**
     * Create serialized JSON string with site tree hints data to be injected into
     * 'data-hints' attribute of root node of jsTree.
     *
     * @return string Serialized JSON
     */
    public function SiteTreeHints()
    {
        $classes = SiteTree::page_type_classes();
        $memberID = Security::getCurrentUser() ? Security::getCurrentUser()->ID : 0;
        $cache = $this->getHintsCache();
        $cacheKey = $this->generateHintsCacheKey($memberID);
        $json = $cache->get($cacheKey);

        if ($json) {
            return $json;
        }

        $canCreate = [];
        foreach ($classes as $class) {
            $canCreate[$class] = singleton($class)->canCreate();
        }

        $def['Root'] = [];
        $def['Root']['disallowedChildren'] = [];

        // Contains all possible classes to support UI controls listing them all,
        // such as the "add page here" context menu.
        $def['All'] = [];

        // Identify disallows and set globals
        foreach ($classes as $class) {
            $obj = singleton($class);
            if ($obj instanceof HiddenClass) {
                continue;
            }

            // Name item
            $def['All'][$class] = [
                'title' => $obj->i18n_singular_name()
            ];

            // Check if can be created at the root
            $needsPerm = $obj->config()->get('need_permission');
            if (!$obj->config()->get('can_be_root')
                || (!array_key_exists($class, $canCreate) || !$canCreate[$class])
                || ($needsPerm && !$this->can($needsPerm))
            ) {
                $def['Root']['disallowedChildren'][] = $class;
            }

            // Hint data specific to the class
            $def[$class] = [];

            $defaultChild = $obj->defaultChild();
            if ($defaultChild !== 'Page' && $defaultChild !== null) {
                $def[$class]['defaultChild'] = $defaultChild;
            }

            $defaultParent = $obj->defaultParent();
            if ($defaultParent !== 1 && $defaultParent !== null) {
                $def[$class]['defaultParent'] = $defaultParent;
            }
        }

        $this->extend('updateSiteTreeHints', $def);

        $json = Convert::raw2json($def);
        $cache->set($cacheKey, $json);

        return $json;
    }

    /**
     * Populates an array of classes in the CMS
     * which allows the user to change the page type.
     *
     * @return SS_List
     */
    public function PageTypes()
    {
        $classes = SiteTree::page_type_classes();

        $result = new ArrayList();

        foreach ($classes as $class) {
            $instance = SiteTree::singleton($class);
            if ($instance instanceof HiddenClass) {
                continue;
            }

            // skip this type if it is restricted
            $needPermissions = $instance->config()->get('need_permission');
            if ($needPermissions && !$this->can($needPermissions)) {
                continue;
            }

            $result->push(new ArrayData(array(
                'ClassName' => $class,
                'AddAction' => $instance->i18n_singular_name(),
                'Description' => $instance->i18n_classDescription(),
                'IconURL' => $instance->getPageIconURL(),
                'Title' => $instance->i18n_singular_name(),
            )));
        }

        $result = $result->sort('AddAction');

        return $result;
    }

    /**
     * Get a database record to be managed by the CMS.
     *
     * @param int $id Record ID
     * @param int $versionID optional Version id of the given record
     * @return SiteTree
     */
    public function getRecord($id, $versionID = null)
    {
        if (!$id) {
            return null;
        }
        $treeClass = $this->config()->get('tree_class');
        if ($id instanceof $treeClass) {
            return $id;
        }
        if (substr($id, 0, 3) == 'new') {
            return $this->getNewItem($id);
        }
        if (!is_numeric($id)) {
            return null;
        }

        $currentStage = Versioned::get_reading_mode();

        if ($this->getRequest()->getVar('Version')) {
            $versionID = (int) $this->getRequest()->getVar('Version');
        }

        /** @var SiteTree $record */
        if ($versionID) {
            $record = Versioned::get_version($treeClass, $id, $versionID);
        } else {
            $record = DataObject::get_by_id($treeClass, $id);
        }

        // Then, try getting a record from the live site
        if (!$record) {
            // $record = Versioned::get_one_by_stage($treeClass, "Live", "\"$treeClass\".\"ID\" = $id");
            Versioned::set_stage(Versioned::LIVE);
            singleton($treeClass)->flushCache();

            $record = DataObject::get_by_id($treeClass, $id);
        }

        // Then, try getting a deleted record
        if (!$record) {
            $record = Versioned::get_latest_version($treeClass, $id);
        }

        // Set the reading mode back to what it was.
        Versioned::set_reading_mode($currentStage);

        return $record;
    }

    /**
     * {@inheritdoc}
     *
     * @param HTTPRequest $request
     * @return Form
     */
    public function EditForm($request = null)
    {
        // set page ID from request
        if ($request) {
            // Validate id is present
            $id = $request->param('ID');
            if (!isset($id)) {
                $this->httpError(400);
                return null;
            }
            $this->setCurrentPageID($id);
        }
        return $this->getEditForm();
    }

    /**
     * @param int $id
     * @param FieldList $fields
     * @return Form
     */
    public function getEditForm($id = null, $fields = null)
    {
        // Get record
        if (!$id) {
            $id = $this->currentPageID();
        }
        /** @var SiteTree $record */
        $record = $this->getRecord($id);

        // Check parent form can be generated
        $form = parent::getEditForm($record, $fields);
        if (!$form || !$record) {
            return $form;
        }

        if (!$fields) {
            $fields = $form->Fields();
        }

        // Add extra fields
        $deletedFromStage = !$record->isOnDraft();
        $fields->push($idField = new HiddenField("ID", false, $id));
        // Necessary for different subsites
        $fields->push($liveLinkField = new HiddenField("AbsoluteLink", false, $record->AbsoluteLink()));
        $fields->push($liveLinkField = new HiddenField("LiveLink"));
        $fields->push($stageLinkField = new HiddenField("StageLink"));
        $fields->push($archiveWarningMsgField = new HiddenField("ArchiveWarningMessage"));
        $fields->push(new HiddenField("TreeTitle", false, $record->getTreeTitle()));

        $archiveWarningMsgField->setValue($this->getArchiveWarningMessage($record));

        // Build preview / live links
        $liveLink = $record->getAbsoluteLiveLink();
        if ($liveLink) {
            $liveLinkField->setValue($liveLink);
        }
        if (!$deletedFromStage) {
            $stageLink = Controller::join_links($record->AbsoluteLink(), '?stage=Stage');
            if ($stageLink) {
                $stageLinkField->setValue($stageLink);
            }
        }

        // Added in-line to the form, but plucked into different view by LeftAndMain.Preview.js upon load
        /** @skipUpgrade */
        if ($record instanceof CMSPreviewable && !$fields->fieldByName('SilverStripeNavigator')) {
            $navField = new LiteralField('SilverStripeNavigator', $this->getSilverStripeNavigator());
            $navField->setAllowHTML(true);
            $fields->push($navField);
        }

        // getAllCMSActions can be used to completely redefine the action list
        if ($record->hasMethod('getAllCMSActions')) {
            $actions = $record->getAllCMSActions();
        } else {
            $actions = $record->getCMSActions();

            // Find and remove action menus that have no actions.
            if ($actions && $actions->count()) {
                /** @var TabSet $tabset */
                $tabset = $actions->fieldByName('ActionMenus');
                if ($tabset) {
                    /** @var Tab $tab */
                    foreach ($tabset->getChildren() as $tab) {
                        if (!$tab->getChildren()->count()) {
                            $tabset->removeByName($tab->getName());
                        }
                    }
                }
            }
        }

        // Use <button> to allow full jQuery UI styling
        $actionsFlattened = $actions->dataFields();
        if ($actionsFlattened) {
            /** @var FormAction $action */
            foreach ($actionsFlattened as $action) {
                $action->setUseButtonTag(true);
            }
        }

        // TODO Can't merge $FormAttributes in template at the moment
        $form->addExtraClass('center ' . $this->BaseCSSClasses());
        // Set validation exemptions for specific actions
        $form->setValidationExemptActions(array(
            'restore',
            'revert',
            'deletefromlive',
            'delete',
            'unpublish',
            'rollback',
            'doRollback',
            'archive',
        ));

        // Announce the capability so the frontend can decide whether to allow preview or not.
        if ($record instanceof CMSPreviewable) {
            $form->addExtraClass('cms-previewable');
        }
        $form->addExtraClass('fill-height flexbox-area-grow');

        if (!$record->canEdit() || $deletedFromStage) {
            $readonlyFields = $form->Fields()->makeReadonly();
            $form->setFields($readonlyFields);
        }

        $form->Fields()->setForm($form);

        $this->extend('updateEditForm', $form);

        // Use custom reqest handler for LeftAndMain requests;
        // CMS Forms cannot be identified solely by name, but also need ID (and sometimes OtherID)
        $form->setRequestHandler(
            LeftAndMainFormRequestHandler::create($form, [$id])
        );
        return $form;
    }

    public function EmptyForm()
    {
        $fields = new FieldList(
            new LabelField('PageDoesntExistLabel', _t('SilverStripe\\CMS\\Controllers\\CMSMain.PAGENOTEXISTS', "This page doesn't exist"))
        );
        $form = parent::EmptyForm();
        $form->setFields($fields);
        $fields->setForm($form);
        return $form;
    }

    /**
     * Build an archive warning message based on the page's children
     *
     * @param SiteTree $record
     * @return string
     */
    protected function getArchiveWarningMessage($record)
    {
        // Get all page's descendants
        $record->collateDescendants(true, $descendants);
        if (!$descendants) {
            $descendants = [];
        }

        // Get all campaigns that the page and its descendants belong to
        $inChangeSetIDs = ChangeSetItem::get_for_object($record)->column('ChangeSetID');

        foreach ($descendants as $page) {
            $inChangeSetIDs = array_merge($inChangeSetIDs, ChangeSetItem::get_for_object($page)->column('ChangeSetID'));
        }

        if (count($inChangeSetIDs) > 0) {
            $inChangeSets = ChangeSet::get()->filter(['ID' => $inChangeSetIDs, 'State' => ChangeSet::STATE_OPEN]);
        } else {
            $inChangeSets = new ArrayList();
        }

        $numCampaigns = ChangeSet::singleton()->i18n_pluralise($inChangeSets->count());
        $numCampaigns = mb_strtolower($numCampaigns);

        if (count($descendants) > 0 && $inChangeSets->count() > 0) {
            $archiveWarningMsg = _t('SilverStripe\\CMS\\Controllers\\CMSMain.ArchiveWarningWithChildrenAndCampaigns', 'Warning: This page and all of its child pages will be unpublished and automatically removed from their associated {NumCampaigns} before being sent to the archive.\n\nAre you sure you want to proceed?', [ 'NumCampaigns' => $numCampaigns ]);
        } elseif (count($descendants) > 0) {
            $archiveWarningMsg = _t('SilverStripe\\CMS\\Controllers\\CMSMain.ArchiveWarningWithChildren', 'Warning: This page and all of its child pages will be unpublished before being sent to the archive.\n\nAre you sure you want to proceed?');
        } elseif ($inChangeSets->count() > 0) {
            $archiveWarningMsg = _t('SilverStripe\\CMS\\Controllers\\CMSMain.ArchiveWarningWithCampaigns', 'Warning: This page will be unpublished and automatically removed from their associated {NumCampaigns} before being sent to the archive.\n\nAre you sure you want to proceed?', [ 'NumCampaigns' => $numCampaigns ]);
        } else {
            $archiveWarningMsg = _t('SilverStripe\\CMS\\Controllers\\CMSMain.ArchiveWarning', 'Warning: This page will be unpublished before being sent to the archive.\n\nAre you sure you want to proceed?');
        }

        return $archiveWarningMsg;
    }

    /**
     * This method exclusively handles deferred ajax requests to render the
     * pages tree deferred handler (no pjax-fragment)
     *
     * @return DBHTMLText HTML response with the rendered treeview
     */
    public function treeview()
    {
        return $this->renderWith($this->getTemplatesWithSuffix('_TreeView'));
    }

    /**
     * Returns deferred listview for the current level
     *
     * @return DBHTMLText HTML response with the rendered listview
     */
    public function listview()
    {
        return $this->renderWith($this->getTemplatesWithSuffix('_ListView'));
    }

    /**
     * Get view state based on the current action
     *
     * @param string $default
     * @return string
     */
    public function ViewState($default = 'treeview')
    {
        $mode = $this->getRequest()->param('Action');
        switch ($mode) {
            case 'listview':
            case 'treeview':
                return $mode;
            default:
                return $default;
        }
    }

    /**
     * Callback to request the list of page types allowed under a given page instance.
     * Provides a slower but more precise response over SiteTreeHints
     *
     * @param HTTPRequest $request
     * @return HTTPResponse
     */
    public function childfilter($request)
    {
        // Check valid parent specified
        $parentID = $request->requestVar('ParentID');
        $parent = SiteTree::get()->byID($parentID);
        if (!$parent || !$parent->exists()) {
            return $this->httpError(404);
        }

        // Build hints specific to this class
        // Identify disallows and set globals
        $classes = SiteTree::page_type_classes();
        $disallowedChildren = array();
        foreach ($classes as $class) {
            $obj = singleton($class);
            if ($obj instanceof HiddenClass) {
                continue;
            }

            if (!$obj->canCreate(null, array('Parent' => $parent))) {
                $disallowedChildren[] = $class;
            }
        }

        $this->extend('updateChildFilter', $disallowedChildren, $parentID);
        return $this
            ->getResponse()
            ->addHeader('Content-Type', 'application/json; charset=utf-8')
            ->setBody(Convert::raw2json($disallowedChildren));
    }

    /**
     * Safely reconstruct a selected filter from a given set of query parameters
     *
     * @param array $params Query parameters to use
     * @return CMSSiteTreeFilter The filter class, or null if none present
     * @throws InvalidArgumentException if invalid filter class is passed.
     */
    protected function getQueryFilter($params)
    {
        if (empty($params['FilterClass'])) {
            return null;
        }
        $filterClass = $params['FilterClass'];
        if (!is_subclass_of($filterClass, CMSSiteTreeFilter::class)) {
            throw new InvalidArgumentException("Invalid filter class passed: {$filterClass}");
        }
        return $filterClass::create($params);
    }

    /**
     * Returns the pages meet a certain criteria as {@see CMSSiteTreeFilter} or the subpages of a parent page
     * defaulting to no filter and show all pages in first level.
     * Doubles as search results, if any search parameters are set through {@link SearchForm()}.
     *
     * @param array $params Search filter criteria
     * @param int $parentID Optional parent node to filter on (can't be combined with other search criteria)
     * @return SS_List
     * @throws InvalidArgumentException if invalid filter class is passed.
     */
    public function getList($params = array(), $parentID = 0)
    {
        if ($filter = $this->getQueryFilter($params)) {
            return $filter->getFilteredPages();
        } else {
            $list = DataList::create($this->config()->get('tree_class'));
            $parentID = is_numeric($parentID) ? $parentID : 0;
            return $list->filter("ParentID", $parentID);
        }
    }

    /**
     * @return Form
     */
    public function ListViewForm()
    {
        $params = $this->getRequest()->requestVar('q');
        $parentID = $this->getRequest()->requestVar('ParentID');
        $list = $this->getList($params, $parentID);
        $gridFieldConfig = GridFieldConfig::create()->addComponents(
            new GridFieldSortableHeader(),
            new GridFieldDataColumns(),
            new GridFieldPaginator($this->config()->get('page_length'))
        );
        if ($parentID) {
            $linkSpec = $this->LinkListViewChildren('%d');
            $gridFieldConfig->addComponent(
                GridFieldLevelup::create($parentID)
                    ->setLinkSpec($linkSpec)
                    ->setAttributes(array('data-pjax-target' => 'ListViewForm,Breadcrumbs'))
            );
            $this->setCurrentPageID($parentID);
        }
        $gridField = new GridField('Page', 'Pages', $list, $gridFieldConfig);
        $gridField->setAttribute('cms-loading-ignore-url-params', true);
        /** @var GridFieldDataColumns $columns */
        $columns = $gridField->getConfig()->getComponentByType(GridFieldDataColumns::class);

        // Don't allow navigating into children nodes on filtered lists
        $fields = array(
            'getTreeTitle' => _t('SilverStripe\\CMS\\Model\\SiteTree.PAGETITLE', 'Page Title'),
            'singular_name' => _t('SilverStripe\\CMS\\Model\\SiteTree.PAGETYPE', 'Page Type'),
            'LastEdited' => _t('SilverStripe\\CMS\\Model\\SiteTree.LASTUPDATED', 'Last Updated'),
        );
        /** @var GridFieldSortableHeader $sortableHeader */
        $sortableHeader = $gridField->getConfig()->getComponentByType(GridFieldSortableHeader::class);
        $sortableHeader->setFieldSorting(array('getTreeTitle' => 'Title'));
        $gridField->getState()->ParentID = $parentID;

        if (!$params) {
            $fields = array_merge(array('listChildrenLink' => ''), $fields);
        }

        $columns->setDisplayFields($fields);
        $columns->setFieldCasting(array(
            'Created' => 'DBDatetime->Ago',
            'LastEdited' => 'DBDatetime->FormatFromSettings',
            'getTreeTitle' => 'HTMLFragment'
        ));

        $controller = $this;
        $columns->setFieldFormatting(array(
            'listChildrenLink' => function ($value, &$item) use ($controller) {
                /** @var SiteTree $item */
                $num = $item ? $item->numChildren() : null;
                if ($num) {
                    return sprintf(
                        '<a class="btn btn-secondary btn--no-text btn--icon-large font-icon-right-dir cms-panel-link list-children-link" data-pjax-target="ListViewForm,Breadcrumbs" href="%s"><span class="sr-only">%s child pages</span></a>',
                        $this->LinkListViewChildren((int)$item->ID),
                        $num
                    );
                }
            },
            'getTreeTitle' => function ($value, &$item) use ($controller) {
                $title = sprintf(
                    '<a class="action-detail" href="%s">%s</a>',
                    Controller::join_links(
                        CMSPageEditController::singleton()->Link('show'),
                        (int)$item->ID
                    ),
                    $item->TreeTitle // returns HTML, does its own escaping
                );
                $breadcrumbs = $item->Breadcrumbs(20, true, false, true, '/');
                // Remove item's tile
                $breadcrumbs = preg_replace('/[^\/]+$/', '', trim($breadcrumbs));
                // Trim spaces around delimiters
                $breadcrumbs = preg_replace('/\s?\/\s?/', '/', trim($breadcrumbs));
                return $title . sprintf('<p class="small cms-list__item-breadcrumbs">%s</p>', $breadcrumbs);
            }
        ));

        $negotiator = $this->getResponseNegotiator();
        $listview = Form::create(
            $this,
            'ListViewForm',
            new FieldList($gridField),
            new FieldList()
        )->setHTMLID('Form_ListViewForm');
        $listview->setAttribute('data-pjax-fragment', 'ListViewForm');
        $listview->setValidationResponseCallback(function (ValidationResult $errors) use ($negotiator, $listview) {
            $request = $this->getRequest();
            if ($request->isAjax() && $negotiator) {
                $result = $listview->forTemplate();
                return $negotiator->respond($request, array(
                    'CurrentForm' => function () use ($result) {
                        return $result;
                    }
                ));
            }
        });

        $this->extend('updateListView', $listview);

        $listview->disableSecurityToken();
        return $listview;
    }

    public function currentPageID()
    {
        $id = parent::currentPageID();

        $this->extend('updateCurrentPageID', $id);

        return $id;
    }

    //------------------------------------------------------------------------------------------//
    // Data saving handlers

    /**
     * Save and Publish page handler
     *
     * @param array $data
     * @param Form $form
     * @return HTTPResponse
     * @throws HTTPResponse_Exception
     */
    public function save($data, $form)
    {
        $className = $this->config()->get('tree_class');

        // Existing or new record?
        $id = $data['ID'];
        if (substr($id, 0, 3) != 'new') {
            /** @var SiteTree $record */
            $record = DataObject::get_by_id($className, $id);
            // Check edit permissions
            if ($record && !$record->canEdit()) {
                return Security::permissionFailure($this);
            }
            if (!$record || !$record->ID) {
                throw new HTTPResponse_Exception("Bad record ID #$id", 404);
            }
        } else {
            if (!$className::singleton()->canCreate()) {
                return Security::permissionFailure($this);
            }
            $record = $this->getNewItem($id, false);
        }

        // Check publishing permissions
        $doPublish = !empty($data['publish']);
        if ($record && $doPublish && !$record->canPublish()) {
            return Security::permissionFailure($this);
        }

        // TODO Coupling to SiteTree
        $record->HasBrokenLink = 0;
        $record->HasBrokenFile = 0;

        if (!$record->ObsoleteClassName) {
            $record->writeWithoutVersion();
        }

        // Update the class instance if necessary
        if (isset($data['ClassName']) && $data['ClassName'] != $record->ClassName) {
            // Replace $record with a new instance of the new class
            $newClassName = $data['ClassName'];
            $record = $record->newClassInstance($newClassName);
        }

        // save form data into record
        $form->saveInto($record);
        $record->write();

        // If the 'Publish' button was clicked, also publish the page
        if ($doPublish) {
            $record->publishRecursive();
            $message = _t(
                __CLASS__ . '.PUBLISHED',
                "Published '{title}' successfully.",
                ['title' => $record->Title]
            );
        } else {
            $message = _t(
                __CLASS__ . '.SAVED',
                "Saved '{title}' successfully.",
                ['title' => $record->Title]
            );
        }

        $this->getResponse()->addHeader('X-Status', rawurlencode($message));
        return $this->getResponseNegotiator()->respond($this->getRequest());
    }

    /**
     * @uses LeftAndMainExtension->augmentNewSiteTreeItem()
     *
     * @param int|string $id
     * @param bool $setID
     * @return mixed|DataObject
     * @throws HTTPResponse_Exception
     */
    public function getNewItem($id, $setID = true)
    {
        $parentClass = $this->config()->get('tree_class');
        list(, $className, $parentID) = array_pad(explode('-', $id), 3, null);

        if (!is_a($className, $parentClass, true)) {
            $response = Security::permissionFailure($this);
            if (!$response) {
                $response = $this->getResponse();
            }
            throw new HTTPResponse_Exception($response);
        }

        /** @var SiteTree $newItem */
        $newItem = Injector::inst()->create($className);
        $newItem->Title = _t(
            __CLASS__ . '.NEWPAGE',
            "New {pagetype}",
            'followed by a page type title',
            array('pagetype' => singleton($className)->i18n_singular_name())
        );
        $newItem->ClassName = $className;
        $newItem->ParentID = $parentID;

        // DataObject::fieldExists only checks the current class, not the hierarchy
        // This allows the CMS to set the correct sort value
        if ($newItem->castingHelper('Sort')) {
            $maxSort = DB::prepared_query(
                'SELECT MAX("Sort") FROM "SiteTree" WHERE "ParentID" = ?',
                array($parentID)
            )->value();
            $newItem->Sort = (int)$maxSort + 1;
        }

        if ($setID && $id) {
            $newItem->ID = $id;
        }

        # Some modules like subsites add extra fields that need to be set when the new item is created
        $this->extend('augmentNewSiteTreeItem', $newItem);

        return $newItem;
    }

    /**
     * Actually perform the publication step
     *
     * @param Versioned|DataObject $record
     * @return mixed
     */
    public function performPublish($record)
    {
        if ($record && !$record->canPublish()) {
            return Security::permissionFailure($this);
        }

        $record->publishRecursive();
    }

    /**
     * Reverts a page by publishing it to live.
     * Use {@link restorepage()} if you want to restore a page
     * which was deleted from draft without publishing.
     *
     * @uses SiteTree->doRevertToLive()
     *
     * @param array $data
     * @param Form $form
     * @return HTTPResponse
     * @throws HTTPResponse_Exception
     */
    public function revert($data, $form)
    {
        if (!isset($data['ID'])) {
            throw new HTTPResponse_Exception("Please pass an ID in the form content", 400);
        }

        $id = (int) $data['ID'];
        $restoredPage = Versioned::get_latest_version(SiteTree::class, $id);
        if (!$restoredPage) {
            throw new HTTPResponse_Exception("SiteTree #$id not found", 400);
        }

        /** @var SiteTree $record */
        $record = Versioned::get_one_by_stage(SiteTree::class, Versioned::LIVE, array(
            '"SiteTree_Live"."ID"' => $id
        ));

        // a user can restore a page without publication rights, as it just adds a new draft state
        // (this action should just be available when page has been "deleted from draft")
        if ($record && !$record->canEdit()) {
            return Security::permissionFailure($this);
        }
        if (!$record || !$record->ID) {
            throw new HTTPResponse_Exception("Bad record ID #$id", 404);
        }

        $record->doRevertToLive();

        $this->getResponse()->addHeader(
            'X-Status',
            rawurlencode(_t(
                __CLASS__ . '.RESTORED',
                "Restored '{title}' successfully",
                'Param {title} is a title',
                array('title' => $record->Title)
            ))
        );

        return $this->getResponseNegotiator()->respond($this->getRequest());
    }

    /**
     * Delete the current page from draft stage.
     *
     * @see deletefromlive()
     *
     * @param array $data
     * @param Form $form
     * @return HTTPResponse
     * @throws HTTPResponse_Exception
     */
    public function delete($data, $form)
    {
        $id = $data['ID'];
        $record = SiteTree::get()->byID($id);
        if ($record && !$record->canDelete()) {
            return Security::permissionFailure();
        }
        if (!$record || !$record->ID) {
            throw new HTTPResponse_Exception("Bad record ID #$id", 404);
        }

        // Delete record
        $record->delete();

        $this->getResponse()->addHeader(
            'X-Status',
            rawurlencode(_t(
                __CLASS__ . '.REMOVEDPAGEFROMDRAFT',
                "Removed '{title}' from the draft site",
                ['title' => $record->Title]
            ))
        );

        // Even if the record has been deleted from stage and live, it can be viewed in "archive mode"
        return $this->getResponseNegotiator()->respond($this->getRequest());
    }

    /**
     * Delete this page from both live and stage
     *
     * @param array $data
     * @param Form $form
     * @return HTTPResponse
     * @throws HTTPResponse_Exception
     */
    public function archive($data, $form)
    {
        $id = $data['ID'];
        /** @var SiteTree $record */
        $record = SiteTree::get()->byID($id);
        if (!$record || !$record->exists()) {
            throw new HTTPResponse_Exception("Bad record ID #$id", 404);
        }
        if (!$record->canArchive()) {
            return Security::permissionFailure();
        }

        // Archive record
        $record->doArchive();

        $this->getResponse()->addHeader(
            'X-Status',
            rawurlencode(_t(
                __CLASS__ . '.ARCHIVEDPAGE',
                "Archived page '{title}'",
                ['title' => $record->Title]
            ))
        );

        // Even if the record has been deleted from stage and live, it can be viewed in "archive mode"
        return $this->getResponseNegotiator()->respond($this->getRequest());
    }

    public function publish($data, $form)
    {
        $data['publish'] = '1';

        return $this->save($data, $form);
    }

    public function unpublish($data, $form)
    {
        $className = $this->config()->get('tree_class');
        /** @var SiteTree $record */
        $record = DataObject::get_by_id($className, $data['ID']);

        if ($record && !$record->canUnpublish()) {
            return Security::permissionFailure($this);
        }
        if (!$record || !$record->ID) {
            throw new HTTPResponse_Exception("Bad record ID #" . (int)$data['ID'], 404);
        }

        $record->doUnpublish();

        $this->getResponse()->addHeader(
            'X-Status',
            rawurlencode(_t(
                __CLASS__ . '.REMOVEDPAGE',
                "Removed '{title}' from the published site",
                ['title' => $record->Title]
            ))
        );

        return $this->getResponseNegotiator()->respond($this->getRequest());
    }

    /**
     * @return HTTPResponse
     */
    public function rollback()
    {
        return $this->doRollback(array(
            'ID' => $this->currentPageID(),
            'Version' => $this->getRequest()->param('VersionID')
        ), null);
    }

    /**
     * Rolls a site back to a given version ID
     *
     * @param array $data
     * @param Form $form
     * @return HTTPResponse
     */
    public function doRollback($data, $form)
    {
        $this->extend('onBeforeRollback', $data['ID'], $data['Version']);

        $id = (isset($data['ID'])) ? (int) $data['ID'] : null;
        $version = (isset($data['Version'])) ? (int) $data['Version'] : null;

        /** @var DataObject|Versioned $record */
        $record = Versioned::get_latest_version($this->config()->get('tree_class'), $id);
        if ($record && !$record->canEdit()) {
            return Security::permissionFailure($this);
        }

        if ($version) {
            $record->doRollbackTo($version);
            $message = _t(
                __CLASS__ . '.ROLLEDBACKVERSIONv2',
                "Rolled back to version #{version}.",
                array('version' => $data['Version'])
            );
        } else {
            $record->doRevertToLive();
            $message = _t(
                __CLASS__ . '.ROLLEDBACKPUBv2',
                "Rolled back to published version."
            );
        }

        $this->getResponse()->addHeader('X-Status', rawurlencode($message));

        // Can be used in different contexts: In normal page edit view, in which case the redirect won't have any effect.
        // Or in history view, in which case a revert causes the CMS to re-load the edit view.
        // The X-Pjax header forces a "full" content refresh on redirect.
        $url = Controller::join_links(CMSPageEditController::singleton()->Link('show'), $record->ID);
        $this->getResponse()->addHeader('X-ControllerURL', $url);
        $this->getRequest()->addHeader('X-Pjax', 'Content');
        $this->getResponse()->addHeader('X-Pjax', 'Content');

        return $this->getResponseNegotiator()->respond($this->getRequest());
    }

    /**
     * Batch Actions Handler
     */
    public function batchactions()
    {
        return new CMSBatchActionHandler($this, 'batchactions');
    }

    public function BatchActionParameters()
    {
        $batchActions = CMSBatchActionHandler::config()->batch_actions;

        $forms = array();
        foreach ($batchActions as $urlSegment => $batchAction) {
            $SNG_action = singleton($batchAction);
            if ($SNG_action->canView() && $fieldset = $SNG_action->getParameterFields()) {
                $formHtml = '';
                /** @var FormField $field */
                foreach ($fieldset as $field) {
                    $formHtml .= $field->Field();
                }
                $forms[$urlSegment] = $formHtml;
            }
        }
        $pageHtml = '';
        foreach ($forms as $urlSegment => $html) {
            $pageHtml .= "<div class=\"params\" id=\"BatchActionParameters_$urlSegment\">$html</div>\n\n";
        }
        return new LiteralField("BatchActionParameters", '<div id="BatchActionParameters" style="display:none">'.$pageHtml.'</div>');
    }
    /**
     * Returns a list of batch actions
     */
    public function BatchActionList()
    {
        return $this->batchactions()->batchActionList();
    }

    public function publishall($request)
    {
        if (!Permission::check('ADMIN')) {
            return Security::permissionFailure($this);
        }

        Environment::increaseTimeLimitTo();
        Environment::increaseMemoryLimitTo();

        $response = "";

        if (isset($this->requestParams['confirm'])) {
            // Protect against CSRF on destructive action
            if (!SecurityToken::inst()->checkRequest($request)) {
                return $this->httpError(400);
            }

            $start = 0;
            $pages = SiteTree::get()->limit("$start,30");
            $count = 0;
            while ($pages) {
                /** @var SiteTree $page */
                foreach ($pages as $page) {
                    if ($page && !$page->canPublish()) {
                        return Security::permissionFailure($this);
                    }

                    $page->publishRecursive();
                    $page->destroy();
                    unset($page);
                    $count++;
                    $response .= "<li>$count</li>";
                }
                if ($pages->count() > 29) {
                    $start += 30;
                    $pages = SiteTree::get()->limit("$start,30");
                } else {
                    break;
                }
            }
            $response .= _t(__CLASS__ . '.PUBPAGES', "Done: Published {count} pages", array('count' => $count));
        } else {
            $token = SecurityToken::inst();
            $fields = new FieldList();
            $token->updateFieldSet($fields);
            $tokenField = $fields->first();
            $tokenHtml = ($tokenField) ? $tokenField->FieldHolder() : '';
            $publishAllDescription = _t(
                __CLASS__ . '.PUBALLFUN2',
                'Pressing this button will do the equivalent of going to every page and pressing "publish".  '
                . 'It\'s intended to be used after there have been massive edits of the content, such as when '
                . 'the site was first built.'
            );
            $response .= '<h1>' . _t(__CLASS__ . '.PUBALLFUN', '"Publish All" functionality') . '</h1>
				<p>' . $publishAllDescription . '</p>
				<form method="post" action="publishall">
					<input type="submit" name="confirm" value="'
                    . _t(__CLASS__ . '.PUBALLCONFIRM', "Please publish every page in the site, copying content stage to live", 'Confirmation button') .'" />'
                    . $tokenHtml .
                '</form>';
        }

        return $response;
    }

    /**
     * Restore a completely deleted page from the SiteTree_versions table.
     *
     * @param array $data
     * @param Form $form
     * @return HTTPResponse
     */
    public function restore($data, $form)
    {
        if (!isset($data['ID']) || !is_numeric($data['ID'])) {
            return new HTTPResponse("Please pass an ID in the form content", 400);
        }

        $id = (int)$data['ID'];
        /** @var SiteTree $restoredPage */
        $restoredPage = Versioned::get_latest_version(SiteTree::class, $id);
        if (!$restoredPage) {
            return new HTTPResponse("SiteTree #$id not found", 400);
        }

        $restoredPage = $restoredPage->doRestoreToStage();

        $this->getResponse()->addHeader(
            'X-Status',
            rawurlencode(_t(
                __CLASS__ . '.RESTORED',
                "Restored '{title}' successfully",
                array('title' => $restoredPage->Title)
            ))
        );

        return $this->getResponseNegotiator()->respond($this->getRequest());
    }

    public function duplicate($request)
    {
        // Protect against CSRF on destructive action
        if (!SecurityToken::inst()->checkRequest($request)) {
            return $this->httpError(400);
        }

        if (($id = $this->urlParams['ID']) && is_numeric($id)) {
            /** @var SiteTree $page */
            $page = SiteTree::get()->byID($id);
            if ($page && (!$page->canEdit() || !$page->canCreate(null, array('Parent' => $page->Parent())))) {
                return Security::permissionFailure($this);
            }
            if (!$page || !$page->ID) {
                throw new HTTPResponse_Exception("Bad record ID #$id", 404);
            }

            $newPage = $page->duplicate();

            // ParentID can be hard-set in the URL.  This is useful for pages with multiple parents
            if (isset($_GET['parentID']) && is_numeric($_GET['parentID'])) {
                $newPage->ParentID = $_GET['parentID'];
                $newPage->write();
            }

            $this->getResponse()->addHeader(
                'X-Status',
                rawurlencode(_t(
                    __CLASS__ . '.DUPLICATED',
                    "Duplicated '{title}' successfully",
                    array('title' => $newPage->Title)
                ))
            );
            $url = Controller::join_links(CMSPageEditController::singleton()->Link('show'), $newPage->ID);
            $this->getResponse()->addHeader('X-ControllerURL', $url);
            $this->getRequest()->addHeader('X-Pjax', 'Content');
            $this->getResponse()->addHeader('X-Pjax', 'Content');

            return $this->getResponseNegotiator()->respond($this->getRequest());
        } else {
            return new HTTPResponse("CMSMain::duplicate() Bad ID: '$id'", 400);
        }
    }

    public function duplicatewithchildren($request)
    {
        // Protect against CSRF on destructive action
        if (!SecurityToken::inst()->checkRequest($request)) {
            return $this->httpError(400);
        }
        Environment::increaseTimeLimitTo();
        if (($id = $this->urlParams['ID']) && is_numeric($id)) {
            /** @var SiteTree $page */
            $page = SiteTree::get()->byID($id);
            if ($page && (!$page->canEdit() || !$page->canCreate(null, array('Parent' => $page->Parent())))) {
                return Security::permissionFailure($this);
            }
            if (!$page || !$page->ID) {
                throw new HTTPResponse_Exception("Bad record ID #$id", 404);
            }

            $newPage = $page->duplicateWithChildren();

            $this->getResponse()->addHeader(
                'X-Status',
                rawurlencode(_t(
                    __CLASS__ . '.DUPLICATEDWITHCHILDREN',
                    "Duplicated '{title}' and children successfully",
                    array('title' => $newPage->Title)
                ))
            );
            $url = Controller::join_links(CMSPageEditController::singleton()->Link('show'), $newPage->ID);
            $this->getResponse()->addHeader('X-ControllerURL', $url);
            $this->getRequest()->addHeader('X-Pjax', 'Content');
            $this->getResponse()->addHeader('X-Pjax', 'Content');

            return $this->getResponseNegotiator()->respond($this->getRequest());
        } else {
            return new HTTPResponse("CMSMain::duplicatewithchildren() Bad ID: '$id'", 400);
        }
    }

    public function providePermissions()
    {
        $title = CMSPagesController::menu_title();
        return array(
            "CMS_ACCESS_CMSMain" => array(
                'name' => _t(__CLASS__ . '.ACCESS', "Access to '{title}' section", array('title' => $title)),
                'category' => _t('SilverStripe\\Security\\Permission.CMS_ACCESS_CATEGORY', 'CMS Access'),
                'help' => _t(
                    __CLASS__ . '.ACCESS_HELP',
                    'Allow viewing of the section containing page tree and content. View and edit permissions can be handled through page specific dropdowns, as well as the separate "Content permissions".'
                ),
                'sort' => -99 // below "CMS_ACCESS_LeftAndMain", but above everything else
            )
        );
    }

    /**
     * Get title for root CMS node
     *
     * @return string
     */
    protected function getCMSTreeTitle()
    {
        $rootTitle = SiteConfig::current_site_config()->Title;
        $this->extend('updateCMSTreeTitle', $rootTitle);
        return $rootTitle;
    }

    /**
     * Cache key for SiteTreeHints() method
     *
     * @param $memberID
     * @return string
     */
    protected function generateHintsCacheKey($memberID)
    {
        return md5($memberID . '_' . __CLASS__);
    }

    /**
     * Clear the cache on ?flush
     */
    public static function flush()
    {
        CMSMain::singleton()->clearCache();
    }

    /**
     * Flush the hints cache for a specific member
     *
     * @param array $memberIDs
     */
    public function flushMemberCache($memberIDs = null)
    {
        $cache = $this->getHintsCache();

        if (!$memberIDs) {
            $cache->clear();
            return;
        }

        foreach ($memberIDs as $memberID) {
            $key = $this->generateHintsCacheKey($memberID);
            $cache->delete($key);
        }
    }
}
