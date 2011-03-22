<?php
/**
 * @package cms
 * @subpackage model
 */
class SiteTreeFileDecorator extends DataObjectDecorator {
	
	function extraStatics() {
		return array(
			'belongs_many_many' => array(
				"BackLinkTracking" => "SiteTree",
			)
		);
	}
	
	/**
	 * @todo Unnecessary shortcut for AssetTableField, coupled with cms module.
	 * 
	 * @return Integer
	 */
	function BackLinkTrackingCount() {
		$pages = $this->owner->BackLinkTracking();
		if($pages) {
			return $pages->Count();
		} else {
			return 0;
		}
	}
	
	/**
	 * Updates link tracking.
	 */
	function onAfterDelete() {
		$brokenPages = $this->owner->BackLinkTracking();
		if($brokenPages) {
			$origStage = Versioned::current_stage();

			// This will syncLinkTracking on draft
			Versioned::reading_stage('Stage');
			foreach($brokenPages as $brokenPage) $brokenPage->write();

			// This will syncLinkTracking on published
			Versioned::reading_stage('Live');
			foreach($brokenPages as $brokenPage) $brokenPage->write();

			Versioned::reading_stage($origStage);
		}
	}
	
	/**
	 * Rewrite links to the $old file to now point to the $new file.
	 * 
	 * @uses SiteTree->rewriteFileURL()
	 * 
	 * @param String $old File path relative to the webroot
	 * @param String $new File path relative to the webroot
	 */
	function updateLinks($old, $new) {
		if(class_exists('Subsite')) Subsite::disable_subsite_filter(true);
	
		$pages = $this->owner->BackLinkTracking();

		$summary = "";
		if($pages) {
			foreach($pages as $page) $page->rewriteFileURL($old,$new);
		}
		
		if(class_exists('Subsite')) Subsite::disable_subsite_filter(false);
	}
	
}