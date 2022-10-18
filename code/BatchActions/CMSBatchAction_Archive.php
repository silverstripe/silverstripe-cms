<?php

namespace SilverStripe\CMS\BatchActions;

use SilverStripe\ORM\SS_List;
use SilverStripe\Admin\CMSBatchAction;
use SilverStripe\Control\HTTPResponse;

/**
 * Delete items batch action.
 */
class CMSBatchAction_Archive extends CMSBatchAction
{
    public function getActionTitle()
    {
        return _t(__CLASS__ . '.TITLE', 'Unpublish and archive');
    }

    public function run(SS_List $pages): HTTPResponse
    {
        return $this->batchaction(
            $pages,
            'doArchive',
            _t(__CLASS__ . '.RESULT', 'Deleted %d pages from draft and live, and sent them to the archive')
        );
    }

    public function applicablePages($ids)
    {
        return $this->applicablePagesHelper($ids, 'canArchive');
    }
}
