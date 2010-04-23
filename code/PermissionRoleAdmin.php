<?php

/**
 * Admin interface for Permission Roles.
 * 
 * @package cms
 * @subpackage security
 */
class PermissionRoleAdmin extends ModelAdmin {
	static $managed_models = array(
		'PermissionRole'
	);
	
	public static $collection_controller_class = "PermissionRoleAdmin_CollectionController";
	public static $record_controller_class = "PermissionRoleAdmin_RecordController";
	
	static $url_segment = 'roles';
	static $menu_title = 'Roles';
}

/**
 * Customized controller for hiding permissions on AddForm
 * 
 * @package cms
 * @subpackage security
 */
class PermissionRoleAdmin_CollectionController extends ModelAdmin_CollectionController {
	public function AddForm() {
		$form = parent::AddForm();
		if ( $this->modelClass=='PermissionRole' ) {
			$permissionField = $form->Fields()->dataFieldByName('Codes');
			if($permissionField) $permissionField->setHiddenPermissions(SecurityAdmin::$hidden_permissions);
		}
		return $form;
	}
}

/**
 * Customized controller for hiding permissions on EditForm
 * 
 * @package cms
 * @subpackage security
 */
class PermissionRoleAdmin_RecordController extends ModelAdmin_RecordController {
	public function EditForm() {
		$form = parent::EditForm();
		if ( $this->parentController->modelClass=='PermissionRole' ) {
			$permissionField = $form->Fields()->dataFieldByName('Codes');
			if($permissionField) $permissionField->setHiddenPermissions(SecurityAdmin::$hidden_permissions);
		}
		return $form;
	}	
}

?>
