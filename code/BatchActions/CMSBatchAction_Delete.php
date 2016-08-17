<?php

namespace SilverStripe\CMS\BatchActions;

use SilverStripe\ORM\SS_List;
use SilverStripe\ORM\Versioning\Versioned;
use SilverStripe\Admin\CMSBatchAction;

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
			$liveRecord = Versioned::get_one_by_stage( 'SilverStripe\\CMS\\Model\\SiteTree', 'Live', array(
				'"SiteTree"."ID"' => $id
			));
			if($liveRecord) {
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
