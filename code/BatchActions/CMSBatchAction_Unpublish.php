<?php

namespace SilverStripe\CMS\BatchActions;

use SilverStripe\Admin\CMSBatchAction;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\ORM\SS_List;

/**
 * Unpublish items batch action.
 */
class CMSBatchAction_Unpublish extends CMSBatchAction
{
    public function getActionTitle()
    {
        return _t(__CLASS__ . '.UNPUBLISH_PAGES', 'Unpublish');
    }

    public function run(SS_List $pages): HTTPResponse
    {
        return $this->batchaction(
            $pages,
            'doUnpublish',
            _t(__CLASS__ . '.UNPUBLISHED_PAGES', 'Unpublished %d pages')
        );
    }

    public function applicablePages($ids)
    {
        return $this->applicablePagesHelper($ids, 'canUnpublish', false, true);
    }
}
