<?php
/**
 * Plug-ins for additional functionality in your SiteTree classes.
 * 
 * @package cms
 * @subpackage model
 */
abstract class SiteTreeExtension extends DataExtension {

	public function onBeforePublish(&$original) {
	}

	public function onAfterPublish(&$original) {
	}
	
	public function onBeforeUnpublish() {
	}
	
	public function onAfterUnpublish() {
	}
	
	public function canAddChildren($member) {
	}
	
	public function canPublish($member) {
		
	}

}
