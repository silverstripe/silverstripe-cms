<?php
/**
 * Plug-ins for additional functionality in your SiteTree classes.
 * 
 * @package cms
 * @subpackage model
 */
abstract class SiteTreeExtension extends DataExtension {

	function onBeforePublish(&$original) {
	}

	function onAfterPublish(&$original) {
	}
	
	function onBeforeUnpublish() {
	}
	
	function onAfterUnpublish() {
	}
	
	function canAddChildren($member) {
	}
	
	function canPublish($member) {
		
	}

}
