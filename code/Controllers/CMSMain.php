<?php

namespace SilverStripe\CMS\Controllers;

use LogicException;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\Admin\AdminRootController;
use SilverStripe\Admin\CMSBatchActionHandler;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Admin\LeftAndMainFormRequestHandler;
use SilverStripe\Admin\Navigator\SilverStripeNavigator;
use SilverStripe\CMS\BatchActions\CMSBatchAction_Archive;
use SilverStripe\CMS\BatchActions\CMSBatchAction_Publish;
use SilverStripe\CMS\BatchActions\CMSBatchAction_Unpublish;
use SilverStripe\CMS\Forms\CMSMainAddForm;
use SilverStripe\CMS\Model\CurrentRecordIdentifier;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Control\PjaxResponseNegotiator;
use SilverStripe\Core\Cache\MemberCacheFlusher;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Flushable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Manifest\ModuleResource;
use SilverStripe\Core\Manifest\ModuleResourceLoader;
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
use SilverStripe\Model\List\ArrayList;
use SilverStripe\ORM\CMSPreviewable;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\HiddenClass;
use SilverStripe\ORM\Hierarchy\Hierarchy;
use SilverStripe\ORM\Hierarchy\MarkedSet;
use SilverStripe\Model\List\SS_List;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Security\InheritedPermissions;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Security\Security;
use SilverStripe\Security\SecurityToken;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Versioned\Versioned;
use SilverStripe\VersionedAdmin\Controllers\CMSPageHistoryViewerController;
use SilverStripe\Model\ArrayData;
use SilverStripe\ORM\Search\SearchContextForm;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionCheckable;
use SilverStripe\Versioned\RecursivePublishable;
use SilverStripe\View\Requirements;
use SilverStripe\View\ThemeResourceLoader;

/**
 * The main "content" area of the CMS.
 *
 * This class creates a 2-frame layout - left-tree and right-form - to sit beneath the main
 * admin menu.
 */
class CMSMain extends LeftAndMain implements CurrentRecordIdentifier, PermissionProvider, Flushable, MemberCacheFlusher
{
    /**
     * Unique ID for page icons CSS block
     */
    public const CMS_RECORD_ICONS_ID = 'CMSRecordIcons';

    private static string $url_segment = 'pages';

    private static string $url_rule = '/$Action/$ID/$OtherID';

    // Maintain a lower priority than other administration sections
    // so that Director does not think they are actions of CMSMain
    private static int $url_priority = 39;

    private static $menu_title = 'Pages';

    private static string $menu_icon_class = 'font-icon-sitemap';

    private static int $menu_priority = 10;

    private static string $model_class = SiteTree::class;

    private static string $session_namespace = CMSMain::class;

    private static string|array $required_permission_codes = 'CMS_ACCESS_CMSMain';

    /**
     * Should the archive warning message be dynamic based on the specific content? This is slow on larger sites and can be disabled.
     */
    private static bool $enable_dynamic_archive_warning_message = true;

    /**
     * Amount of results showing on a single page.
     */
    private static int $page_length = 15;

    private static array $allowed_actions = [
        'add',
        'AddForm',
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
        'schema',
        'getSearchForm',
        'TreeAsUL',
        'getshowdeletedsubtree',
        'savetreenode',
        'getsubtree',
        'updatetreenodes',
        'batchactions',
        'treeview',
        'listview',
        'ListViewForm',
        'childfilter',
    ];

    private static array $url_handlers = [
        'EditForm/$ID' => 'EditForm',
        'GET SearchForm' => 'getSearchForm',
        'treeview/$ID' => 'treeview',
    ];

    private static array $casting = [
        'TreeIsFiltered' => 'Boolean',
        'AddForm' => 'HTMLFragment',
        'LinkRecords' => 'Text',
        'Link' => 'Text',
        'ListViewForm' => 'HTMLFragment',
        'ExtraTreeTools' => 'HTMLFragment',
        'RecordList' => 'HTMLFragment',
        'TreeHints' => 'HTMLFragment',
        'SecurityID' => 'Text',
        'TreeAsUL' => 'HTMLFragment',
    ];

    private static array $dependencies = [
        'HintsCache' => '%$' . CacheInterface::class . '.CMSMain_TreeHints',
        'creatableChildrenCache' => '%$' . CacheInterface::class . '.SiteTree_CreatableChildren',
    ];

    protected ?CacheInterface $hintsCache = null;

    private ?CacheInterface $creatableChildrenCache = null;

    protected function init()
    {
        $modelClass = $this->getModelClass();
        if (!$modelClass::has_extension(Hierarchy::class)) {
            throw new LogicException($modelClass . ' must have the Hierarchy extension');
        }
        if (!DataObject::singleton($modelClass)->getSortField()) {
            throw new LogicException($modelClass . ' must define a sort field');
        }

        parent::init();

        Requirements::javascript('silverstripe/cms: client/dist/js/bundle.js');
        Requirements::javascript('silverstripe/cms: client/dist/js/SilverStripeNavigator.js');
        Requirements::css('silverstripe/cms: client/dist/styles/bundle.css');

        Requirements::add_i18n_javascript('silverstripe/cms: client/lang', false);

        CMSBatchActionHandler::register('archive', CMSBatchAction_Archive::class);
        CMSBatchActionHandler::register('unpublish', CMSBatchAction_Unpublish::class);
        CMSBatchActionHandler::register('publish', CMSBatchAction_Publish::class);
    }

    public function index(HTTPRequest $request): HTTPResponse
    {
        // In case we're not showing a specific record, explicitly remove any session state,
        // to avoid it being highlighted in the tree, and causing an edit form to show.
        if (!$request->param('Action')) {
            $this->setCurrentRecordID(null);
        }

        return parent::index($request);
    }

    public function getResponseNegotiator(): PjaxResponseNegotiator
    {
        $negotiator = parent::getResponseNegotiator();

        // ListViewForm
        $negotiator->setCallback('ListViewForm', function () {
            return $this->ListViewForm()->forTemplate();
        });

        return $negotiator;
    }

    /**
     * Get record listing area
     */
    public function RecordList(): DBHTMLText
    {
        return $this->renderWith($this->getTemplatesWithSuffix('_RecordList'));
    }

    /**
     * If this is set to true, the "switchView" context in the
     * template is shown, with links to the staging and publish site.
     */
    public function ShowSwitchView(): bool
    {
        return true;
    }

    /**
     * Overloads the LeftAndMain::ShowView. Allows to pass a record as a parameter, so we are able
     * to switch view also for archived versions.
     */
    public function SwitchView(?DataObject $record = null): array
    {
        if (!$record) {
            $record = $this->currentRecord();
        }

        if ($record) {
            $nav = SilverStripeNavigator::get_for_record($record);
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

    public function LinkRecords()
    {
        $controller = $this;
        if (static::class !== CMSMain::class) {
            $controller = CMSMain::singleton();
        }
        return $controller->Link();
    }

    public function LinkRecordsWithSearch()
    {
        return $this->LinkWithSearch($this->LinkRecords());
    }

    /**
     * Get link to tree view
     *
     * @return string
     */
    public function LinkTreeView()
    {
        // Tree view is just default link to main section (no /treeview suffix)
        return CMSMain::singleton()->Link();
    }

    /**
     * Get link to list view
     *
     * @return string
     */
    public function LinkListView()
    {
        // Note : Force redirect to top level record controller (no parentid)
        return $this->LinkWithSearch(CMSMain::singleton()->Link('listview'));
    }

    /**
     * Link to list view for children of a parent record
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

    /**
     * Get the link for editing a record.
     *
     * @see CMSEditLinkExtension::getCMSEditLinkForManagedDataObject()
     */
    public function getCMSEditLinkForManagedDataObject(DataObject $obj): string
    {
        return $this->LinkRecordEdit($obj->ID);
    }

    public function LinkRecordEdit($id = null)
    {
        if (!$id) {
            $id = $this->currentRecordID();
        }
        return $this->LinkWithSearch(
            Controller::join_links(CMSPageEditController::singleton()->Link('show'), $id)
        );
    }

    public function LinkRecordSettings()
    {
        if (!DataObject::singleton($this->getModelClass())->hasMethod('getSettingsFields')) {
            return null;
        }
        if ($id = $this->currentRecordID()) {
            return $this->LinkWithSearch(
                Controller::join_links(CMSPageSettingsController::singleton()->Link('show'), $id)
            );
        } else {
            return null;
        }
    }

    public function LinkRecordHistory()
    {
        $controller = Injector::inst()->get(CMSPageHistoryViewerController::class);
        if (($id = $this->currentRecordID()) && $controller) {
            if ($controller) {
                return $this->LinkWithSearch(
                    Controller::join_links($controller->Link('show'), $id)
                );
            }
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

    public function setHintsCache(CacheInterface $cache): static
    {
        $this->hintsCache = $cache;
        return $this;
    }

    public function getHintsCache(): ?CacheInterface
    {
        return $this->hintsCache;
    }

    public function setCreatableChildrenCache(CacheInterface $cache): static
    {
        $this->creatableChildrenCache = $cache;
        return $this;
    }

    public function getCreatableChildrenCache(): ?CacheInterface
    {
        return $this->creatableChildrenCache;
    }

    /**
     * Clears all dependent cache backends
     */
    public function clearCache()
    {
        $this->getHintsCache()->clear();
        $this->getCreatableChildrenCache()->clear();
    }

    public function LinkWithSearch($link)
    {
        // Whitelist to avoid side effects
        $params = [
            'q' => (array)$this->getRequest()->getVar('q'),
            'ParentID' => $this->getRequest()->getVar('ParentID')
        ];
        $link = Controller::join_links(
            $link,
            array_filter(array_values($params ?? [])) ? '?' . http_build_query($params) : null
        );
        $this->extend('updateLinkWithSearch', $link);
        return $link;
    }

    public function LinkRecordAdd($extra = null, $placeholders = null)
    {
        $link = $this->Link('add');
        if ($extra) {
            $link = Controller::join_links($link, $extra);
        }
        if ($placeholders) {
            $link .= (strpos($link ?? '', '?') === false ? "?$placeholders" : "&$placeholders");
        }
        $this->extend('updateLinkRecordAdd', $link);
        return $link;
    }

    public function add()
    {
        if ($this->getRequest()->isAjax()) {
            return $this->AddForm()->forTemplate();
        }
        return $this->render([
            'Content' => DBHTMLText::create()->setValue($this->AddForm()->forTemplate()),
        ]);
    }

    public function AddForm(): CMSMainAddForm
    {
        return CMSMainAddForm::create($this);
    }

    /**
     * Return the entire tree as a nested set of ULs
     */
    public function TreeAsUL()
    {
        $modelClass = $this->getModelClass();
        $options = $this->getTreeAsULPrepopulateOptions($modelClass);
        DataObject::singleton($modelClass)->prepopulateTreeDataCache(null, $options);
        $html = $this->getTreeFor($modelClass);
        $this->extend('updateTreeAsUL', $html);
        return $html;
    }

    /**
     * Get the options used for the call to Hierarchy::prepopulateTreeDataCache()
     */
    private function getTreeAsULPrepopulateOptions(string $modelClass)
    {
        /** @var DataObject&Hierarchy $obj */
        $obj = DataObject::singleton($modelClass);
        $baseClass = $obj->getHierarchyBaseClass();
        return [
            'childrenMethod' => $baseClass::config()->get('tree_children_method'),
            'numChildrenMethod' => 'numChildren',
        ];
    }

    /**
     * Get a tree HTML listing which displays the nodes under the given criteria.
     *
     * @param string $className The class of the root object
     * @param string $rootID The ID of the root object.  If this is null then a complete tree will be
     *  shown
     * @param string $childrenMethod The method to call to get the children of the tree. For example,
     *  Children, AllChildrenIncludingDeleted, or AllHistoricalChildren
     * @param string $numChildrenMethod
     * @param callable $filterFunction
     * @param int $nodeCountThreshold
     * @return string Nested unordered list with links to each record
     */
    public function getTreeFor(
        $className,
        $rootID = null,
        $childrenMethod = null,
        $numChildrenMethod = null,
        $filterFunction = null,
        $nodeCountThreshold = null
    ) {
        $nodeCountThreshold = is_null($nodeCountThreshold) ? Config::inst()->get($className, 'node_threshold_total') : $nodeCountThreshold;

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

        // Ensure current record is exposed
        $currentRecord = $this->currentRecord();
        if ($currentRecord) {
            $markingSet->markToExpose($currentRecord);
        }

        // Pre-cache permissions if using a permission checker
        $modelClass = $this->getModelClass();
        if (is_a($modelClass, PermissionCheckable::class, true)) {
            $checker = DataObject::singleton($modelClass)->getPermissionChecker();
            if ($checker instanceof InheritedPermissions) {
                $checker->prePopulatePermissionCache(
                    InheritedPermissions::EDIT,
                    $markingSet->markedNodeIDs()
                );
            }
        }

        // Render using full-subtree template
        return $markingSet->renderChildren(
            [ CMSMain::class . '_SubTree', 'type' => 'Includes' ],
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
        $isFirstPageAssigned = false;
        $currentRecordID = $this->currentRecordID();
        $hasCurrentPage = $currentRecordID !== null;

        return function (DataObject $node) use ($currentRecordID, $hasCurrentPage, &$isFirstPageAssigned) {
            $isCurrentPage = $node->ID === $currentRecordID;
            $isFirstPage = false;
            if (!$isFirstPageAssigned) {
                $isFirstPage = true;
                $isFirstPageAssigned = true;
            }

            return [
                'Controller' => $this,
                'isCurrentPage' => $isCurrentPage,
                'isFirstPage' => $isFirstPage,
                'hasCurrentPage' => $hasCurrentPage,
                'listViewLink' => $this->LinkListViewChildren($node->ID),
                'rootTitle' => $this->getCMSTreeTitle(),
                'extraClass' => $this->getTreeNodeClasses($node),
                'Title' => _t(
                    CMSMain::class . '.RECORD_TYPE_TITLE',
                    '(Record type: {type}) {title}',
                    [
                        'type' => $node->i18n_singular_name(),
                        'title' => $node->Title,
                    ]
                ),
                'TreeTitle' => DBHTMLText::create()->setValue($this->getRecordTreeMarkup($node)),
            ];
        };
    }

    /**
     * Get extra CSS classes for a record's tree node
     */
    public function getTreeNodeClasses(DataObject $node): string
    {
        // Get classes from object
        $classes = $node->CMSTreeClasses();

        // Get status flag classes
        $flags = $node->getStatusFlags();
        if ($flags) {
            $statuses = array_keys($flags ?? []);
            foreach ($statuses as $s) {
                $classes .= ' status-' . $s;
            }
        }

        return trim($classes ?? '');
    }

    /**
     * Get a subtree underneath the request param 'ID'.
     * If ID = 0, then get the whole tree.
     */
    public function getsubtree(HTTPRequest $request): HTTPResponse
    {
        $html = $this->getTreeFor(
            $this->getModelClass(),
            $request->getVar('ID'),
            null,
            null,
            null,
            $request->getVar('minNodeCount')
        );

        // Trim off the outer tag
        $html = preg_replace('/^[\s\t\r\n]*<ul[^>]*>/', '', $html ?? '');
        $html = preg_replace('/<\/ul[^>]*>[\s\t\r\n]*$/', '', $html ?? '');

        return $this->getResponse()->setBody($html);
    }

    /**
     * Allows requesting a view update on specific tree nodes.
     * Similar to {@link getsubtree()}, but doesn't enforce loading
     * all children with the node. Useful to refresh views after
     * state modifications, e.g. saving a form.
     */
    public function updatetreenodes(HTTPRequest $request): HTTPResponse
    {
        $data = [];
        $ids = explode(',', $request->getVar('ids') ?? '');
        foreach ($ids as $id) {
            if ($id === "") {
                continue; // $id may be a blank string, which is invalid and should be skipped over
            }

            /** @var DataObject&Hierarchy $record */
            $record = $this->getRecord($id);
            if (!$record) {
                continue; // In case a record is no longer available
            }

            // Create marking set with sole marked root
            $markingSet = MarkedSet::create($record);
            $markingSet->setMarkingFilterFunction(function () {
                return false;
            });
            $markingSet->markUnexpanded($record);

            // Find the next & previous nodes, for proper positioning (Sort isn't good enough - it's not a raw offset)
            $prev = null;

            $className = $this->getModelClass();
            $sortField = $record->getSortField();
            $list = DataObject::get($className)->filter('ParentID', $record->ParentID);
            if ($sortField) {
                $list = $list->filter($sortField . ':GreaterThan', $record->$sortField);
            }
            $next = $list->first();

            if (!$next) {
                $list = DataObject::get($className)->filter('ParentID', $record->ParentID);
                if ($sortField) {
                    $list = $list->filter($sortField . ':LessThan', $record->$sortField);
                }
                $prev = $list->reverse()->first();
            }

            // Render using single node template
            $html = $markingSet->renderChildren(
                [ CMSMain::class . '_TreeNode', 'type' => 'Includes'],
                $this->getTreeNodeCustomisations()
            );

            $data[$id] = [
                'html' => $html,
                'ParentID' => $record->ParentID,
                'NextID' => $next ? $next->ID : null,
                'PrevID' => $prev ? $prev->ID : null
            ];
        }
        return $this
            ->getResponse()
            ->addHeader('Content-Type', 'application/json')
            ->setBody(json_encode($data));
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
     * @throws HTTPResponse_Exception
     */
    public function savetreenode(HTTPRequest $request): HTTPResponse
    {
        if (!SecurityToken::inst()->checkRequest($request)) {
            $this->httpError(400);
        }
        if (!$this->canOrganiseTree()) {
            $this->httpError(
                403,
                _t(
                    __CLASS__.'.CANT_REORGANISE2',
                    "You do not have permission to rearange the tree. Your change was not saved.",
                )
            );
        }

        $className = $this->getModelClass();
        $id = $request->requestVar('ID');
        $parentID = $request->requestVar('ParentID');
        if (!is_numeric($id) || !is_numeric($parentID)) {
            $this->httpError(400);
        }

        // Check record exists in the DB
        /** @var DataObject&Hierarchy $node */
        $node = DataObject::get($className)->setUseCache(true)->byID($id);
        if (!$node) {
            $this->httpError(
                500,
                _t(
                    __CLASS__.'.PLEASESAVE2',
                    "Please Save Record: This record could not be updated because it hasn't been saved yet."
                )
            );
        }

        // Check top level permissions
        $isRoot = $node->ParentID == 0;
        if (($parentID == '0' || $isRoot) && !$this->canCreateTopLevel()) {
            $this->httpError(
                403,
                _t(
                    __CLASS__.'.CANT_REORGANISE_TOPLEVEL',
                    'You do not have permission to alter Top level records. Your change was not saved.'
                )
            );
        }

        $siblingIDs = $request->requestVar('SiblingIDs');
        $statusUpdates = ['modified'=>[]];

        if (!$node->canEdit()) {
            return Security::permissionFailure($this);
        }

        // Update hierarchy (only if ParentID changed)
        $parentChanged = $node->ParentID != $parentID;
        if ($parentChanged) {
            $node->ParentID = (int)$parentID;
            $node->write();

            $statusUpdates['modified'][$node->ID] = [
                'TreeTitle' => $this->getRecordTreeMarkup($node),
            ];
            $this->getResponse()->addHeader(
                'X-Status',
                rawurlencode(_t(__CLASS__.'.REORGANISATIONSUCCESSFUL2', 'Reorganised the tree successfully.') ?? '')
            );
        }

        // Update sorting
        $sortField = $node->getSortField();
        $sortChanged = $sortField && is_array($siblingIDs);
        if ($sortChanged) {
            $counter = 0;
            foreach ($siblingIDs as $id) {
                if ($id == $node->ID) {
                    $node->$sortField = ++$counter;
                    $node->write();
                    $statusUpdates['modified'][$node->ID] = [
                        'TreeTitle' => $this->getRecordTreeMarkup($node),
                    ];
                } elseif (is_numeric($id)) {
                    // Nodes that weren't "actually moved" shouldn't be registered as
                    // having been edited; do a direct SQL update instead
                    ++$counter;
                    $table = DataObject::getSchema()->baseDataTable($className);
                    DB::prepared_query(
                        "UPDATE \"$table\" SET \"$sortField\" = ? WHERE \"ID\" = ?",
                        [$counter, $id]
                    );
                }
            }

            $this->getResponse()->addHeader(
                'X-Status',
                rawurlencode(_t(__CLASS__.'.REORGANISATIONSUCCESSFUL2', 'Reorganised the tree successfully.') ?? '')
            );
        }

        $node->invokeWithExtensions('updateSaveTreeNodeStatusUpdates', $statusUpdates, $parentChanged, $sortChanged);

        return $this
            ->getResponse()
            ->addHeader('Content-Type', 'application/json')
            ->setBody(json_encode($statusUpdates));
    }

    /**
     * Whether the current member has the permission to create top-level records.
     * Note that a canCreate() check is performed separately.
     */
    public function canCreateTopLevel(): bool
    {
        // Hardcoded check for SiteTree
        // Ideally CMSMain will get an abstract parent class with all of the generic logic,
        // and then this conditional check can be removed in CMSMain itself.
        if (is_a($this->getModelClass(), SiteTree::class, true)) {
            return SiteConfig::current_site_config()->canCreateTopLevel();
        }
        // Assume anyone who can create records can create them at the top level.
        return true;
    }

    /**
     * Whether the current member has the permission to reorganise records.
     * Note that a canEdit() check is performed separately.
     */
    public function canOrganiseTree(): bool
    {
        // Hardcoded check for SiteTree
        // Ideally CMSMain will get an abstract parent class with all of the generic logic,
        // and then this conditional check can be removed in CMSMain itself.
        if (is_a($this->getModelClass(), SiteTree::class, true)) {
            return (bool) Permission::check('SITETREE_REORGANISE');
        }
        // Assume anyone who can edit can also organise the tree.
        return true;
    }

    /**
     * Whether the tree has been filtered in this request or not.
     */
    public function TreeIsFiltered(): bool
    {
        $query = $this->getRequest()->getVar('q');
        return !empty($query);
    }

    public function ExtraTreeTools(): string
    {
        $html = '';
        $this->extend('updateExtraTreeTools', $html);
        return $html;
    }

    /**
     * Returns a Form for record searching for use in templates.
     */
    public function getSearchForm(): SearchContextForm
    {
        $context = DataObject::singleton($this->getModelClass())->getDefaultSearchContext();
        $params = $this->getRequest()->requestVar('q') ?: [];
        $context->setSearchParams($params);
        $form = SearchContextForm::create($this, $context);
        // Allow decorators to modify the form
        $this->extend('updateSearchForm', $form);
        return $form;
    }

    /**
     * Returns a sorted array suitable for a dropdown with classes and their localised name
     */
    protected function getRecordTypes(): array
    {
        $types = [];
        foreach ($this->getAllowedSubClasses() as $class) {
            $types[$class] = DataObject::singleton($class)->i18n_singular_name();
        }
        asort($types);
        return $types;
    }

    public function doSearch(array $data, Form $form): HTTPResponse
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
            return $this->LinkRecords();
        }
        // Get second from end breadcrumb
        return $breadcrumbs
            ->offsetGet($breadcrumbs->count() - 2)
            ->Link;
    }

    public function Breadcrumbs($unlinked = false)
    {
        $items = ArrayList::create();

        if (($this->getAction() !== 'index') && ($record = $this->currentRecord())) {
            // The record is being edited
            $this->buildEditFormBreadcrumb($items, $record, $unlinked);
        } else {
            // Ensure we always have the admin section crumb first
            $this->pushCrumb(
                $items,
                CMSMain::menu_title(),
                $unlinked ? false : $this->LinkRecords()
            );

            if ($this->TreeIsFiltered()) {
                // Showing search results
                $this->pushCrumb(
                    $items,
                    _t(CMSMain::class . '.SEARCHRESULTS', 'Search results'),
                    ($unlinked) ? false : $this->LinkRecords()
                );
            } elseif ($parentID = $this->getRequest()->getVar('ParentID')) {
                // We're navigating the listview. ParentID is the record whose
                // children are currently displayed.
                if ($record = DataObject::get($this->getModelClass())->byID($parentID)) {
                    $this->buildListViewBreadcrumb($items, $record);
                }
                // At this stage we also know the parent ID is the "current" ID for this view
                $this->setCurrentRecordID($parentID);
            }
        }

        $this->extend('updateBreadcrumbs', $items);

        return $items;
    }

    /**
     * Push the provided an extra breadcrumb crumb at the end of the provided List
     */
    private function pushCrumb(ArrayList $items, string $title, string|false $link): void
    {
        $items->push(ArrayData::create([
            'Title' => $title,
            'Link' => $link
        ]));
    }

    /**
     * Build Breadcrumb for the Edit form. Each crumb links back to its own edit form.
     */
    private function buildEditFormBreadcrumb(ArrayList $items, DataObject $record, bool $unlinked): void
    {
        // Find all ancestors of the provided record
        /** @var DataObject&Hierarchy $record */
        $ancestors = $record->getAncestors(true);
        $ancestors = array_reverse($ancestors->toArray() ?? []);
        foreach ($ancestors as $ancestor) {
            // Link to the ancestor's edit form
            $this->pushCrumb(
                $items,
                $ancestor->MenuTitle ?: $ancestor->Title ?: '',
                $unlinked ? false : $this->LinkRecordEdit($ancestor->ID)
            );
        }
    }

    /**
     * Build Breadcrumb for the List view. Each crumb links to the list view for that record.
     */
    private function buildListViewBreadcrumb(ArrayList $items, DataObject $record): void
    {
        // Find all ancestors of the provided record
        /** @var DataObject&Hierarchy $record */
        $ancestors = $record->getAncestors(true);
        $ancestors = array_reverse($ancestors->toArray() ?? []);

        //turns the title and link of the breadcrumbs into template-friendly variables
        $params = array_filter([
            'view' => $this->getRequest()->getVar('view'),
            'q' => $this->getRequest()->getVar('q')
        ]);

        foreach ($ancestors as $ancestor) {
            // Link back to the list view for the current ancestor
            $params['ParentID'] = $ancestor->ID;
            $this->pushCrumb(
                $items,
                $ancestor->MenuTitle ?: $ancestor->Title,
                Controller::join_links($this->Link(), '?' . http_build_query($params ?? []))
            );
        }
    }

    /**
     * Create serialized JSON string with tree hints data to be injected into
     * 'data-hints' attribute of root node of jsTree.
     */
    public function TreeHints(): string
    {
        $classes = $this->getAllowedSubClasses();
        $memberID = Security::getCurrentUser()?->ID ?? 0;
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
        // such as the "add record here" context menu.
        $def['All'] = [];

        // Identify disallows and set globals
        foreach ($classes as $class) {
            /** @var DataObject&Hierarchy $obj */
            $obj = DataObject::singleton($class);
            if ($obj instanceof HiddenClass) {
                continue;
            }

            // Name item
            $def['All'][$class] = [
                'title' => $obj->i18n_singular_name()
            ];

            // Check if can be created at the root
            if ($obj::config()->get('can_be_root') === false
                || (!array_key_exists($class, $canCreate ?? []) || !$canCreate[$class])
            ) {
                $def['Root']['disallowedChildren'][] = $class;
            }

            // Hint data specific to the class
            $def[$class] = [];

            $defaultChild = $obj->defaultChild();
            if ($defaultChild !== $this->getDefaultModelClass() && $defaultChild !== null) {
                $def[$class]['defaultChild'] = $defaultChild;
            }

            $defaultParent = $obj->defaultParent();
            if ($defaultParent !== 1 && $defaultParent !== null) {
                $def[$class]['defaultParent'] = $defaultParent;
            }
        }

        $this->extend('updateTreeHints', $def);

        $json = json_encode($def);
        $cache->set($cacheKey, $json);

        return $json;
    }

    /**
     * Populates an array of classes in the CMS
     * which allows the user to change the record's ClassName field.
     */
    public function RecordTypes(): SS_List
    {
        $classes = $this->getAllowedSubClasses();
        $result = new ArrayList();

        foreach ($classes as $class) {
            $instance = DataObject::singleton($class);
            if ($instance instanceof HiddenClass) {
                continue;
            }

            $result->push(new ArrayData([
                'ClassName' => $class,
                'AddAction' => $instance->i18n_singular_name(),
                'Description' => $instance->i18n_classDescription(),
                'IconURL' => $this->getRecordIconUrl($instance),
                'Title' => $instance->i18n_singular_name(),
            ]));
        }

        $result = $result->sort('AddAction');

        return $result;
    }

    /**
     * Get the URL to the icon for this record, if there is one
     */
    public function getRecordIconUrl(DataObject|string $recordOrClass): ?string
    {
        if (is_string($recordOrClass)) {
            $icon = Config::inst()->get($recordOrClass, 'cms_icon');
        } else {
            $icon = $recordOrClass::config()->get('cms_icon');
        }
        if (!$icon) {
            return null;
        }
        if (strpos($icon ?? '', 'data:image/') !== false) {
            return $icon;
        }

        // Icon is relative resource
        $iconResource = ModuleResourceLoader::singleton()->resolveResource($icon);
        if ($iconResource instanceof ModuleResource) {
            return $iconResource->getURL();
        }

        // Icon is themed resource
        $iconThemedUrl = ThemeResourceLoader::themedResourceURL($icon);
        if ($iconThemedUrl) {
            return $iconThemedUrl;
        }

        // Full path to file
        if (Director::fileExists($icon)) {
            return ModuleResourceLoader::resourceURL($icon);
        }

        // Skip invalid files
        return null;
    }

    /**
     * Get the CSS class for the record's icon, if there is one
     */
    public function getRecordIconCssClass(DataObject|string $recordOrClass): ?string
    {
        $uninheritedClass = $recordOrClass::config()->get('cms_icon_class', Config::UNINHERITED);
        if ($uninheritedClass) {
            return $uninheritedClass;
        }
        if ($recordOrClass::config()->get('cms_icon')) {
            return null;
        }
        $icon = $recordOrClass::config()->get('cms_icon_class');
        if (!$icon) {
            $icon = 'font-icon-edit';
        }
        return $icon;
    }

    /**
     * Get the HTML markup to represent the record in a jstree structure.
     *
     * Returns three <span> html elements, an empty <span> with the class 'jstree-recordicon' in
     * front, following by a <span> wrapping around its MenuTitle, then following by a <span> indicating its
     * publication status.
     */
    public function getRecordTreeMarkup(DataObject $record): string
    {
        /** @var DataObject&Hierarchy $record */
        $children = $this->getCreatableSubClasses($record);
        $flags = $record->getStatusFlags();
        $iconClasses = [
            'jstree-recordicon',
            'record-icon',
            $this->getRecordIconCssClass($record),
            'class-' . Convert::raw2htmlid(get_class($record)),
        ];
        $record->invokeWithExtensions('updateTreeIconClasses', $iconClasses);
        $titleText = $record->getTreeTitle();
        // Hierarchy::getTreeTitle() will call Convert::raw2xml(), though this may have been
        // overridden by another method that doesn't call this
        // Check to see if it was converted first by calling the xml2raw() to before calling
        // raw2xml() to ensure we don't double convert and end up with "&amp;amp;"
        $wasChanged = Convert::xml2raw($titleText) !== $titleText;
        if (!$wasChanged) {
            $titleText = Convert::raw2xml($titleText);
        }
        $titleText = str_replace(["\n","\r"], '', $titleText);
        $titleText = trim($titleText);
        if ($titleText === '') {
            $titleText = _t(__CLASS__ . '.TREE_NO_TITLE', '(no title)');
        }
        $treeTitle = sprintf(
            '<span class="%s" aria-hidden="true"></span><span class="item" data-allowedchildren="%s">%s</span>',
            implode(' ', $iconClasses),
            Convert::raw2att(json_encode($children)),
            $titleText
        );
        foreach ($flags as $class => $data) {
            if (is_string($data)) {
                $data = ['text' => $data];
            }
            $treeTitle .= sprintf(
                '<span class="badge %s"%s>%s</span>',
                'status-' . Convert::raw2xml($class),
                (isset($data['title'])) ? sprintf(' title="%s"', Convert::raw2xml($data['title'])) : '',
                Convert::raw2xml($data['text'])
            );
        }

        return $treeTitle;
    }

    /**
     * Get a database record to be managed by the CMS.
     *
     * @param int $id Record ID
     * @param int $versionID optional Version id of the given record
     */
    public function getRecord($id, ?int $versionID = null): ?DataObject
    {
        if (!$id) {
            return null;
        }
        $modelClass = $this->getModelClass();
        if ($id instanceof $modelClass) {
            return $id;
        }
        if (substr($id ?? '', 0, 3) == 'new') {
            return $this->getNewItem($id);
        }
        if ($id === 'singleton') {
            return DataObject::singleton($modelClass);
        }
        if (!is_numeric($id)) {
            return null;
        }

        $currentStage = Versioned::get_reading_mode();

        if ($this->getRequest()->getVar('Version')) {
            $versionID = (int) $this->getRequest()->getVar('Version');
        }

        $isVersioned = $modelClass::has_extension(Versioned::class);
        if ($versionID) {
            if (!$isVersioned) {
                throw new HTTPResponse_Exception("Cannot get a version of non-versioned $modelClass record", 400);
            }
            $record = Versioned::get_version($modelClass, $id, $versionID);
        } else {
            $record = DataObject::get($modelClass)->setUseCache(true)->byID($id);
        }

        // Then, try getting a record from the live site
        if (!$record && $isVersioned) {
            // $record = Versioned::get_one_by_stage($modelClass, "Live", "\"$modelClass\".\"ID\" = $id");
            Versioned::set_stage(Versioned::LIVE);
            DataObject::singleton($modelClass)->flushCache();

            $record = DataObject::get($modelClass)->setUseCache(true)->byID($id);
        }

        // Then, try getting a deleted record
        if (!$record && $isVersioned) {
            $record = Versioned::get_latest_version($modelClass, $id);
        }

        // Set the reading mode back to what it was.
        Versioned::set_reading_mode($currentStage);

        return $record;
    }

    /**
     * {@inheritdoc}
     *
     * @param HTTPRequest $request
     */
    public function EditForm($request = null): Form
    {
        // set record ID from request
        if ($request) {
            // Validate id is present
            $id = $request->param('ID');
            if (!isset($id)) {
                $this->httpError(400);
                return null;
            }
            $this->setCurrentRecordID($id);
        }
        return $this->getEditForm();
    }

    /**
     * @param int $id
     * @param FieldList $fields
     */
    public function getEditForm($id = null, $fields = null): Form
    {
        // Get record
        if (!$id) {
            $id = $this->currentRecordID();
        }
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
        $fields->push(new HiddenField('ID', false, $id));
        $fields->push($archiveWarningMsgField = new HiddenField('ArchiveWarningMessage'));
        $fields->push(new HiddenField('TreeTitle', false, $this->getRecordTreeMarkup($record)));

        $archiveWarningMsgField->setValue($this->getArchiveWarningMessage($record));

        // Added in-line to the form, but plucked into different view by LeftAndMain.Preview.js upon load
        if (($record instanceof CMSPreviewable || $record->hasExtension(CMSPreviewable::class))
            && !$fields->fieldByName('SilverStripeNavigator')
        ) {
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
        $actionsFlattened = $actions->getDataFields();
        if ($actionsFlattened) {
            /** @var FormAction $action */
            foreach ($actionsFlattened as $action) {
                $action->setUseButtonTag(true);
            }
        }

        $form->addExtraClass('center ' . $this->BaseCSSClasses());
        // Set validation exemptions for specific actions
        $form->setValidationExemptActions([
            'restore',
            'revert',
            'deletefromlive',
            'delete',
            'unpublish',
            'rollback',
            'doRollback',
            'archive',
        ]);

        // Announce the capability so the frontend can decide whether to allow preview or not.
        if ($record instanceof CMSPreviewable || $record->hasExtension(CMSPreviewable::class)) {
            $form->addExtraClass('cms-previewable');
        }
        $form->addExtraClass('fill-height flexbox-area-grow');

        if (!$record->canEdit() || ($record->hasExtension(Versioned::class) && $record->hasStages() && !$record->isOnDraft())) {
            $readonlyFields = $form->Fields()->makeReadonly();
            $form->setFields($readonlyFields);
        }

        $form->Fields()->setForm($form);

        $this->extend('updateEditForm', $form);

        // Use custom request handler for LeftAndMain requests;
        // CMS Forms cannot be identified solely by name, but also need ID (and sometimes OtherID)
        $form->setRequestHandler(
            LeftAndMainFormRequestHandler::create($form, [$id])
        );
        return $form;
    }

    public function EmptyForm(): Form
    {
        $fields = new FieldList(
            new LabelField('RecordDoesntExistLabel', _t(__CLASS__ . '.RECORDNOTEXISTS', "This record doesn't exist"))
        );
        $form = parent::EmptyForm();
        $form->setFields($fields);
        $fields->setForm($form);
        return $form;
    }

    /**
     * Build an archive warning message based on the record's children
     */
    protected function getArchiveWarningMessage(DataObject $record): string
    {
        $defaultMessage = _t(
            LeftAndMain::class . '.ArchiveWarningWithChildren',
            'Warning: This record and all of its child records will be unpublished before being sent to the archive.\n\nAre you sure you want to proceed?'
        );

        // Option to disable this feature as it is slow on large sites
        if (!static::config()->get('enable_dynamic_archive_warning_message')) {
            return $defaultMessage;
        }

        // Get all record's descendants
        $descendants = [];
        $this->collateDescendants([$record->ID], $descendants);
        if (!$descendants) {
            $descendants = [];
        }

        if (count($descendants ?? []) > 0) {
            $archiveWarningMsg = $defaultMessage;
        } else {
            $archiveWarningMsg = _t(
                LeftAndMain::class . '.ArchiveWarning',
                'Warning: This record will be unpublished before being sent to the archive.\n\nAre you sure you want to proceed?'
            );
        }
        $this->extend('updateArchiveWarningMessage', $archiveWarningMsg, $descendants, $record);

        return $archiveWarningMsg;
    }

    /**
     * Find IDs of all descendant records for the provided ID lists.
     * @param int[] $recordIDs
     * @param array $collator
     * @return bool
     */
    protected function collateDescendants($recordIDs, &$collator)
    {
        // Disabling sort improves performance
        $children = DataObject::get($this->getModelClass())->filter(['ParentID' => $recordIDs])->sort(null)->column('ID');
        if ($children) {
            foreach ($children as $item) {
                $collator[] = $item;
            }
            $this->collateDescendants($children, $collator);
            return true;
        }
        return false;
    }

    /**
     * This method exclusively handles deferred ajax requests to render the
     * records tree deferred handler (no pjax-fragment)
     *
     * @return DBHTMLText HTML response with the rendered treeview
     */
    public function treeview()
    {
        $id = $this->getRequest()->param('ID');
        if ($id) {
            $this->setCurrentRecordID($id);
        }
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
     * Callback to request the list of record types allowed under a given record instance.
     * Provides a slower but more precise response over TreeHints
     */
    public function childfilter(HTTPRequest $request): HTTPResponse
    {
        // Check valid parent specified
        $parentID = $request->requestVar('ParentID');
        $parent = DataObject::get($this->getModelClass())->byID($parentID);
        if (!$parent || !$parent->exists()) {
            $this->httpError(404);
        }

        // Build hints specific to this class
        // Identify disallows and set globals
        $classes = $this->getAllowedSubClasses();
        $disallowedChildren = [];
        foreach ($classes as $class) {
            $obj = singleton($class);
            if ($obj instanceof HiddenClass) {
                continue;
            }

            if (!$obj->canCreate(null, ['Parent' => $parent])) {
                $disallowedChildren[] = $class;
            }
        }

        $this->extend('updateChildFilter', $disallowedChildren, $parentID);
        return $this
            ->getResponse()
            ->addHeader('Content-Type', 'application/json; charset=utf-8')
            ->setBody(json_encode($disallowedChildren));
    }

    /**
     * @return Form
     */
    public function ListViewForm()
    {
        $modelClass = $this->getModelClass();
        $parentID = $this->getRequest()->requestVar('ParentID');
        $params = $this->getRequest()->requestVar('q') ?? [];
        if ($params) {
            $form = $this->getSearchForm();
            $params = $form->prepareValuesForSearchContext($params);
            $list = DataObject::singleton($modelClass)->getDefaultSearchContext()->getQuery($params);
        } else {
            $list = DataObject::singleton($modelClass)::get()->filter('ParentID', is_numeric($parentID) ? $parentID : 0);
        }
        $gridFieldConfig = GridFieldConfig::create()->addComponents(
            Injector::inst()->create(GridFieldSortableHeader::class),
            Injector::inst()->create(GridFieldDataColumns::class),
            Injector::inst()->createWithArgs(GridFieldPaginator::class, [static::config()->get('page_length')])
        );
        if ($parentID) {
            $linkSpec = $this->LinkListViewChildren('%d');
            $gridFieldConfig->addComponent(
                GridFieldLevelup::create($parentID)
                    ->setLinkSpec($linkSpec)
                    ->setAttributes(['data-pjax-target' => 'ListViewForm,Breadcrumbs'])
            );
            $this->setCurrentRecordID($parentID);
        }
        $gridField = GridField::create('Record', 'Records', $list, $gridFieldConfig);
        $gridField->setAttribute('cms-loading-ignore-url-params', true);
        $columns = $gridField->getConfig()->getComponentByType(GridFieldDataColumns::class);

        // Set up columns and sorting for list view GridField
        $fields = [
            'getTreeTitle' => _t($modelClass . '.TREETITLE', 'Title'),
            'i18n_singular_name' => _t($modelClass . '.TREETYPE', 'Record Type'),
            'LastEdited' => _t($modelClass . '.LASTUPDATED', 'Last Updated'),
        ];
        $sortableHeader = $gridField->getConfig()->getComponentByType(GridFieldSortableHeader::class);
        $sortableHeader->setFieldSorting(['getTreeTitle' => 'Title']);
        $gridField->getState()->ParentID = $parentID;

        if (!$params) {
            $fields = array_merge(['listChildrenLink' => ''], $fields);
        }

        $columns->setDisplayFields($fields);
        $columns->setFieldCasting([
            'Created' => 'DBDatetime->Ago',
            'LastEdited' => 'DBDatetime->FormatFromSettings',
            'getTreeTitle' => 'HTMLFragment'
        ]);

        $columns->setFieldFormatting([
            'listChildrenLink' => function ($value, &$item) {
                /** @var DataObject&Hierarchy $item */
                $num = $item?->numChildren();
                if ($num) {
                    $screenReaderText = _t(__CLASS__ . '.NUM_CHILD_RECORDS', 'one child record|{count} child records', ['count' => $num]);
                    return sprintf(
                        '<a class="btn btn-secondary btn--no-text btn--icon-large font-icon-right-dir cms-panel-link list-children-link" data-pjax-target="ListViewForm,Breadcrumbs" href="%s" aria-label="%s" title="%s"></a>',
                        $this->LinkListViewChildren((int)$item->ID),
                        $screenReaderText,
                        $screenReaderText
                    );
                }
            },
            'getTreeTitle' => function ($value, &$item) {
                /** @var DataObject&Hierarchy $item */
                $title = sprintf(
                    '<a class="action-detail" href="%s">%s</a>',
                    $this->LinkRecordEdit($item->ID),
                    $this->getRecordTreeMarkup($item) // returns HTML, does its own escaping
                );
                $pages = $item->getAncestors();
                $pageNames = [];
                foreach ($pages as $page) {
                    $pageNames[] = $page->MenuTitle ?: $page->Title;
                }
                $breadcrumbs = implode('/', $pageNames);
                if ($breadcrumbs) {
                    $breadcrumbs .= '/';
                }
                return $title . sprintf('<p class="small cms-list__item-breadcrumbs">%s</p>', $breadcrumbs);
            }
        ]);

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
                return $negotiator->respond($request, [
                    'CurrentForm' => function () use ($result) {
                        return $result;
                    }
                ]);
            }
        });

        $this->extend('updateListView', $listview);

        $listview->disableSecurityToken();
        return $listview;
    }

    /**
     * Gets a list of the classes that can be created under this specific record.
     *
     * @return array Array of associative arrays with FQCNs, localised model names, and icon CSS classes.
     */
    protected function getCreatableSubClasses(DataObject $record): array
    {
        // Build the list of candidate children
        $cache = $this->getCreatableChildrenCache();
        $cacheKey = $this->generateChildrenCacheKey();
        $children = [];
        if ($cache !== null) {
            $children = $cache->get($cacheKey, []);
        }
        $modelClass = $this->getModelClass();
        if (!$children || !isset($children[$modelClass][$record->ID])) {
            $children[$modelClass][$record->ID] = [];
            $candidates = $this->getAllowedSubClasses();

            foreach ($candidates as $childClass) {
                $child = DataObject::singleton($childClass);
                if ($child->canCreate(context: ['Parent' => $record])) {
                    $children[$modelClass][$record->ID][] = [
                        'ClassName' => $childClass,
                        'Title' => $child->i18n_singular_name(),
                        'IconClass' => $this->getRecordIconCssClass($child),
                    ];
                }
            }
            if ($cache !== null) {
                $cache->set($cacheKey, $children);
            }
        }

        return $children[$modelClass][$record->ID];
    }

    /**
     * Get all the subclasses of the model class that are allowed to be created through this admin section.
     */
    protected function getAllowedSubClasses(): array
    {
        $modelClass = $this->getModelClass();
        $classes = ClassInfo::getValidSubClasses($modelClass);
        DataObject::singleton($modelClass)->invokeWithExtensions('updateAllowedSubClasses', $classes);
        return $classes;
    }

    //------------------------------------------------------------------------------------------//
    // Data saving handlers

    /**
     * Save and Publish record handler
     *
     * @throws HTTPResponse_Exception
     */
    public function save(array $data, Form $form): HTTPResponse
    {
        $className = $this->getModelClass();

        // Existing or new record?
        $id = $data['ID'];
        if (substr($id ?? '', 0, 3) != 'new') {
            $record = DataObject::get($className)->setUseCache(true)->byID($id);
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
        $isVersioned = $record->hasExtension(Versioned::class);
        if ($isVersioned && $doPublish && !$record->canPublish()) {
            return Security::permissionFailure($this);
        }

        if ($isVersioned && !$record->ObsoleteClassName) {
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

        // If the 'Publish' button was clicked, also publish the record
        if ($doPublish) {
            if (!$record->hasExtension(RecursivePublishable::class)) {
                throw new HTTPResponse_Exception(get_class($record) . ' record is not publishable.', 400);
            }
            $record->publishRecursive();
            $message = _t(
                LeftAndMain::class . '.PUBLISHED_RECORD',
                'Published {name} "{title}"',
                [
                    'name' => $record->i18n_singular_name(),
                    'title' => $record->Title,
                ]
            );
        } else {
            $message = _t(
                LeftAndMain::class . '.SAVED_RECORD',
                'Saved {name} "{title}"',
                [
                    'name' => $record->i18n_singular_name(),
                    'title' => $record->Title,
                ]
            );
        }

        $this->getResponse()->addHeader('X-Status', rawurlencode($message ?? ''));
        return $this->getResponseNegotiator()->respond($this->getRequest());
    }

    /**
     * @param int|string $id
     * @param bool $setID
     * @throws HTTPResponse_Exception
     */
    public function getNewItem($id, $setID = true): DataObject
    {
        $parentClass = $this->getModelClass();
        list(, $className, $parentID) = array_pad(explode('-', $id ?? ''), 3, null);

        if (!is_a($className, $parentClass ?? '', true)) {
            $response = Security::permissionFailure($this);
            if (!$response) {
                $response = $this->getResponse();
            }
            throw new HTTPResponse_Exception($response);
        }

        /** @var DataObject&Hierarchy $newItem */
        $newItem = Injector::inst()->create($className);
        $newItem->Title = _t(
            LeftAndMain::class . '.NEW_RECORD',
            'New {recordtype}',
            ['recordtype' => DataObject::singleton($className)->i18n_singular_name()]
        );
        $newItem->ClassName = $className;
        $newItem->ParentID = $parentID;

        $sortField = $newItem->getSortField();
        if ($sortField) {
            $table = DataObject::singleton($parentClass)->baseTable();
            $maxSort = DB::prepared_query(
                "SELECT MAX(\"$sortField\") FROM \"$table\" WHERE \"ParentID\" = ?",
                [$parentID]
            )->value();
            $newItem->$sortField = (int)$maxSort + 1;
        }

        if ($setID && $id) {
            $newItem->ID = $id;
        }

        # Some modules like subsites add extra fields that need to be set when the new item is created
        $this->extend('updateNewItem', $newItem);

        return $newItem;
    }

    /**
     * Reverts a record by publishing it to live.
     * Use {@link restoreRecord()} if you want to restore a record
     * which was deleted from draft without publishing.
     *
     * @throws HTTPResponse_Exception
     */
    public function revert(array $data, Form $form): HTTPResponse
    {
        $modelClass = $this->getModelClass();
        if (!isset($data['ID'])) {
            throw new HTTPResponse_Exception("Please pass an ID in the form content", 400);
        }

        if (!$modelClass::has_extension(Versioned::class)) {
            throw new HTTPResponse_Exception("$modelClass record cannot be reverted", 400);
        }

        $id = (int) $data['ID'];
        $restoredRecord = Versioned::get_latest_version($modelClass, $id);
        if (!$restoredRecord) {
            throw new HTTPResponse_Exception("Record #$id not found", 400);
        }

        $table = DataObject::singleton($modelClass)->baseTable();
        $liveTable = DataObject::singleton($modelClass)->stageTable($table, Versioned::LIVE);
        $record = Versioned::get_one_by_stage($modelClass, Versioned::LIVE, [
            "\"$liveTable\".\"ID\"" => $id
        ]);

        // a user can restore a record without publication rights, as it just adds a new draft state
        // (this action should just be available when the record has been "deleted from draft")
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
                LeftAndMain::class . '.RESTORED_RECORD',
                'Restored {name} "{title}"',
                [
                    'name' => $record->i18n_singular_name(),
                    'title' => $record->Title,
                ]
            ))
        );

        return $this->getResponseNegotiator()->respond($this->getRequest());
    }

    /**
     * Delete the current record from draft stage.
     *
     * @throws HTTPResponse_Exception
     */
    public function delete(array $data, Form $form): HTTPResponse
    {
        $id = $data['ID'];
        $record = DataObject::get($this->getModelClass())->byID($id);
        if ($record && !$record->canDelete()) {
            return Security::permissionFailure();
        }
        if (!$record || !$record->ID) {
            throw new HTTPResponse_Exception("Bad record ID #$id", 404);
        }

        // Delete record
        $record->delete();

        if ($record->hasExtension(Versioned::class) && $record->hasStages()) {
            $message = _t(
                LeftAndMain::class . '.ARCHIVED_RECORD',
                'Archived {name} "{title}"',
                [
                    'name' => $record->i18n_singular_name(),
                    'title' => $record->Title,
                ]
            );
        } else {
            $message = _t(
                LeftAndMain::class . '.DELETED_RECORD',
                'Deleted {name} "{title}"',
                [
                    'name' => $record->i18n_singular_name(),
                    'title' => $record->Title,
                ]
            );
        }
        $this->getResponse()->addHeader(
            'X-Status',
            rawurlencode($message)
        );

        // Even if the record has been deleted from stage and live, it can be viewed in "archive mode"
        return $this->getResponseNegotiator()->respond($this->getRequest());
    }

    /**
     * Delete this record from both live and stage
     *
     * @throws HTTPResponse_Exception
     */
    public function archive(array $data, Form $form): HTTPResponse
    {
        $id = $data['ID'];
        $record = DataObject::get($this->getModelClass())->byID($id);
        if (!$record || !$record->exists()) {
            throw new HTTPResponse_Exception("Bad record ID #$id", 404);
        }
        if (!$record->hasExtension(Versioned::class)) {
            throw new HTTPResponse_Exception(get_class($record) . ' record cannot be archived.', 400);
        }
        if (!$record->canDelete()) {
            return Security::permissionFailure();
        }

        // Archive record
        $record->doArchive();

        $this->getResponse()->addHeader(
            'X-Status',
            rawurlencode(_t(
                LeftAndMain::class . '.ARCHIVED_RECORD',
                'Archived {name} "{title}"',
                [
                    'name' => $record->i18n_singular_name(),
                    'title' => $record->Title,
                ]
            ))
        );

        // Even if the record has been deleted from stage and live, it can be viewed in "archive mode"
        return $this->getResponseNegotiator()->respond($this->getRequest());
    }

    public function publish(array $data, Form $form): HTTPResponse
    {
        $data['publish'] = '1';

        return $this->save($data, $form);
    }

    public function unpublish(array $data, Form $form): HTTPResponse
    {
        $className = $this->getModelClass();
        $record = DataObject::get($className)->setUseCache(true)->byID($data['ID']);

        if (!$record->hasExtension(Versioned::class)) {
            throw new HTTPResponse_Exception(get_class($record) . ' record cannot be unpublished.', 400);
        }

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
                LeftAndMain::class . '.UNPUBLISHED_RECORD',
                'Unpublished {name} "{title}"',
                [
                    'name' => $record->i18n_singular_name(),
                    'title' => $record->Title,
                ]
            ))
        );

        return $this->getResponseNegotiator()->respond($this->getRequest());
    }

    /**
     * @return HTTPResponse
     */
    public function rollback()
    {
        return $this->doRollback([
            'ID' => $this->currentRecordID(),
            'Version' => $this->getRequest()->param('VersionID')
        ], null);
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

        $modelClass = $this->getModelClass();
        if (!$modelClass::has_extension(Versioned::class)) {
            throw new HTTPResponse_Exception("$modelClass record cannot be rolled back", 400);
        }

        /** @var DataObject&Versioned $record */
        $record = Versioned::get_latest_version($modelClass, $id);
        if ($record && !$record->canEdit()) {
            return Security::permissionFailure($this);
        }

        if ($version) {
            $record->rollbackRecursive($version);
            $message = _t(
                LeftAndMain::class . '.ROLLEDBACK_VERSION',
                'Rolled back to version #{version}.',
                ['version' => $data['Version']]
            );
        } else {
            $record->doRevertToLive();
            $record->publishRecursive();
            $message = _t(
                LeftAndMain::class . '.ROLLEDBACK_PUBLISHED',
                'Rolled back to published version.'
            );
        }

        $this->getResponse()->addHeader('X-Status', rawurlencode($message ?? ''));

        // Can be used in different contexts: In normal record edit view, in which case the redirect won't have any effect.
        // Or in history view, in which case a revert causes the CMS to re-load the edit view.
        // The X-Pjax header forces a "full" content refresh on redirect.
        $url = $this->LinkRecordEdit($record->ID);
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

    /**
     * Returns a LiteralField containing parameter field HTML
     * for batch actions
     *
     * Used by {@link LeftAndMain} to render batch actions in
     * the BatchActionsForm
     *
     * @return LiteralField
     */
    public function BatchActionParameters()
    {
        $batchActions = $this->batchactions()->registeredActions();

        $forms = [];
        foreach ($batchActions as $urlSegment => $batchAction) {
            $SNG_action = singleton($batchAction['class']);
            if ($SNG_action->canView() && $fieldList = $SNG_action->getParameterFields()) {
                $formHtml = '';
                /** @var FormField $field */
                foreach ($fieldList as $field) {
                    $formHtml .= $field->FieldHolder();
                }
                $forms[$urlSegment] = $formHtml;
            }
        }
        $recordHtml = '';
        foreach ($forms as $urlSegment => $html) {
            $recordHtml .= '<div class="params" id="BatchActionParameters_' . $urlSegment . '" style="display:none">' . $html . '</div>';
        }
        return new LiteralField('BatchActionParameters', '<div id="BatchActionParameters" class="action-parameters" style="display:none">' . $recordHtml . '</div>');
    }

    /**
     * Returns a list of batch actions
     */
    public function BatchActionList()
    {
        return $this->batchactions()->batchActionList();
    }

    /**
     * Restore a completely deleted record from the *_versions table.
     */
    public function restore(array $data, Form $form): HTTPResponse
    {
        if (!isset($data['ID']) || !is_numeric($data['ID'])) {
            return new HTTPResponse("Please pass an ID in the form content", 400);
        }

        $modelClass = $this->getModelClass();
        if (!$modelClass::has_extension(Versioned::class)) {
            throw new HTTPResponse_Exception("$modelClass record cannot be restored", 400);
        }

        $id = (int)$data['ID'];
        $restoredRecord = Versioned::get_latest_version($modelClass, $id);
        if (!$restoredRecord) {
            return new HTTPResponse("Record #$id not found", 400);
        }

        $restoredRecord = $restoredRecord->doRestoreToStage();

        $this->getResponse()->addHeader(
            'X-Status',
            rawurlencode(_t(
                LeftAndMain::class . '.RESTORED_RECORD',
                'Restored {name} "{title}"',
                [
                    'name' => $restoredRecord->i18n_singular_name(),
                    'title' => $restoredRecord->Title,
                ]
            ))
        );

        return $this->getResponseNegotiator()->respond($this->getRequest());
    }

    public function duplicate(HTTPRequest $request): HTTPResponse
    {
        // Protect against CSRF on destructive action
        if (!SecurityToken::inst()->checkRequest($request)) {
            return $this->httpError(400);
        }

        if (($id = $this->urlParams['ID']) && is_numeric($id)) {
            $modelClass = $this->getModelClass();
            /** @var DataObject&Hierarchy $record */
            $record = DataObject::get($modelClass)->byID($id);
            if ($record && !$record->canCreate(null, ['Parent' => $record->getParent() ?? $modelClass::create()])) {
                return Security::permissionFailure($this);
            }
            if (!$record || !$record->ID) {
                throw new HTTPResponse_Exception("Bad record ID #$id", 404);
            }

            $newRecord = $record->duplicate();

            // ParentID can be hard-set in the URL.  This is useful for pages with multiple parents
            if (isset($_GET['parentID']) && is_numeric($_GET['parentID'])) {
                $newRecord->ParentID = $_GET['parentID'];
                $newRecord->write();
            }

            $this->getResponse()->addHeader(
                'X-Status',
                rawurlencode(_t(
                    LeftAndMain::class . '.DUPLICATED_RECORD',
                    'Duplicated {name} "{title}"',
                    [
                        'name' => $newRecord->i18n_singular_name(),
                        'title' => $newRecord->Title,
                    ]
                ))
            );
            $url = $this->LinkRecordEdit($newRecord->ID);
            $this->getResponse()->addHeader('X-ControllerURL', $url);
            $this->getRequest()->addHeader('X-Pjax', 'Content');
            $this->getResponse()->addHeader('X-Pjax', 'Content');

            return $this->getResponseNegotiator()->respond($this->getRequest());
        }
        return new HTTPResponse("CMSMain::duplicate() Bad ID: '$id'", 400);
    }

    public function duplicatewithchildren(HTTPRequest $request): HTTPResponse
    {
        // Protect against CSRF on destructive action
        if (!SecurityToken::inst()->checkRequest($request)) {
            $this->httpError(400);
        }
        Environment::increaseTimeLimitTo();
        if (($id = $this->urlParams['ID']) && is_numeric($id)) {
            $modelClass = $this->getModelClass();
            /** @var DataObject&Hierarchy $record */
            $record = DataObject::get($modelClass)->byID($id);
            if ($record && !$record->canCreate(null, ['Parent' => $record->getParent() ?? $modelClass::create()])) {
                return Security::permissionFailure($this);
            }
            if (!$record || !$record->ID) {
                throw new HTTPResponse_Exception("Bad record ID #$id", 404);
            }

            $newRecord = $record->duplicateWithChildren();

            $this->getResponse()->addHeader(
                'X-Status',
                rawurlencode(_t(
                    LeftAndMain::class . '.DUPLICATED_RECORD_WITH_CHILDREN',
                    'Duplicated {name} "{title}" and children',
                    [
                        'name' => $newRecord->i18n_singular_name(),
                        'title' => $newRecord->Title,
                    ]
                ) ?? '')
            );
            $url = $this->LinkRecordEdit($newRecord->ID);
            $this->getResponse()->addHeader('X-ControllerURL', $url);
            $this->getRequest()->addHeader('X-Pjax', 'Content');
            $this->getResponse()->addHeader('X-Pjax', 'Content');

            return $this->getResponseNegotiator()->respond($this->getRequest());
        }
        return new HTTPResponse("CMSMain::duplicatewithchildren() Bad ID: '$id'", 400);
    }

    public function providePermissions()
    {
        $title = CMSMain::menu_title();
        return [
            "CMS_ACCESS_CMSMain" => [
                'name' => _t(LeftAndMain::class . '.ACCESS', "Access to '{title}' section", ['title' => $title]),
                'category' => _t(LeftAndMain::class . '.CMS_ACCESS_CATEGORY', 'CMS Access'),
                'help' => _t(
                    __CLASS__ . '.ACCESS_HELP',
                    'Allow viewing of the section containing page tree and content. View and edit permissions can be handled through page specific dropdowns, as well as the separate "Content permissions".'
                ),
                'sort' => -99 // below "CMS_ACCESS_LeftAndMain", but above everything else
            ]
        ];
    }

    /**
     * Get the FQCN of the class to use when creating new records
     */
    public function getDefaultModelClass()
    {
        $modelClass = $this->getModelClass();
        $default = Config::forClass($modelClass)->get('default_classname');
        if ($default && class_exists($default)) {
            return $default;
        }
        return $modelClass;
    }

    /**
     * Get title for root CMS node
     *
     * @return string
     */
    public function getCMSTreeTitle()
    {
        $rootTitle = SiteConfig::current_site_config()->Title;
        $this->extend('updateCMSTreeTitle', $rootTitle);
        return $rootTitle;
    }

    /**
     * Cache key for TreeHints() method
     *
     * @param $memberID
     * @return string
     */
    protected function generateHintsCacheKey($memberID)
    {
        $baseKey = $memberID . '_' . __CLASS__;
        $this->extend('updateHintsCacheKey', $baseKey);
        return md5($baseKey ?? '');
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
        $hintsCache = $this->getHintsCache();
        $childrenCache = $this->getCreatableChildrenCache();

        if (!$memberIDs) {
            $hintsCache->clear();
            $childrenCache->clear();
            return;
        }

        foreach ($memberIDs as $memberID) {
            $hintsKey = $this->generateHintsCacheKey($memberID);
            $hintsCache->delete($hintsKey);
            $childrenKey = $this->generateChildrenCacheKey($memberID);
            $childrenCache->delete($childrenKey);
        }
    }

    private function generateChildrenCacheKey(?int $memberID = null)
    {
        if ($memberID === null) {
            $memberID = Security::getCurrentUser() ? Security::getCurrentUser()->ID : 0;
        }
        return md5($memberID . '_' . __CLASS__);
    }
}
