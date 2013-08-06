<?php

/**
 * Adds tracking of links in any HTMLText fields which reference SiteTree or File items
 *
 * Attaching this to any DataObject will add four fields which contain all links to SiteTree and File items
 * referenced in any HTMLText fields, and two booleans to indicate if there are any broken links
 *
 * Call augmentSyncLinkTracking to update those fields with any changes to those fields
 */
class SiteTreeLinkTracking extends DataExtension {

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

			if($href) {
				if(preg_match('/\[sitetree_link,id=([0-9]+)\]/i', $href, $matches)) {
					$ID = $matches[1];

					// clear out any broken link classes
					if($class = $link->getAttribute('class')) {
						$link->setAttribute('class',
							preg_replace('/(^ss-broken|ss-broken$| ss-broken )/', null, $class));
					}

					$linkedPages[] = $ID;
					if(!DataObject::get_by_id('SiteTree', $ID))  $record->HasBrokenLink = true;

				} else if(substr($href, 0, strlen(ASSETS_DIR) + 1) == ASSETS_DIR.'/') {
					$candidateFile = File::find(Convert::raw2sql(urldecode($href)));
					if($candidateFile) {
						$linkedFiles[] = $candidateFile->ID;
					} else {
						$record->HasBrokenFile = true;
					}
				} else if($href == '' || $href[0] == '/') {
					$record->HasBrokenLink = true;
				}
			}
		}

		// Add file tracking for image references
		if($images = $htmlValue->getElementsByTagName('img')) foreach($images as $img) {
			if($image = File::find($path = urldecode(Director::makeRelative($img->getAttribute('src'))))) {
				$linkedFiles[] = $image->ID;
			}
			else {
				if(substr($path, 0, strlen(ASSETS_DIR) + 1) == ASSETS_DIR . '/') {
					$record->HasBrokenFile = true;
				}
			}
		}

		// Update the "LinkTracking" many_many
		if($record->ID && $record->many_many('LinkTracking') && $tracker = $record->LinkTracking()) {
			$tracker->removeByFilter(sprintf(
				'"FieldName" = \'%s\' AND "%s" = %d',
				$field,
				$tracker->getForeignKey(),
				$record->ID
			));

			if($linkedPages) foreach($linkedPages as $item) {
				$tracker->add($item, array('FieldName' => $field));
			}
		}

		// Update the "ImageTracking" many_many
		if($record->ID && $record->many_many('ImageTracking') && $tracker = $record->ImageTracking()) {
			$tracker->removeByFilter(sprintf(
				'"FieldName" = \'%s\' AND "%s" = %d',
				$field,
				$tracker->getForeignKey(),
				$record->ID
			));

			if($linkedFiles) foreach($linkedFiles as $item) {
				$tracker->add($item, array('FieldName' => $field));
			}
		}
	}


	function augmentSyncLinkTracking() {
		// Reset boolean broken flags
		$this->owner->HasBrokenLink = false;
		$this->owner->HasBrokenFile = false;

		// Build a list of HTMLText fields
		$allFields = $this->owner->db();
		$htmlFields = array();
		foreach($allFields as $field => $fieldSpec) {
			if(preg_match('/([^(]+)/', $fieldSpec, $matches)) {
				$class = $matches[0];
				if(class_exists($class)){
					if($class == 'HTMLText' || is_subclass_of($class, 'HTMLText')) $htmlFields[] = $field;
				}
			}
		}

		foreach($htmlFields as $field) $this->trackLinksInField($field);
	}
}