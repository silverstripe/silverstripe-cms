<?php

namespace SilverStripe\CMS\BatchActions;

use SilverStripe\Admin\CMSBatchAction;
use SilverStripe\CMS\Controllers\CMSMain;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Model\List\SS_List;
use SilverStripe\ORM\DataObject;

/**
 * Publish items batch action.
 */
class CMSBatchAction_Publish extends CMSBatchAction
{
    public function getActionTitle()
    {
        return _t(__CLASS__ . '.PUBLISH_PAGES', 'Publish');
    }

    public function run(SS_List $pages): HTTPResponse
    {
        $status = ['modified' => [], 'error' => [], 'deleted' => [], 'success' => []];
        foreach ($pages as $page) {
            $id = $page->ID;
            if ($page->canPublishRecursive() && $page->publishRecursive()) {
                $status['success'][$id] = $id;
            } else {
                $status['error'][$id] = $id;
            }
            $record = DataObject::get($this->managedClass)->setUseCache(true)->byID($id);
            if ($record) {
                $status['modified'][$id] = ['TreeTitle' => CMSMain::singleton()->getRecordTreeMarkup($record)];
            } else {
                $status['deleted'][$id] = $id;
            }
            $page->destroy();
        }
        return $this->response(_t(__CLASS__ . '.PUBLISHED_PAGES', 'Published %d pages, %d failures'), $status);
    }

    public function applicablePages($ids)
    {
        return $this->applicablePagesHelper($ids, 'canPublish', true, false);
    }
}
