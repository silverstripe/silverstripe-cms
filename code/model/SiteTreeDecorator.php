<?php
/**
 * @package    cms
 * @subpackage model
 * @deprecated 3.0 Use {@link SiteTreeExtension}.
 */
abstract class SiteTreeDecorator extends SiteTreeExtension {

	public function __construct() {
		user_error(
			'SiteTreeDecorator is deprecated, please use SiteTreeExtension instead.',
			E_USER_NOTICE
		);
		parent::__construct();
	}

}