<?php

namespace SilverStripe\CMS\BatchActions;

use SilverStripe\Admin\CMSBatchAction;
use SilverStripe\ORM\SS_List;

/**
 * Archives a page, removing it from both live and stage
 */
class CMSBatchAction_Archive extends CMSBatchAction
{

	public function getActionTitle()
	{
		return _t('CMSBatchActions.ARCHIVE', 'Archive');
	}

	public function run(SS_List $pages)
	{
		return $this->batchaction($pages, 'doArchive',
			_t('CMSBatchActions.ARCHIVED_PAGES', 'Archived %d pages')
		);
	}

	public function applicablePages($ids)
	{
		return $this->applicablePagesHelper($ids, 'canArchive', true, true);
	}

}
