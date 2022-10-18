<?php

namespace SilverStripe\CMS\BatchActions;

use SilverStripe\Admin\CMSBatchAction;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\SS_List;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Security\Permission;

/**
 * Batch restore of pages
 */
class CMSBatchAction_Restore extends CMSBatchAction
{

    public function getActionTitle()
    {
        return _t(__CLASS__ . '.RESTORE', 'Restore');
    }

    public function run(SS_List $pages): HTTPResponse
    {
        // Sort pages by depth
        $pageArray = $pages->toArray();
        // because of https://bugs.php.net/bug.php?id=50688
        /** @var SiteTree $page */
        foreach ($pageArray as $page) {
            $page->getPageLevel();
        }
        usort($pageArray, function (SiteTree $a, SiteTree $b) {
            return $a->getPageLevel() - $b->getPageLevel();
        });
        $pages = new ArrayList($pageArray);

        // Restore
        return $this->batchaction(
            $pages,
            'doRestoreToStage',
            _t(__CLASS__ . '.RESTORED_PAGES', 'Restored %d pages')
        );
    }

    /**
     * {@see SiteTree::canEdit()}
     *
     * @param array $ids
     * @return array
     */
    public function applicablePages($ids)
    {
        // Basic permission check based on SiteTree::canEdit
        if (!Permission::check(["ADMIN", "SITETREE_EDIT_ALL"])) {
            return [];
        }

        // Get pages that exist in stage and remove them from the restore-able set
        $stageIDs = Versioned::get_by_stage($this->managedClass, Versioned::DRAFT)->column('ID');
        return array_values(array_diff($ids ?? [], $stageIDs));
    }
}
