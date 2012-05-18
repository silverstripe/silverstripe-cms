<?php
class AssetAdminGridFieldToolbarHeader extends GridFieldToolbarHeader {

	/**
	 * Defines a back link on the GridFieldToolbarHeader so a user can
	 * go back up in the folder hierarchy
	 *
	 * @return array
	 */
	public function getHTMLFragments($gridField) {
		$fragments = parent::getHTMLFragments($gridField);
		$folder = Controller::curr()->currentPage();
		$parent = $folder->Parent();
		if($folder->ID != 0) {
			$link = Controller::join_links(Controller::curr()->Link('show'), $parent->ID);
			$fragments['toolbar-header-left'] = sprintf('<a class="ui-icon folder-back" href="%s">Back</a>', $link);
		}
		return $fragments;
	}

}
