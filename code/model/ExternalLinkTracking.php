<?php

/**
 * Adds tracking of external links in any HTMLText fields (copied from SiteTreeLinkTracking)
 *
 * Attaching this to any DataObject will add four fields which contain all links to external Sites and File items
 * referenced in any HTMLText fields, and two booleans to indicate if there are any broken links
 *
 * Call augmentExternalLinkTracking to update those fields with any changes to those fields
 */
class ExternalLinkTracking extends SiteTreeLinkTracking {

	private static $db = array(
		"HasBrokenFile" => "Boolean",
		"HasBrokenLink" => "Boolean"
	);

	private static $many_many = array(
		"LinkTracking" => "SiteTree",
		"ImageTracking" => "File"
	);

	private static $many_many_extraFields = array(
		"LinkTracking" => array("FieldName" => "Varchar"),
		"ImageTracking" => array("FieldName" => "Varchar")
	);

	function trackLinksInField($field) {
		$record = $this->owner;

		$linkedPages = array();
		$linkedFiles = array();

		$htmlValue = Injector::inst()->create('HTMLValue', $record->$field);

		// Populate link tracking for internal links & links to asset files.
		if($links = $htmlValue->getElementsByTagName('a')) foreach($links as $link) {
			$href = Director::makeRelative($link->getAttribute('href'));

			// ignore SiteTree and assets links as they will be caught by SiteTreeLinkTracking
			if(preg_match('/\[sitetree_link,id=([0-9]+)\]/i', $href, $matches)) {
				return;
			} else if(substr($href, 0, strlen(ASSETS_DIR) + 1) == ASSETS_DIR.'/') {
				return;
			}
			if($href && function_exists('curl_init')) {
				$handle = curl_init($href);
				curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
				$response = curl_exec($handle);
				$httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);  
				curl_close($handle);
				if ($httpCode < 200 || $httpCode > 300 && $httpCode != 302) {
					$record->HasBrokenLink = true;
				} else if($href == '' || $href[0] == '/') {
					$record->HasBrokenLink = true;
				}
			}
		}

	}


	function augmentExternalLinkTracking() {
		$this->augmentSyncLinkTracking();
	}
}