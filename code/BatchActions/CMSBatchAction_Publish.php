<?php

namespace SilverStripe\CMS\BatchActions;

use SilverStripe\Admin\CMSBatchAction;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\ORM\SS_List;

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
        return $this->batchaction(
            $pages,
            'publishRecursive',
            _t(__CLASS__ . '.PUBLISHED_PAGES', 'Published %d pages, %d failures')
        );
    }

    public function applicablePages($ids)
    {
        return $this->applicablePagesHelper($ids, 'canPublish', true, false);
    }
}
