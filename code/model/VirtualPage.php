<?php

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Versioning\Versioned;
use SilverStripe\Security\Member;

/**
* Virtual Page creates an instance of a  page, with the same fields that the original page had, but readonly.
* This allows you can have a page in mulitple places in the site structure, with different children without duplicating the content
* Note: This Only duplicates $db fields and not the $has_one etc..
 *
 * @method SiteTree CopyContentFrom()
 * @property int $CopyContentFromID
 *
* @package cms
*/
class VirtualPage extends Page {

	private static $description = 'Displays the content of another page';

	public static $virtualFields;

	/**
	 * @var array Define fields that are not virtual - the virtual page must define these fields themselves.
	 * Note that anything in {@link self::config()->initially_copied_fields} is implicitly included in this list.
	 */
	private static $non_virtual_fields = array(
		"ID",
		"ClassName",
		"ObsoleteClassName",
		"SecurityTypeID",
		"OwnerID",
		"ParentID",
		"URLSegment",
		"Sort",
		"Status",
		'ShowInMenus',
		// 'Locale'
		'ShowInSearch',
		'Version',
		"Embargo",
		"Expiry",
		"CanViewType",
		"CanEditType",
		"CopyContentFromID",
		"HasBrokenLink",
	);

	/**
	 * @var array Define fields that are initially copied to virtual pages but left modifiable after that.
	 */
	private static $initially_copied_fields = array(
		'ShowInMenus',
		'ShowInSearch',
		'URLSegment',
	);

	private static $has_one = array(
		"CopyContentFrom" => "SiteTree",
	);

	private static $owns = array(
		"CopyContentFrom",
	);

	private static $db = array(
		"VersionID" => "Int",
	);

	/**
	 * Generates the array of fields required for the page type.
	 *
	 * @return array
	 */
	public function getVirtualFields() {
		// Check if copied page exists
		$record = $this->CopyContentFrom();
		if(!$record || !$record->exists()) {
			return array();
		}

		// Diff db with non-virtual fields
		$fields = array_keys($record->db());
		$nonVirtualFields = $this->getNonVirtualisedFields();
		return array_diff($fields, $nonVirtualFields);
	}

	/**
	 * List of fields or properties to never virtualise
	 *
	 * @return array
	 */
	public function getNonVirtualisedFields() {
		return array_merge($this->config()->non_virtual_fields, $this->config()->initially_copied_fields);
	}

	public function setCopyContentFromID($val) {
		// Sanity check to prevent pages virtualising other virtual pages
		if($val && DataObject::get_by_id('SiteTree', $val) instanceof VirtualPage) {
			$val = 0;
		}
		return $this->setField("CopyContentFromID", $val);
	}

	public function ContentSource() {
		$copied = $this->CopyContentFrom();
		if($copied && $copied->exists()) {
			return $copied;
		}
		return $this;
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
		$copied = $this->CopyContentFrom();
		if ($copied && $copied->exists()) {
			$link = Convert::raw2att($copied->Link());
			$tags .= "<link rel=\"canonical\" href=\"{$link}\" />\n";
		}
		return $tags;
	}

	public function allowedChildren() {
		$copy = $this->CopyContentFrom();
		if($copy && $copy->exists()) {
			return $copy->allowedChildren();
		}
		return array();
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
	 *
	 * @param Member $member Member to check
	 * @return bool
	 */
	public function canPublish($member = null) {
		return $this->isPublishable() && parent::canPublish($member);
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

	public function onBeforeWrite() {
		parent::onBeforeWrite();
		$this->refreshFromCopied();
	}

	/**
	 * Copy any fields from the copied record to bootstrap /backup
	 */
	protected function refreshFromCopied() {
		// Skip if copied record isn't available
		$source = $this->CopyContentFrom();
		if(!$source || !$source->exists()) {
			return;
		}

		// We also want to copy certain, but only if we're copying the source page for the first
		// time. After this point, the user is free to customise these for the virtual page themselves.
		if($this->isChanged('CopyContentFromID', 2) && $this->CopyContentFromID) {
			foreach (self::config()->initially_copied_fields as $fieldName) {
				$this->$fieldName = $source->$fieldName;
			}
		}

		// Copy fields to the original record in case the class type changes
		foreach($this->getVirtualFields() as $virtualField) {
			$this->$virtualField = $source->$virtualField;
		}
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

	public function validate() {
		$result = parent::validate();

		// "Can be root" validation
		$orig = $this->CopyContentFrom();
		if($orig && $orig->exists() && !$orig->stat('can_be_root') && !$this->ParentID) {
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

	public function updateImageTracking() {
		// Doesn't work on unsaved records
		if(!$this->ID) return;

		// Remove CopyContentFrom() from the cache
		unset($this->components['CopyContentFrom']);

		// Update ImageTracking
		$this->ImageTracking()->setByIDList($this->CopyContentFrom()->ImageTracking()->column('ID'));
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
		}
		if(parent::hasField($field) || ($field === 'ID' && !$this->exists())) {
			return $this->getField($field);
		}
		if(($copy = $this->CopyContentFrom()) && $copy->exists()) {
			return $copy->$field;
		}
		return null;
	}

	public function getField($field) {
		if($this->isFieldVirtualised($field)) {
			return $this->CopyContentFrom()->getField($field);
		}
		return parent::getField($field);
	}

	/**
	 * Check if given field is virtualised
	 *
	 * @param string $field
	 * @return bool
	 */
	public function isFieldVirtualised($field) {
		// Don't defer if field is non-virtualised
		$ignore = $this->getNonVirtualisedFields();
		if(in_array($field, $ignore)) {
			return false;
		}

		// Don't defer if no virtual page
		$copied = $this->CopyContentFrom();
		if(!$copied || !$copied->exists()) {
			return false;
	}

		// Check if copied object has this field
		return $copied->hasField($field);
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
			return call_user_func_array(array($this->CopyContentFrom(), $method), $args);
		}
	}

	/**
	 * @param string $field
	 * @return bool
	 */
	public function hasField($field) {
		if(parent::hasField($field)) {
			return true;
		}
		$copy = $this->CopyContentFrom();
		return $copy && $copy->exists() && $copy->hasField($field);
	}

	/**
	 * Overwrite to also check for method on the original data object
	 *
	 * @param string $method
	 * @return bool
	 */
	public function hasMethod($method) {
		if(parent::hasMethod($method)) {
			return true;
		}
		// Don't call property setters on copied page
		if(stripos($method, 'set') === 0) {
			return false;
		}
		$copy = $this->CopyContentFrom();
		return $copy && $copy->exists() && $copy->hasMethod($method);
	}

	/**
	 * Return the "casting helper" (a piece of PHP code that when evaluated creates a casted value object) for a field
	 * on this object.
	 *
	 * @param string $field
	 * @return string
	 */
	public function castingHelper($field) {
		$copy = $this->CopyContentFrom();
		if($copy && $copy->exists() && ($helper = $copy->castingHelper($field))) {
			return $helper;
		}
		return parent::castingHelper($field);
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
	 * Backup of virtualised controller
	 *
	 * @var ContentController
	 */
	protected $virtualController = null;

	/**
	 * Get virtual controller
	 *
	 * @return ContentController
	 */
	protected function getVirtualisedController() {
		if($this->virtualController) {
			return $this->virtualController;
		}

		// Validate virtualised model
		/** @var VirtualPage $page */
		$page = $this->data();
		$virtualisedPage = $page->CopyContentFrom();
		if (!$virtualisedPage || !$virtualisedPage->exists()) {
			return null;
		}

		// Create controller using standard mechanism
		$this->virtualController = ModelAsController::controller_for($virtualisedPage);
		return $this->virtualController;
	}

	public function getViewer($action) {
		$controller = $this->getVirtualisedController() ?: $this;
		return $controller->getViewer($action);
	}

	/**
	 * When the virtualpage is loaded, check to see if the versions are the same
	 * if not, reload the content.
	 * NOTE: Virtual page must have a container object of subclass of sitetree.
	 * We can't load the content without an ID or record to copy it from.
	 */
	public function init(){
		parent::init();
		$this->__call('init', array());
	}

	/**
	 * Also check the original object's original controller for the method
	 *
	 * @param string $method
	 * @return bool
	 */
	public function hasMethod($method) {
		if(parent::hasMethod($method)) {
			return true;
		};

		// Fallback
		$controller = $this->getVirtualisedController();
		return $controller && $controller->hasMethod($method);
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
	public function __call($method, $args)
	{
		// Check if we can safely call this method before passing it back
		// to custom methods.
		if ($this->getExtraMethodConfig($method)) {
			return parent::__call($method, $args);
		}

		// Pass back to copied page
		$controller = $this->getVirtualisedController();
		if(!$controller) {
			return null;
		}

		// Ensure request/response data is available on virtual controller
		$controller->setRequest($this->getRequest());
		$controller->setResponse($this->getResponse());

		return call_user_func_array(array($controller, $method), $args);
	}
}
