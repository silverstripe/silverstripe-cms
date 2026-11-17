<?php

namespace SilverStripe\CMS\Controllers;

use SilverStripe\Admin\AdminController;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Hierarchy\Hierarchy;

/**
 * This controller serves JSON endpoints at admin/pages/tree that read the page tree of CMSMain.
 *
 * A client builds the URLs from Link().
 *
 * The actions end a failed request with a JSON error from AdminController::jsonError():
 * - 400 for an ID that is missing or not a whole number, and for the path of the top level.
 * - 404 for a record that does not exist, or for a request without an action.
 * - 405 for a request to the apiReadChildren or apiReadPath action URL that is not GET.
 *
 * A failed access check gets the response of Security::permissionFailure(), as in other admin controllers, and not a
 * JSON error. A request to the `children` or `path` route that is not GET matches no action, so the request gets an
 * error without a JSON body.
 *
 * @internal The React tree view in admin is the only supported client. The routes and the responses can change in a
 * minor release.
 */
class PageTreeController extends AdminController
{
    private static string $url_segment = 'pages/tree';

    private static array $url_handlers = [
        'GET children/$ParentID' => 'apiReadChildren',
        'GET path/$ID' => 'apiReadPath',
    ];

    private static array $allowed_actions = [
        'apiReadChildren',
        'apiReadPath',
    ];

    private ?CMSMain $cmsMain = null;

    /**
     * Respond to a request without an action with a 404 JSON error.
     */
    public function index(HTTPRequest $request): void
    {
        $this->jsonError(404);
    }

    /**
     * Respond to `children/{parentID}` with one level of the tree: the child nodes of a record.
     *
     * A parent ID of 0 gives the top level. For a parent with too many children to list, this method responds with no
     * nodes and `limited` set to true.
     *
     * This method also lists the children that the member cannot view, as the legacy tree does. The member can then
     * reach a viewable page below them. TreeView shows these nodes as disabled, from their `canView` value.
     *
     * An extension changes the fields of a node in
     * updateTreeNodeData(array &$data, DataObject $node, DataObject $parent).
     */
    public function apiReadChildren(HTTPRequest $request): HTTPResponse
    {
        $this->requireGetRequest($request);
        $parentID = $this->getTreeRequestID($request, 'ParentID');
        // The top level uses a new record, not DataObject::singleton(), because the updateTreeNodeData hook gets the
        // parent. A change that an extension makes to the singleton would reach every other caller of
        // DataObject::singleton().
        $parent = $parentID ? $this->getTreeRecord($parentID) : $this->getModelClass()::create();
        if ($this->isTreeNodeLimited($parent)) {
            return $this->jsonSuccess(200, ['nodes' => [], 'limited' => true]);
        }

        /** @var DataObject&Hierarchy $parent */
        $childrenMethod = $parent->getHierarchyBaseClass()::config()->get('tree_children_method');
        $children = $parent->$childrenMethod();
        // Hierarchy::showingCMSTree() recognises only the tree actions of LeftAndMain, so the children method keeps the
        // hide_from_cms_tree classes and this method excludes them.
        $hiddenClasses = $parent->config()->get('hide_from_cms_tree');
        if ($hiddenClasses) {
            $children = $children->exclude('ClassName', $hiddenClasses);
        }

        $nodes = [];
        foreach ($children as $child) {
            $data = $this->getTreeNodeData($child, $parent);
            $this->extend('updateTreeNodeData', $data, $child, $parent);
            $nodes[] = $data;
        }
        return $this->jsonSuccess(200, ['nodes' => $nodes, 'limited' => false]);
    }

    /**
     * Respond to `path/{id}` with the IDs of the ancestors of a record, top level first.
     *
     * An ID of 0 gets a 400 error, because the top level has no path. Like apiReadChildren(), this method responds for
     * a record that the member cannot view.
     */
    public function apiReadPath(HTTPRequest $request): HTTPResponse
    {
        $this->requireGetRequest($request);
        $id = $this->getTreeRequestID($request, 'ID');
        if ($id === 0) {
            $this->jsonError(400);
        }
        /** @var DataObject&Hierarchy $record */
        $record = $this->getTreeRecord($id);
        $ancestorIDs = array_map('intval', $record->getAncestors()->column('ID'));
        return $this->jsonSuccess(200, ['path' => array_reverse($ancestorIDs)]);
    }

    /**
     * Check access as CMSMain does, so the permission codes of CMSMain and an alternateAccessCheck extension on CMSMain
     * apply to the tree too.
     */
    public function canView($member = null)
    {
        return $this->getCMSMain()->canView($member);
    }

    /**
     * Get the fields of a node.
     *
     * The `fetchChildren` prop of the TreeView component in admin lists the fields that the component consumes.
     *
     * - `statusFlags` is a list of `{key, text, title}`. The `title` of a flag can be null.
     * - `hasChildren` is true when the record has children that the tree lists.
     * - `limited` is true when the record has too many children to list.
     *
     * @param DataObject $parent The record whose children the request reads, or a new record at the top level.
     */
    private function getTreeNodeData(DataObject $node, DataObject $parent): array
    {
        /** @var DataObject&Hierarchy $node */
        $cmsMain = $this->getCMSMain();
        $hasChildren = $this->countTreeChildren($node) > 0;
        return [
            'id' => (int) $node->ID,
            'parentId' => (int) $node->ParentID,
            'treeTitle' => $this->getTreeTitleText($node),
            'tooltip' => _t(
                CMSMain::class . '.RECORD_TYPE_TITLE',
                '(Record type: {type}) {title}',
                [
                    'type' => $node->i18n_singular_name(),
                    'title' => $node->Title,
                ]
            ),
            'recordClass' => $node->ClassName,
            'classes' => $cmsMain->getTreeNodeClasses($node),
            'iconClasses' => implode(' ', array_filter($cmsMain->getRecordTreeIconClasses($node))),
            'statusFlags' => $this->getTreeStatusFlags($node),
            'hasChildren' => $hasChildren,
            'limited' => $this->isTreeNodeLimited($node),
            'listViewUrl' => $hasChildren ? $cmsMain->LinkListViewChildren($node->ID) : null,
            'editUrl' => $cmsMain->LinkRecordEdit($node->ID),
            'canView' => (bool) $node->canView(),
            'canEdit' => (bool) $node->canEdit(),
            'canAddChildren' => (bool) $node->canAddChildren(),
            // CMSMain::duplicate() and CMSMain::duplicatewithchildren() make the same check.
            'canDuplicate' => (bool) $node->canCreate(null, ['Parent' => $parent]),
        ];
    }

    /**
     * Get the class of the records in the tree, from CMSMain.
     *
     * @return class-string<DataObject>
     */
    private function getModelClass(): string
    {
        return $this->getCMSMain()->getModelClass();
    }

    /**
     * Get the CMSMain that gives this controller the access check, the links and the classes.
     *
     * The CMSMain gets the request of this controller, so CMSMain::LinkWithSearch() keeps the search of the tree
     * request in the edit links.
     */
    private function getCMSMain(): CMSMain
    {
        if (!$this->cmsMain) {
            $this->cmsMain = CMSMain::create();
            $this->cmsMain->setRequest($this->getRequest());
        }
        return $this->cmsMain;
    }

    /**
     * End the request with a 405 JSON error unless it is a GET request.
     *
     * The default action route of RequestHandler reaches apiReadChildren() and apiReadPath() with any HTTP method, so
     * the GET routes in url_handlers do not limit them.
     */
    private function requireGetRequest(HTTPRequest $request): void
    {
        if (!$request->isGET()) {
            $this->jsonError(405);
        }
    }

    /**
     * Get an ID from the URL, or end the request with a 400 JSON error for an ID that is not a whole number.
     */
    private function getTreeRequestID(HTTPRequest $request, string $name): int
    {
        $value = $request->param($name);
        if (!is_string($value) || !ctype_digit($value)) {
            $this->jsonError(400);
        }
        return (int) $value;
    }

    /**
     * Get a record in the current stage, or end the request with a 404 JSON error.
     */
    private function getTreeRecord(int $id): DataObject
    {
        $record = DataObject::get($this->getModelClass())->byID($id);
        if (!$record) {
            $this->jsonError(404);
        }
        return $record;
    }

    /**
     * Check whether a node has too many children to list.
     *
     * The limit is the node_threshold_leaf of the node, so a page type can set its own limit.
     * MarkedSet::getMaxChildNodes() reads node_threshold_leaf from the tree root instead.
     */
    private function isTreeNodeLimited(DataObject $node): bool
    {
        $isTopLevel = !$node->ID;
        if ($isTopLevel) {
            return false;
        }
        $threshold = $node->config()->get('node_threshold_leaf');
        return $this->countTreeChildren($node) > $threshold;
    }

    /**
     * Count the children of a record that the tree lists.
     *
     * Hierarchy::numChildren() leaves out the hide_from_cms_tree classes only in the tree actions of LeftAndMain.
     * For a record with hidden classes, this method counts the children without those classes instead.
     */
    private function countTreeChildren(DataObject $node): int
    {
        /** @var DataObject&Hierarchy $node */
        $hiddenClasses = $node->config()->get('hide_from_cms_tree');
        if (!$hiddenClasses) {
            return $node->numChildren();
        }
        return $node->stageChildren(true)->exclude('ClassName', $hiddenClasses)->count();
    }

    /**
     * Get the tree title as plain text.
     *
     * getTreeTitle() can return escaped text or markup.
     */
    private function getTreeTitleText(DataObject $node): string
    {
        /** @var DataObject&Hierarchy $node */
        $title = Convert::xml2raw(strip_tags($node->getTreeTitle()));
        $title = trim(str_replace(["\n", "\r"], '', $title));
        if ($title === '') {
            return _t(CMSMain::class . '.TREE_NO_TITLE', '(no title)');
        }
        return $title;
    }

    private function getTreeStatusFlags(DataObject $node): array
    {
        $flags = [];
        foreach ($node->getStatusFlags() as $key => $flag) {
            if (is_string($flag)) {
                $flag = ['text' => $flag];
            }
            $flags[] = [
                'key' => (string) $key,
                'text' => $flag['text'],
                'title' => $flag['title'] ?? null,
            ];
        }
        return $flags;
    }
}
