<?php

namespace SilverStripe\CMS\Controllers;

use SilverStripe\Admin\Forms\ComplexTreeView;
use SilverStripe\Admin\HierarchyTreeController;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\DataObject;

/**
 * Controller for serving JSON tree data for the CMS page tree.
 *
 * Provides endpoints for:
 * - jsonview: Fetch tree data as JSON
 * - savejsonnode: Save node position after drag-and-drop
 * - updatejsonnodes: Refresh node data
 * - duplicate: Duplicate a page without children
 * - duplicateWithChildren: Duplicate a page with all children
 */
class CMSSiteTreeController extends HierarchyTreeController
{
    private static string $url_segment = 'pages/tree';

    private static string $model_class = SiteTree::class;

    /**
     * Create and configure a ComplexTreeView schema provider with all endpoints
     *
     * @param int|null $currentRecordID The currently selected record ID
     * @return ComplexTreeView
     */
    public function getComplexTreeView(?int $currentRecordID = 0): ComplexTreeView
    {
        return ComplexTreeView::create()
            ->setApiEndpoint($this->getApiEndpoint())
            ->setDuplicateEndpoint($this->getDuplicateEndpoint())
            ->setDuplicateWithChildrenEndpoint($this->getDuplicateWithChildrenEndpoint())
            ->setAddChildEndpoint($this->getAddChildEndpoint())
            ->setSaveNodeEndpoint($this->getSaveNodeEndpoint())
            ->setUpdateNodesEndpoint($this->getUpdateNodesEndpoint())
            ->setEditUrlPattern('/admin/pages/edit/show/%s')
            ->setListUrlPattern('/admin/pages?ParentID=%s')
            ->setTreeLabel('Page Tree')
            ->setLabels([
                'edit' => _t(__CLASS__ . '.VIEW', 'View'),
                'showAsList' => _t(__CLASS__ . '.SHOW_AS_LIST', 'Show children as list'),
                'addChild' => _t(__CLASS__ . '.ADD_PAGE_UNDER', 'Add page under this page'),
                'duplicate' => _t(__CLASS__ . '.DUPLICATE', 'Duplicate'),
                'duplicateThisOnly' => _t(__CLASS__ . '.DUPLICATE_THIS_ONLY', 'This page only'),
                'duplicateWithChildren' => _t(__CLASS__ . '.DUPLICATE_WITH_CHILDREN', 'This page and subpages'),
            ])
            ->setEnableDefaultNavigationHandlers(false)
            ->setCurrentRecordID($currentRecordID ?? 0);
    }

    /**
     * Transform a subtree array to JSON format with context menu data for CMS
     *
     * @param array $data Subtree data from MarkedSet::getSubtree()
     * @return array Transformed tree data for JSON response
     */
    protected function transformSubtree(array $data): array
    {
        $node = $data['node'];
        $output = parent::transformSubtree($data);
        $output['contextMenuData'] = [
            'canEdit' => (bool) $node->canEdit(),
            'canCreate' => (bool) $node->canCreate(),
            'canDelete' => (bool) $node->canDelete(),
            'numChildren' => $data['count'],
            'allowedChildren' => $this->getCreatableChildrenForNode($node),
        ];
        return $output;
    }

    /**
     * Get the list of creatable child types for a node.
     *
     * Returns array of child classes that can be created under this node,
     * filtered by canCreate permission for the context of this parent.
     * Applies updateAllowedSubClasses extension hook to filter out base classes.
     *
     * @param DataObject $node The parent node
     * @return array Array with keys: ClassName, Title, IconClass
     */
    private function getCreatableChildrenForNode(DataObject $node): array
    {
        $modelClass = $this->getModelClass();
        $creatable = [];
        $subclasses = ClassInfo::getValidSubClasses($modelClass);
        DataObject::singleton($modelClass)->invokeWithExtensions('updateAllowedSubClasses', $subclasses);
        foreach ($subclasses as $class) {
            $instance = DataObject::singleton($class);
            if ($instance->canCreate(context: ['Parent' => $node])) {
                $creatable[] = [
                    'ClassName' => $class,
                    'Title' => $instance->i18n_singular_name(),
                    'IconClass' => $instance->config()->get('icon'),
                ];
            }
        }
        return $creatable;
    }

    private function getAddChildEndpoint(): string
    {
        return '/admin/pages/add';
    }
}
