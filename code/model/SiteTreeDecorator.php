<?php
/**
 * @package    cms
 * @subpackage model
 * @deprecated 3.0 Use {@link SiteTreeExtension}.
 */
abstract class SiteTreeDecorator extends SiteTreeExtension {

	public function __construct() {
		// TODO Re-enable before we release 3.0 beta, for now it "breaks" too many modules
		// user_error(
		// 			'SiteTreeDecorator is deprecated, please use SiteTreeExtension instead.',
		// 			E_USER_NOTICE
		// 		);
		parent::__construct();
	}

}