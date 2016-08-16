<?php

namespace SilverStripe\CMS\Controllers;

use Convert;
use SilverStripe\Admin\CMSBatchAction;
use SilverStripe\ORM\SS_List;

/**
 * Delete multiple {@link Folder} records (and the associated filesystem nodes).
 * Usually used through the {@link AssetAdmin} interface.
 *
 * @package cms
 * @subpackage batchactions
 */
class AssetAdmin_DeleteBatchAction extends CMSBatchAction
{
	public function getActionTitle()
	{
		// _t('AssetAdmin_left_ss.SELECTTODEL','Select the folders that you want to delete and then click the button below')
		return _t('AssetAdmin_DeleteBatchAction.TITLE', 'Delete folders');
	}

	public function run(SS_List $records)
	{
		$status = array(
			'modified' => array(),
			'deleted' => array()
		);

		foreach ($records as $record) {
			$id = $record->ID;

			// Perform the action
			if ($record->canDelete()) {
				$record->delete();
			}

			$status['deleted'][$id] = array();

			$record->destroy();
			unset($record);
		}

		return Convert::raw2json($status);
	}
}
