<?php
/**
 * Adds tracking of links in any HTMLText fields which reference SiteTree or File items. Attaching this to any
 * DataObject will add four fields which contain all links to SiteTree and File items referenced in any HTMLText fields, 
 * and two booleans to indicate if there are any broken links.
 *
 * SiteTreeLinkTracking provides augmentSyncLinkTracking as an entry point for the tracking updater.
 *
 * Additionally, a SiteTreeLinkTracking_Highlighter extension is provided which, when applied to HtmlEditorField,
 * will reuse the link SiteTreeLinkTracking's parser to add "ss-broken" classes to all broken links found this way.
 * The resulting class will be saved to the Content on the subsequent write operation. If links are found to be
 * no longer broken, the class will be removed on the next write.
 *
 * The underlying SiteTreeLinkTracking_Parser can recognise broken internal links, broken internal anchors, and some
 * typical broken links such as empty href, or a link starting with a slash.
 */
class SiteTreeLinkTracking extends DataExtension {

	public $parser;

	private static $dependencies = array(
		'parser' => '%$SiteTreeLinkTracking_Parser'
	);

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

	public function trackLinksInField($fieldName) {
		$record = $this->owner;

		$linkedPages = array();
		$linkedFiles = array();

		$htmlValue = Injector::inst()->create('HTMLValue', $record->$fieldName);
		$links = $this->parser->process($htmlValue);

		// Highlight broken links in the content.
		foreach ($links as $link) {
			$classStr = trim($link['DOMReference']->getAttribute('class'));
			if (!$classStr) {
				$classes = array();
			} else {
				$classes = explode(' ', $classStr);
			}

			// Add or remove the broken class from the link, depending on the link status.
			if ($link['Broken']) {
				$classes = array_unique(array_merge($classes, array('ss-broken')));
			} else {
				$classes = array_diff($classes, array('ss-broken'));
			}

			if (!empty($classes)) {
				$link['DOMReference']->setAttribute('class', implode(' ', $classes));
			} else {
				$link['DOMReference']->removeAttribute('class');
			}
		}
		$record->$fieldName = $htmlValue->getContent();

		// Populate link tracking for internal links & links to asset files.
		foreach ($links as $link) {
			switch ($link['Type']) {
				case 'sitetree':
					if ($link['Broken']) {
						$record->HasBrokenLink = true;
					} else {
						$linkedPages[] = $link['Target'];
					}
					break;

				case 'file':
					if ($link['Broken']) {
						$record->HasBrokenFile = true;
					} else {
						$linkedFiles[] = $link['Target'];
					}
					break;

				default:
					if ($link['Broken']) {
						$record->HasBrokenLink = true;
					}
					break;
			}
		}

		// Add file tracking for image references
		if($images = $htmlValue->getElementsByTagName('img')) foreach($images as $img) {
			if($image = File::find($path = urldecode(Director::makeRelative($img->getAttribute('src'))))) {
				$linkedFiles[] = $image->ID;
			} else {
				if(substr($path, 0, strlen(ASSETS_DIR) + 1) == ASSETS_DIR . '/') {
					$record->HasBrokenFile = true;
				}
			}
		}

		// Update the "LinkTracking" many_many
		if($record->ID && $record->many_many('LinkTracking') && $tracker = $record->LinkTracking()) {
			$tracker->removeByFilter(sprintf(
				'"FieldName" = \'%s\' AND "%s" = %d',
				$fieldName,
				$tracker->getForeignKey(),
				$record->ID
			));

			if($linkedPages) foreach($linkedPages as $item) {
				$tracker->add($item, array('FieldName' => $fieldName));
			}
		}

		// Update the "ImageTracking" many_many
		if($record->ID && $record->many_many('ImageTracking') && $tracker = $record->ImageTracking()) {
			$tracker->removeByFilter(sprintf(
				'"FieldName" = \'%s\' AND "%s" = %d',
				$fieldName,
				$tracker->getForeignKey(),
				$record->ID
			));

			if($linkedFiles) foreach($linkedFiles as $item) {
				$tracker->add($item, array('FieldName' => $fieldName));
			}
		}
	}

	public function augmentSyncLinkTracking() {
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

/**
 * A helper object for extracting information about links.
 */
class SiteTreeLinkTracking_Parser {

	/**
	 * Finds the links that are of interest for the link tracking automation. Checks for brokenness and attaches
	 * extracted metadata so consumers can decide what to do with the DOM element (provided as DOMReference).
	 *
	 * @param SS_HTMLValue $htmlValue Object to parse the links from.
	 * @return array Associative array containing found links with the following field layout:
	 *		Type: string, name of the link type
	 *		Target: any, a reference to the target object, depends on the Type
	 *		Anchor: string, anchor part of the link
	 *		DOMReference: DOMElement, reference to the link to apply changes.
	 *		Broken: boolean, a flag highlighting whether the link should be treated as broken.
	 */
	public function process(SS_HTMLValue $htmlValue) {
		$results = array();

		$links = $htmlValue->getElementsByTagName('a');
		if(!$links) return $results;

		foreach($links as $link) {
			if (!$link->hasAttribute('href')) continue;

			$href = Director::makeRelative($link->getAttribute('href'));

			// Definitely broken links.
			if($href == '' || $href[0] == '/') {
				$results[] = array(
					'Type' => 'broken',
					'Target' => null,
					'Anchor' => null,
					'DOMReference' => $link,
					'Broken' => true
				);

				continue;
			}

			// Link to a page on this site.
			$matches = array();
			if(preg_match('/\[sitetree_link(?:\s*|%20|,)?id=([0-9]+)\](#(.*))?/i', $href, $matches)) {
				$page = DataObject::get_by_id('SiteTree', $matches[1]);
				if (!$page) {
					// Page doesn't exist.
					$broken = true;
				} else if (!empty($matches[3]) && !preg_match("/(name|id)=\"{$matches[3]}\"/", $page->Content)) {
					// Broken anchor on the target page.
					$broken = true;
				} else {
					$broken = false;
				}

				$results[] = array(
					'Type' => 'sitetree',
					'Target' => $matches[1],
					'Anchor' => empty($matches[3]) ? null : $matches[3],
					'DOMReference' => $link,
					'Broken' => $broken
				);

				continue;
			}

			// Link to a file on this site.
			$matches = array();
			if(preg_match('/\[file_link(?:\s*|%20|,)?id=([0-9]+)\]/i', $href, $matches)) {
				$results[] = array(
					'Type' => 'file',
					'Target' => $matches[1],
					'Anchor' => null,
					'DOMReference' => $link,
					'Broken' => !DataObject::get_by_id('File', $matches[1])
				);

				continue;
			}

			// Local anchor.
			$matches = array();
			if(preg_match('/^#(.*)/i', $href, $matches)) {
				$results[] = array(
					'Type' => 'localanchor',
					'Target' => null,
					'Anchor' => $matches[1],
					'DOMReference' => $link,
					'Broken' => !preg_match("#(name|id)=\"{$matches[1]}\"#", $htmlValue->getContent())
				);

				continue;
			}

		}

		return $results;
	}

}
