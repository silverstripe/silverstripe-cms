<?php

namespace SilverStripe\CMS\BatchActions;

use SilverStripe\Admin\CMSBatchAction;
use SilverStripe\ORM\SS_List;

/**
 * Unpublish items batch action.
 */
class CMSBatchAction_Unpublish extends CMSBatchAction
{
    public function getActionTitle()
    {
        return _t('CMSBatchActions.UNPUBLISH_PAGES', 'Unpublish');
    }

    public function run(SS_List $pages)
    {
        return $this->batchaction($pages, 'doUnpublish',
            _t('CMSBatchActions.UNPUBLISHED_PAGES', 'Unpublished %d pages')
        );
    }

    public function applicablePages($ids)
    {
        return $this->applicablePagesHelper($ids, 'canUnpublish', false, true);
    }
}
