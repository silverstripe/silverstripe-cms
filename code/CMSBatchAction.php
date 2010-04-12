<?php

/**
 * A class representing back actions.
 * See cms/javascript/CMSMain.BatchActions.js on how to add custom javascript
 * functionality.
 * 
 * <code>
 * CMSMain::register_batch_action('publishitems', new CMSBatchAction('doPublish', 
 * 	_t('CMSBatchActions.PUBLISHED_PAGES', 'published %d pages')));
 * </code>
 * 
 * @package cms
 * @subpackage batchaction
 */
abstract class CMSBatchAction extends Object {
	/**
	 * The the text to show in the dropdown for this action
	 */
	abstract function getActionTitle();
	
	/**
	 * Run this action for the given set of pages.
	 * Return a set of status-updated JavaScript to return to the CMS.
	 */
	abstract function run(DataObjectSet $pages);
	
	/**
	 * Helper method for processing batch actions.
	 * Returns a set of status-updating JavaScript to return to the CMS.
	 *
	 * @param $pages The DataObjectSet of SiteTree objects to perform this batch action
	 * on.
	 * @param $helperMethod The method to call on each of those objects.
	 * @return JSON encoded map in the following format:
	 *  {
	 *     'modified': {
	 *       3: {'TreeTitle': 'Page3'},
	 *       5: {'TreeTitle': 'Page5'}
	 *     },
	 *     'deleted': {
	 *       // all deleted pages
	 *     }
	 *  }
	 */
	public function batchaction(DataObjectSet $pages, $helperMethod, $successMessage, $arguments = array()) {
		$status = array('modified' => array(), 'error' => array());
		
		foreach($pages as $page) {
			
			// Perform the action
			if (!call_user_func_array(array($page, $helperMethod), $arguments)) {
				$status['error'][$page->ID] = '';
			}
			
			// Now make sure the tree title is appropriately updated
			$publishedRecord = DataObject::get_by_id('SiteTree', $page->ID);
			if ($publishedRecord) {
				$status['modified'][$publishedRecord->ID] = array(
					'TreeTitle' => $publishedRecord->TreeTitle,
				);
			}
			$page->destroy();
			unset($page);
		}

		Controller::curr()->getResponse()->setStatusCode(
			200, 
			sprintf($successMessage, $pages->Count(), count($status['error']))
		);

		return Convert::raw2json($status);
	}
	
	// if your batchaction has parameters, return a fieldset here
	function getParameterFields() {
		return false;
	}
}

/**
 * Publish items batch action.
 * 
 * @package cms
 * @subpackage batchaction
 */
class CMSBatchAction_Publish extends CMSBatchAction {
	function getActionTitle() {
		return _t('CMSBatchActions.PUBLISH_PAGES', 'Publish');
	}

	function run(DataObjectSet $pages) {
		return $this->batchaction($pages, 'doPublish',
			_t('CMSBatchActions.PUBLISHED_PAGES', 'Published %d pages, %d failures')
		);
	}
}

/**
 * Un-publish items batch action.
 * 
 * @package cms
 * @subpackage batchaction
 */
class CMSBatchAction_Unpublish extends CMSBatchAction {
	function getActionTitle() {
		return _t('CMSBatchActions.UNPUBLISH_PAGES', 'Un-publish');
	}

	function run(DataObjectSet $pages) {
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
	function getActionTitle() {
		return _t('CMSBatchActions.DELETE_DRAFT_PAGES', 'Delete from draft');
	}

	function run(DataObjectSet $pages) {
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

		return Convert::raw2json($status);
	}
}

/**
 * Unpublish (delete from live site) items batch action.
 * 
 * @package cms
 * @subpackage batchaction
 */
class CMSBatchAction_DeleteFromLive extends CMSBatchAction {
	function getActionTitle() {
		return _t('CMSBatchActions.DELETE_PAGES', 'Delete from published site');
	}

	function run(DataObjectSet $pages) {
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
<<<<<<< .working

		return Convert::raw2json($status);
=======
		
		return FormResponse::respond();
>>>>>>> .merge-right.r96789
	}
}



