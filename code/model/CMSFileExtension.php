<?php

/**
 * Class CMSFileExtension
 *
 * Extends the core File class to provide basic permission checking.
 *
 * @package silverstripe
 * @subpackage cms
 */
class CMSFileExtension extends DataExtension {


	/**
	 * @param null $member
	 *
	 * @return bool
	 */
	public function canView($member = null) {
		return true;
	}


	/**
	 * @param null $member
	 *
	 * @return bool
	 */
	public function canEdit($member = null) {
		return Permission::checkMember($member, 'CMS_ACCESS_AssetAdmin');
	}


	/**
	 * @param null $member
	 *
	 * @return bool
	 */
	public function canDelete($member = null) {
		return Permission::checkMember($member, 'CMS_ACCESS_AssetAdmin');
	}


	/**
	 * @param null $member
	 *
	 * @return bool
	 */
	public function canCreate($member = null) {
		return Permission::checkMember($member, 'CMS_ACCESS_AssetAdmin');
	}

}
