<?php

namespace SilverStripe\CMS\BatchActions;

use SilverStripe\ORM\SS_List;
use SilverStripe\Admin\CMSBatchAction;

/**
 * Delete items batch action.
 */
class CMSBatchAction_Archive extends CMSBatchAction {
	public function getActionTitle() {
		return _t('CMSBatchAction_Archive.TITLE', 'Unpublish and archive');
	}

	public function run(SS_List $pages) {
		return $this->batchaction($pages, 'doArchive',
			_t('CMSBatchAction_Archive.RESULT', 'Deleted %d pages from draft and live, and sent them to the archive')
		);
	}

	public function applicablePages($ids) {
		return $this->applicablePagesHelper($ids, 'canArchive');
	}
}
