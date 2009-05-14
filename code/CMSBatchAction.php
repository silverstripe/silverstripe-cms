<?php

/**
 * A class representing back actions 
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
	 * Get text to be shown while the action is being processed, of the form
	 * "publishing pages".
	 */
	abstract function getDoingText();
	
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
	 * @para
	 */
	public function batchaction(DataObjectSet $pages, $helperMethod, $successMessage) {
		foreach($pages as $page) {
			// Perform the action
			$page->$helperMethod();
			
			// Now make sure the tree title is appropriately updated
			$publishedRecord = DataObject::get_by_id('SiteTree', $page->ID);
			$JS_title = Convert::raw2js($publishedRecord->TreeTitle());
			FormResponse::add("\$('sitetree').setNodeTitle($page->ID, '$JS_title');");
			$page->destroy();
			unset($page);
		}

		$message = sprintf($successMessage, $pages->Count());
		FormResponse::add('statusMessage("'.$message.'","good");');

		return FormResponse::respond();
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
	function getDoingText() {
		return _t('CMSBatchActions.PUBLISHING_PAGES', 'Publishing pages');
	}

	function run(DataObjectSet $pages) {
		return $this->batchaction($pages, 'doPublish',
			_t('CMSBatchActions.PUBLISHED_PAGES', 'Published %d pages')
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
	function getDoingText() {
		return _t('CMSBatchActions.UNPUBLISHING_PAGES', 'Un-publishing pages');
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
		return _t('CMSBatchActions.DELETE_PAGES', 'Delete from draft');
	}
	function getDoingText() {
		return _t('CMSBatchActions.DELETING_PAGES', 'Deleting selected pages from draft');
	}

	function run(DataObjectSet $pages) {
		foreach($pages as $page) {
			$id = $page->ID;
			
			// Perform the action
			if($page->canDelete()) $page->delete();

			// check to see if the record exists on the live site, if it doesn't remove the tree node
			$liveRecord = Versioned::get_one_by_stage( 'SiteTree', 'Live', "`SiteTree`.`ID`=$id");
			if($liveRecord) {
				$liveRecord->IsDeletedFromStage = true;
				$title = Convert::raw2js($liveRecord->TreeTitle());
				FormResponse::add("$('sitetree').setNodeTitle($id, '$title');");
				FormResponse::add("$('Form_EditForm').reloadIfSetTo($id);");
			} else {
				FormResponse::add("var node = $('sitetree').getTreeNodeByIdx('$id');");
				FormResponse::add("if(node && node.parentTreeNode)	node.parentTreeNode.removeTreeNode(node);");
				FormResponse::add("$('Form_EditForm').reloadIfSetTo($id);");
			}

			$page->destroy();
			unset($page);
		}

		$message = sprintf(_t('CMSBatchActions.DELETED_PAGES', 'Deleted %d pages from the draft site'), $pages->Count());
		FormResponse::add('statusMessage("'.$message.'","good");');

		return FormResponse::respond();
	}
}

/**
 * Delete items batch action.
 * 
 * @package cms
 * @subpackage batchaction
 */
class CMSBatchAction_DeleteFromLive extends CMSBatchAction {
	function getActionTitle() {
		return _t('CMSBatchActions.DELETE_PAGES', 'Delete from published site');
	}
	function getDoingText() {
		return _t('CMSBatchActions.DELETING_PAGES', 'Deleting selected pages from the published site');
	}

	function run(DataObjectSet $pages) {
		foreach($pages as $page) {
			$id = $page->ID;
			
			// Perform the action
			if($page->canDelete()) $page->doDeleteFromLive();

			// check to see if the record exists on the live site, if it doesn't remove the tree node
			$stageRecord = Versioned::get_one_by_stage( 'SiteTree', 'Stage', "`SiteTree`.`ID`=$id");
			if($stageRecord) {
				$stageRecord->IsAddedToStage = true;
				$title = Convert::raw2js($stageRecord->TreeTitle());
				FormResponse::add("$('sitetree').setNodeTitle($id, '$title');");
				FormResponse::add("$('Form_EditForm').reloadIfSetTo($id);");
			} else {
				FormResponse::add("var node = $('sitetree').getTreeNodeByIdx('$id');");
				FormResponse::add("if(node && node.parentTreeNode)	node.parentTreeNode.removeTreeNode(node);");
				FormResponse::add("$('Form_EditForm').reloadIfSetTo($id);");
			}

			$page->destroy();
			unset($page);
		}

		$message = sprintf(_t('CMSBatchActions.DELETED_PAGES', 'Deleted %d pages from the published site'), $pages->Count());
		FormResponse::add('statusMessage("'.$message.'","good");');

		return FormResponse::respond();
	}
}

