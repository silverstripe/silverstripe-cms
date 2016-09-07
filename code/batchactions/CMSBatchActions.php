<?php
/**
 * Publish items batch action.
 * 
 * @package cms
 * @subpackage batchaction
 */
class CMSBatchAction_Publish extends CMSBatchAction {
	public function getActionTitle() {
		return _t('CMSBatchActions.PUBLISH_PAGES', 'Publish');
	}

	public function run(SS_List $pages) {
		return $this->batchaction($pages, 'doPublish',
			_t('CMSBatchActions.PUBLISHED_PAGES', 'Published %d pages, %d failures')
		);
	}

	public function applicablePages($ids) {
		return $this->applicablePagesHelper($ids, 'canPublish', true, false);
	}
}

/**
 * Un-publish items batch action.
 * 
 * @package cms
 * @subpackage batchaction
 */
class CMSBatchAction_Unpublish extends CMSBatchAction {
	public function getActionTitle() {
		return _t('CMSBatchActions.UNPUBLISH_PAGES', 'Un-publish');
	}

	public function run(SS_List $pages) {
		return $this->batchaction($pages, 'doUnpublish',
			_t('CMSBatchActions.UNPUBLISHED_PAGES', 'Un-published %d pages')
		);
	}
}

/**
 * Delete items batch action.
 * 
 * @package cms
 * @subpackage batchaction
 */
class CMSBatchAction_Delete extends CMSBatchAction {
	public function getActionTitle() {
		return _t('CMSBatchActions.DELETE_DRAFT_PAGES', 'Delete from draft site');
	}

	public function run(SS_List $pages) {
		$status = array(
			'modified'=>array(),
			'deleted'=>array(),
			'error'=>array()
		);
		
		foreach($pages as $page) {
			$id = $page->ID;
			
			// Perform the action
			if($page->canDelete()) $page->delete();
			else $status['error'][$page->ID] = true;

			// check to see if the record exists on the live site, 
			// if it doesn't remove the tree node
			$liveRecord = Versioned::get_one_by_stage( 'SiteTree', 'Live', "\"SiteTree\".\"ID\"=$id");
			if($liveRecord) {
				$liveRecord->IsDeletedFromStage = true;
				$status['modified'][$liveRecord->ID] = array(
					'TreeTitle' => $liveRecord->TreeTitle,
				);
			} else {
				$status['deleted'][$id] = array();
			}

		}

		return $this->response(_t('CMSBatchActions.DELETED_DRAFT_PAGES', 'Deleted %d pages from draft site, %d failures'), $status);
	}

	public function applicablePages($ids) {
		return $this->applicablePagesHelper($ids, 'canDelete', true, false);
	}
}

/**
 * Unpublish (delete from live site) items batch action.
 * 
 * @package cms
 * @subpackage batchaction
 */
class CMSBatchAction_DeleteFromLive extends CMSBatchAction {
	public function getActionTitle() {
		return _t('CMSBatchActions.DELETE_PAGES', 'Delete from published site');
	}


	public function run(SS_List $pages) {
		$status = array(
			'modified'=>array(),
			'deleted'=>array()
		);
		
		foreach($pages as $page) {
			$id = $page->ID;
			
			// Perform the action
			if($page->canDelete()) $page->doDeleteFromLive();

			// check to see if the record exists on the stage site, if it doesn't remove the tree node
			$stageRecord = Versioned::get_one_by_stage( 'SiteTree', 'Stage', "\"SiteTree\".\"ID\"=$id");
			if($stageRecord) {
				$stageRecord->IsAddedToStage = true;
				$status['modified'][$stageRecord->ID] = array(
					'TreeTitle' => $stageRecord->TreeTitle,
				);
			} else {
				$status['deleted'][$id] = array();
			}

		}

		return $this->response(_t('CMSBatchActions.DELETED_PAGES', 'Deleted %d pages from published site, %d failures'), $status);
	}

	public function applicablePages($ids) {
		return $this->applicablePagesHelper($ids, 'canDelete', false, true);
	}
}

/**
 * Restore (undelete) items batch action. This is useful if you perform the 
 * CMSBatchAction_Delete batch action.
 *
 * @package cms
 * @subpackage batchaction
 */
class CMSBatchAction_Restore extends CMSBatchAction {
	public function getActionTitle() {
		return _t('CMSBatchActions.RESTORE_PAGES', 'Restore deleted pages');
	}

	public function run(SS_List $pages) {
		return $this->batchaction($pages, 'doRevertToLive',
			_t('CMSBatchActions.RESTORED_PAGES', 'Restored %d pages')
		);
	}
}
