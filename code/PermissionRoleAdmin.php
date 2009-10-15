<?php

/**
 * Admin interface for Permission Roles.
 */
class PermissionRoleAdmin extends ModelAdmin {
	static $managed_models = array(
		'PermissionRole'
	);
	
	static $url_segment = 'roles';
	static $menu_title = 'Roles';
}

?>
