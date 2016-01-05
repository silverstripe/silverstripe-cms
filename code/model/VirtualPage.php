<?php
/**
* Virtual Page creates an instance of a  page, with the same fields that the original page had, but readonly.
* This allows you can have a page in mulitple places in the site structure, with different children without duplicating the content
* Note: This Only duplicates $db fields and not the $has_one etc..
* @package cms
*/
class VirtualPage extends Page {

	private static $description = 'Displays the content of another page';
	
	public static $virtualFields;
	
	/**
	 * @var Array Define fields that are not virtual - the virtual page must define these fields themselves.
	 * Note that anything in {@link self::config()->initially_copied_fields} is implicitly included in this list.
	 */
	private static $non_virtual_fields = array(
		"SecurityTypeID",
		"OwnerID",
		"URLSegment",
		"Sort",
		"Status",
		'ShowInMenus',
		// 'Locale'
		'ShowInSearch',
		'Version',
		"Embargo",
		"Expiry",
	);
	
	/**
	 * @var Array Define fields that are initially copied to virtual pages but left modifiable after that.
	 */
	private static $initially_copied_fields = array(
		'ShowInMenus',
		'ShowInSearch',
		'URLSegment',
	);
	
	private static $has_one = array(
		"CopyContentFrom" => "SiteTree",	
	);
	
	private static $db = array(
		"VersionID" => "Int",
	);
	
	/**
	 * Generates the array of fields required for the page type.
	 */
	public function getVirtualFields() {
		$nonVirtualFields = array_merge(self::config()->non_virtual_fields, self::config()->initially_copied_fields);
		$record = $this->CopyContentFrom();

		$allFields = $record->db();
		if($hasOne = $record->hasOne()) foreach($hasOne as $link) $allFields[$link . 'ID'] = "Int";
		$virtualFields = array();
		foreach($allFields as $field => $type) {
			if(!in_array($field, $nonVirtualFields)) $virtualFields[] = $field;
		}

		return $virtualFields;
	}

	/**
	 * @return SiteTree Returns the linked page, or failing that, a new object.
	 */
	public function CopyContentFrom() {
		$copyContentFromID = $this->CopyContentFromID;
		if(!$copyContentFromID) return new SiteTree();
		
		if(!isset($this->components['CopyContentFrom'])) {
			$this->components['CopyContentFrom'] = DataObject::get_by_id("SiteTree",
				$copyContentFromID);

			// Don't let VirtualPages point to other VirtualPages
			if($this->components['CopyContentFrom'] instanceof VirtualPage) {
				$this->components['CopyContentFrom'] = null;
			}
				
			// has_one component semantics incidate than an empty object should be returned
			if(!$this->components['CopyContentFrom']) {
				$this->components['CopyContentFrom'] = new SiteTree();
			}
		}
		
		return $this->components['CopyContentFrom'] ? $this->components['CopyContentFrom'] : new SiteTree();
	}
	public function setCopyContentFromID($val) {
		if($val && DataObject::get_by_id('SiteTree', $val) instanceof VirtualPage) $val = 0;
		return $this->setField("CopyContentFromID", $val);
	}

	public function ContentSource() {
		return $this->CopyContentFrom();
	}

	/**
	 * For VirtualPage, add a canonical link tag linking to the original page
	 * See TRAC #6828 & http://support.google.com/webmasters/bin/answer.py?hl=en&answer=139394
	 *
	 * @param boolean $includeTitle Show default <title>-tag, set to false for custom templating
	 * @return string The XHTML metatags
	 */
	public function MetaTags($includeTitle = true) {
		$tags = parent::MetaTags($includeTitle);
		if ($this->CopyContentFrom()->ID) {
			$tags .= "<link rel=\"canonical\" href=\"{$this->CopyContentFrom()->Link()}\" />\n";
		}
		return $tags;
	}
	
	public function allowedChildren() {
		if($this->CopyContentFrom()) {
			return $this->CopyContentFrom()->allowedChildren();
		}
	}
	
	public function syncLinkTracking() {
		if($this->CopyContentFromID) {
			$this->HasBrokenLink = !(bool) DataObject::get_by_id('SiteTree', $this->CopyContentFromID);
		} else {
			$this->HasBrokenLink = true;
		}
	}
	
	/**
	 * We can only publish the page if there is a published source page
	 */
	public function canPublish($member = null) {
		return $this->isPublishable() && parent::canPublish($member);
	}
	
	/**
	 * Return true if we can delete this page from the live site, which is different from can
	 * we publish it.
	 */
	public function canDeleteFromLive($member = null) {
		return parent::canPublish($member);
	}
	
	/**
	 * Returns true if is page is publishable by anyone at all
	 * Return false if the source page isn't published yet.
	 *
	 * Note that isPublishable doesn't affect ete from live, only publish.
	 */
	public function isPublishable() {
		// No source
		if(!$this->CopyContentFrom() || !$this->CopyContentFrom()->ID) {
			return false;
		}
		
		// Unpublished source
		if(!Versioned::get_versionnumber_by_stage('SiteTree', 'Live', $this->CopyContentFromID)) {
			return false;
		}
		
		// Default - publishable
		return true;
	}
		
	/**
	 * Generate the CMS fields from the fields from the original page.
	 */
	public function getCMSFields() {
		$fields = parent::getCMSFields();
		
		// Setup the linking to the original page.
		$copyContentFromField = new TreeDropdownField(
			"CopyContentFromID",
			_t('VirtualPage.CHOOSE', "Linked Page"),
			"SiteTree"
		);
		// filter doesn't let you select children of virtual pages as as source page
		//$copyContentFromField->setFilterFunction(create_function('$item', 'return !($item instanceof VirtualPage);'));
		
		// Setup virtual fields
		if($virtualFields = $this->getVirtualFields()) {
			$roTransformation = new ReadonlyTransformation();
			foreach($virtualFields as $virtualField) {
				if($fields->dataFieldByName($virtualField))
					$fields->replaceField($virtualField, $fields->dataFieldByName($virtualField)->transform($roTransformation));
			}
		}

		$msgs = array();
		
		$fields->addFieldToTab("Root.Main", $copyContentFromField, "Title");
		
		// Create links back to the original object in the CMS
		if($this->CopyContentFrom()->exists()) {
			$link = "<a class=\"cmsEditlink\" href=\"admin/pages/edit/show/$this->CopyContentFromID\">"
				. _t('VirtualPage.EditLink', 'edit')
				. "</a>";
			$msgs[] = _t(
				'VirtualPage.HEADERWITHLINK',
				"This is a virtual page copying content from \"{title}\" ({link})",
				array(
					'title' => $this->CopyContentFrom()->obj('Title'),
					'link' => $link
				)
			);
		} else {
			$msgs[] = _t('VirtualPage.HEADER', "This is a virtual page");
			$msgs[] = _t(
				'SITETREE.VIRTUALPAGEWARNING',
				'Please choose a linked page and save first in order to publish this page'
			);
		}
		if(
			$this->CopyContentFromID
			&& !Versioned::get_versionnumber_by_stage('SiteTree', 'Live', $this->CopyContentFromID)
		) {
			$msgs[] = _t(
				'SITETREE.VIRTUALPAGEDRAFTWARNING',
				'Please publish the linked page in order to publish the virtual page'
			);
		}

		$fields->addFieldToTab("Root.Main",
			new LiteralField(
				'VirtualPageMessage',
				'<div class="message notice">' . implode('. ', $msgs) . '.</div>'
			),
			'CopyContentFromID'
		);
	
		return $fields;
	}

	public function getSettingsFields() {
		$fields = parent::getSettingsFields();
		if(!$this->CopyContentFrom()->exists()) {
			$fields->addFieldToTab("Root.Settings",
				new LiteralField(
					'VirtualPageWarning',
					'<div class="message notice">'
					 . _t(
							'SITETREE.VIRTUALPAGEWARNINGSETTINGS',
							'Please choose a linked page in the main content fields in order to publish'
						)
					. '</div>'
				),
				'ClassName'
			);
		}

		return $fields;
	}
	
	/**
	 * We have to change it to copy all the content from the original page first.
	 */
	public function onBeforeWrite() {
		$performCopyFrom = null;

		// Determine if we need to copy values.
		if(
			$this->extension_instances['Versioned']->migratingVersion
			&& Versioned::current_stage() == 'Live'
			&& $this->CopyContentFromID
		) {
			// On publication to live, copy from published source.
			$performCopyFrom = true;
		
			$stageSourceVersion = DB::prepared_query(
				'SELECT "Version" FROM "SiteTree" WHERE "ID" = ?',
				array($this->CopyContentFromID)
			)->value();
			$liveSourceVersion = DB::prepared_query(
				'SELECT "Version" FROM "SiteTree_Live" WHERE "ID" = ?',
				array($this->CopyContentFromID)
			)->value();
		
			// We're going to create a new VP record in SiteTree_versions because the published
			// version might not exist, unless we're publishing the latest version
			if($stageSourceVersion != $liveSourceVersion) {
				$this->extension_instances['Versioned']->migratingVersion = null;
			}
		} else {
			// On regular write, copy from draft source. This is only executed when the source page changes.
			$performCopyFrom = $this->isChanged('CopyContentFromID', 2) && $this->CopyContentFromID != 0;
		}
		
 		if($performCopyFrom && $this instanceof VirtualPage) {
			// This flush is needed because the get_one cache doesn't respect site version :-(
			singleton('SiteTree')->flushCache();
			// @todo Update get_one to support parameterised queries
			$source = DataObject::get_by_id("SiteTree", $this->CopyContentFromID);
			// Leave the updating of image tracking until after write, in case its a new record
			$this->copyFrom($source, false);
		}
		
		parent::onBeforeWrite();
	}
	
	public function onAfterWrite() {
		parent::onAfterWrite();

		// Don't do this stuff when we're publishing
		if(!$this->extension_instances['Versioned']->migratingVersion) {
	 		if(
				$this->isChanged('CopyContentFromID')
	 			&& $this->CopyContentFromID != 0
				&& $this instanceof VirtualPage
			) {
				$this->updateImageTracking();
			}
		}

		// Check if page type has changed to a non-virtual page.
		// Caution: Relies on the fact that the current instance is still of the old page type.
		if($this->isChanged('ClassName', 2)) {
			$changed = $this->getChangedFields();
			$classBefore = $changed['ClassName']['before'];
			$classAfter = $changed['ClassName']['after'];
			if($classBefore != $classAfter) {
				// Remove all database rows for the old page type to avoid inconsistent data retrieval.
				// TODO This should apply to all page type changes, not only on VirtualPage - but needs
				// more comprehensive testing as its a destructive operation
				$removedTables = array_diff(ClassInfo::dataClassesFor($classBefore), ClassInfo::dataClassesFor($classAfter));
				if($removedTables) foreach($removedTables as $removedTable) {
					// Note: *_versions records are left intact
					foreach(array('', 'Live') as $stage) {
						if($stage) $removedTable = "{$removedTable}_{$stage}";
						DB::prepared_query("DELETE FROM \"$removedTable\" WHERE \"ID\" = ?", array($this->ID));
					}
				}

				// Also publish the change immediately to avoid inconsistent behaviour between
				// a non-virtual draft and a virtual live record (e.g. republishing the original record
				// shouldn't republish the - now unrelated - changes on the ex-VirtualPage draft).
				// Copies all stage fields to live as well.
				// @todo Update get_one to support parameterised queries
				$source = DataObject::get_by_id("SiteTree", $this->CopyContentFromID);
				$this->copyFrom($source);
				$this->publish('Stage', 'Live');

				// Change reference on instance (as well as removing the underlying database tables)
				$this->CopyContentFromID = 0;
			}
		}
	}

	protected function validate() {
		$result = parent::validate();

		// "Can be root" validation
		$orig = $this->CopyContentFrom();
		if(!$orig->stat('can_be_root') && !$this->ParentID) {
			$result->error(
				_t(
					'VirtualPage.PageTypNotAllowedOnRoot',
					'Original page type "{type}" is not allowed on the root level for this virtual page',
					array('type' => $orig->i18n_singular_name())
				),
				'CAN_BE_ROOT_VIRTUAL'
			);
		}

		return $result;
	}
	
	/**
	 * Ensure we have an up-to-date version of everything.
	 */
	public function copyFrom($source, $updateImageTracking = true) {
		if($source) {
			foreach($this->getVirtualFields() as $virtualField) {
				$this->$virtualField = $source->$virtualField;
			}
			
			// We also want to copy certain, but only if we're copying the source page for the first
			// time. After this point, the user is free to customise these for the virtual page themselves.
			if($this->isChanged('CopyContentFromID', 2) && $this->CopyContentFromID != 0) {
				foreach(self::config()->initially_copied_fields as $fieldName) {
					$this->$fieldName = $source->$fieldName;
				}
			}
			
			if($updateImageTracking) $this->updateImageTracking();
		}
	}
	
	public function updateImageTracking() {
		// Doesn't work on unsaved records
		if(!$this->ID) return;

		// Remove CopyContentFrom() from the cache
		unset($this->components['CopyContentFrom']);
		
		// Update ImageTracking
		$this->ImageTracking()->setByIdList($this->CopyContentFrom()->ImageTracking()->column('ID'));
	}

	/**
	 * @param string $numChildrenMethod
	 * @return string
	 */
	public function CMSTreeClasses($numChildrenMethod="numChildren") {
		return parent::CMSTreeClasses($numChildrenMethod) . ' VirtualPage-' . $this->CopyContentFrom()->ClassName;
	}
	
	/**
	 * Allow attributes on the master page to pass
	 * through to the virtual page
	 *
	 * @param string $field
	 * @return mixed
	 */
	public function __get($field) {
		if(parent::hasMethod($funcName = "get$field")) {
			return $this->$funcName();
		} else if(parent::hasField($field) || ($field === 'ID' && !$this->exists())) {
			return $this->getField($field);
		} else {
			return $this->copyContentFrom()->$field;
		}
	}
	
	/**
	 * Pass unrecognized method calls on to the original data object
	 *
	 * @param string $method
	 * @param string $args
	 * @return mixed
	 */
	public function __call($method, $args) {
		if(parent::hasMethod($method)) {
			return parent::__call($method, $args);
		} else {
			return call_user_func_array(array($this->copyContentFrom(), $method), $args);
		}
	}

	/**
	 * @param string $field
	 * @return bool
	 */
	public function hasField($field) {
		return (
			array_key_exists($field, $this->record)
			|| $this->hasDatabaseField($field)
			|| array_key_exists($field, $this->db()) // Needed for composite fields
			|| parent::hasMethod("get{$field}")
			|| $this->CopyContentFrom()->hasField($field)
		);
	}	
	/**
	 * Overwrite to also check for method on the original data object
	 *
	 * @param string $method
	 * @return bool
	 */
	public function hasMethod($method) {
		if(parent::hasMethod($method)) return true;
		return $this->copyContentFrom()->hasMethod($method);
	}

	/**
	 * Return the "casting helper" (a piece of PHP code that when evaluated creates a casted value object) for a field
	 * on this object.
	 *
	 * @param string $field
	 * @return string
	 */
	public function castingHelper($field) {
		if($this->copyContentFrom()) {
			return $this->copyContentFrom()->castingHelper($field);
		} else {
			return parent::castingHelper($field);
		}
	}

}

/**
 * Controller for the virtual page.
 * @package cms
 */
class VirtualPage_Controller extends Page_Controller {
	
	private static $allowed_actions = array(
		'loadcontentall' => 'ADMIN',
	);
	
	/**
	 * Reloads the content if the version is different ;-)
	 */
	public function reloadContent() {
		$this->failover->copyFrom($this->failover->CopyContentFrom());
		$this->failover->write();
		return;
	}
	
	public function getViewer($action) {
		$originalClass = get_class($this->CopyContentFrom());
		if ($originalClass == 'SiteTree') $name = 'Page_Controller';
		else $name = $originalClass."_Controller";
		$controller = new $name();
		return $controller->getViewer($action);
	}
	
	/**
	 * When the virtualpage is loaded, check to see if the versions are the same
	 * if not, reload the content.
	 * NOTE: Virtual page must have a container object of subclass of sitetree.
	 * We can't load the content without an ID or record to copy it from.
	 */
	public function init(){
		if(isset($this->record) && $this->record->ID){
			if($this->record->VersionID != $this->failover->CopyContentFrom()->Version){
				$this->reloadContent();
				$this->VersionID = $this->failover->CopyContentFrom()->VersionID;
			}
		}
		parent::init();
		$this->__call('init', array());
	}

	public function loadcontentall() {
		$pages = DataObject::get("VirtualPage");
		foreach($pages as $page) {
			$page->copyFrom($page->CopyContentFrom());
			$page->write();
			$page->publish("Stage", "Live");
			echo "<li>Published $page->URLSegment";
		}
	}
	
	/**
	 * Also check the original object's original controller for the method
	 *
	 * @param string $method
	 * @return bool
	 */
	public function hasMethod($method) {
		$haveIt = parent::hasMethod($method);
		if (!$haveIt) {	
			$originalClass = get_class($this->CopyContentFrom());
			if ($originalClass == 'SiteTree') $name = 'ContentController';
			else $name = $originalClass."_Controller";
			$controller = new $name($this->dataRecord->copyContentFrom());
			$haveIt = $controller->hasMethod($method);
		}
		return $haveIt;
	}
	
	/**
	 * Pass unrecognized method calls on to the original controller
	 *
	 * @param string $method
	 * @param string $args
	 * @return mixed
	 *
	 * @throws Exception Any error other than a 'no method' error.
	 */
	public function __call($method, $args) {
		try {
			return parent::__call($method, $args);
		} catch (Exception $e) {
			// Hack... detect exception type. We really should use exception subclasses.
			// if the exception isn't a 'no method' error, rethrow it
			if ($e->getCode() !== 2175) {
				throw $e;
			}

			$original = $this->copyContentFrom();
			$controller = ModelAsController::controller_for($original);

			// Ensure request/response data is available on virtual controller
			$controller->setRequest($this->getRequest());
			$controller->response = $this->response; // @todo - replace with getter/setter in 3.3

			return call_user_func_array(array($controller, $method), $args);
		}
	}
}


