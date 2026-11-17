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
 * @internal The React tree view in admin is the only supported client. The routes and the responses can change in a
 * minor release.
 */
class PageTreeController extends AdminController
{
    private static string $url_segment = 'pages/tree';

    /**
     * The routes match every HTTP method, so a request that is not GET gets a JSON 405.
     */
    private static array $url_handlers = [
        'children/$ItemID' => 'apiReadChildren',
        '$ItemID!' => 'apiRead',
    ];

    private static array $allowed_actions = [
        'apiReadChildren',
        'apiRead',
    ];

    private ?CMSMain $cmsMain = null;

    public function index(HTTPRequest $request): void
    {
        $this->jsonError(404);
    }

    /**
     * Respond with one level of the tree: the child nodes of a record. An ID of 0 gives the top level.
     *
     * This method also lists the children that the member cannot view, so the member can reach a viewable page below
     * them.
     */
    public function apiReadChildren(HTTPRequest $request): HTTPResponse
    {
        $this->requireGetRequest($request);
        $parentID = $this->getRequestID($request);
        $parent = $parentID ? $this->getRecordByID($parentID) : $this->getModelClass()::create();
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
            $nodes[] = $this->getTreeNodeData($child, $parent);
        }
        return $this->jsonSuccess(200, ['nodes' => $nodes, 'limited' => false]);
    }

    /**
     * Respond with the node of a record and the IDs of the ancestors of the record, top level first.
     */
    public function apiRead(HTTPRequest $request): HTTPResponse
    {
        $this->requireGetRequest($request);
        /** @var DataObject&Hierarchy $record */
        $record = $this->getRecordByID($this->getRequestID($request));
        $parent = DataObject::get($this->getModelClass())->byID($record->ParentID) ?? $this->getModelClass()::create();
        $ancestorIDs = array_map('intval', $record->getAncestors()->column('ID'));
        return $this->jsonSuccess(200, [
            'node' => $this->getTreeNodeData($record, $parent),
            'path' => array_reverse($ancestorIDs),
        ]);
    }

    /**
     * Check access as CMSMain does, so the permission codes of CMSMain and an alternateAccessCheck extension on CMSMain
     * apply to the tree too.
     */
    public function canView($member = null): bool
    {
        return $this->getCMSMain()->canView($member);
    }

    /**
     * Get the fields of a node. An extension changes the fields in updateTreeNodeData().
     *
     * A node at the top level has no parent, so $parent is an unsaved record.
     */
    private function getTreeNodeData(DataObject $node, DataObject $parent): array
    {
        /** @var DataObject&Hierarchy $node */
        $cmsMain = $this->getCMSMain();
        $hasChildren = $this->getTreeChildrenCount($node) > 0;
        $data = [
            'id' => (int) $node->ID,
            'parentId' => (int) $node->ParentID,
            'treeTitle' => $this->getTreeNodeTitle($node),
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
            'iconClasses' => $this->getTreeNodeIconClasses($node),
            'statusFlags' => $this->getTreeNodeStatusFlags($node),
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
        $this->extend('updateTreeNodeData', $data, $node, $parent);
        return $data;
    }

    /**
     * @return class-string<DataObject>
     */
    private function getModelClass(): string
    {
        return $this->getCMSMain()->getModelClass();
    }

    /**
     * Get a CMSMain with the request of this controller, so the edit links keep the search of the request.
     */
    private function getCMSMain(): CMSMain
    {
        if (!$this->cmsMain) {
            $this->cmsMain = CMSMain::create();
            $this->cmsMain->setRequest($this->getRequest());
        }
        return $this->cmsMain;
    }

    private function requireGetRequest(HTTPRequest $request): void
    {
        if (!$request->isGET()) {
            $this->jsonError(405);
        }
    }

    private function getRequestID(HTTPRequest $request): int
    {
        $value = $request->param('ItemID');
        if (!is_string($value) || !ctype_digit($value)) {
            $this->jsonError(400);
        }
        return (int) $value;
    }

    private function getRecordByID(int $id): DataObject
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
     */
    private function isTreeNodeLimited(DataObject $node): bool
    {
        $isTopLevel = !$node->ID;
        if ($isTopLevel) {
            return false;
        }
        $threshold = $node->config()->get('node_threshold_leaf');
        return $this->getTreeChildrenCount($node) > $threshold;
    }

    /**
     * Count the children of a record that the tree lists.
     *
     * Hierarchy::numChildren() leaves out the hide_from_cms_tree classes only in the tree actions of LeftAndMain.
     */
    private function getTreeChildrenCount(DataObject $node): int
    {
        /** @var DataObject&Hierarchy $node */
        $hiddenClasses = $node->config()->get('hide_from_cms_tree');
        if (!$hiddenClasses) {
            return $node->numChildren();
        }
        return $node->stageChildren(true)->exclude('ClassName', $hiddenClasses)->count();
    }

    /**
     * Get the tree title as plain text, because getTreeTitle() can return escaped text or markup.
     */
    private function getTreeNodeTitle(DataObject $node): string
    {
        /** @var DataObject&Hierarchy $node */
        $title = Convert::xml2raw(strip_tags($node->getTreeTitle()));
        $title = trim(str_replace(["\n", "\r"], '', $title));
        if ($title === '') {
            return _t(CMSMain::class . '.TREE_NO_TITLE', '(no title)');
        }
        return $title;
    }

    /**
     * Get the icon classes of a node, as CMSMain::getRecordTreeMarkup() builds them without the legacy tree class.
     */
    private function getTreeNodeIconClasses(DataObject $node): string
    {
        $iconClasses = [
            'record-icon',
            $this->getCMSMain()->getRecordIconCssClass($node),
            'class-' . Convert::raw2htmlid(get_class($node)),
        ];
        $node->invokeWithExtensions('updateTreeIconClasses', $iconClasses);
        return implode(' ', array_filter($iconClasses));
    }

    private function getTreeNodeStatusFlags(DataObject $node): array
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
