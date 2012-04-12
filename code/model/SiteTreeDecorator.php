<?php
/**
 * @package    cms
 * @subpackage model
 * @deprecated 3.0 Use {@link SiteTreeExtension}.
 */
abstract class SiteTreeDecorator extends SiteTreeExtension {

	public function __construct() {
		Deprecation::notice('3.0', 'Use SiteTreeExtension instead.');
		parent::__construct();
	}

}
