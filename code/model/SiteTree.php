<?php
/**
 * Basic data-object representing all pages within the site tree.
 * This data-object takes care of the heirachy. All page types that live within the hierarchy should inherit from this.
 * In addition, it contains a number of static methods for querying the site tree.
 * 
 * <h2>URLs</h2>
 * A page is identified during request handling via its "URLSegment" database column. 
 * As pages can be nested, the full path of a URL might contain multiple segments.
 * Each segment is stored in its filtered representation (through {@link URLSegmentFilter}).
 * The full path is constructed via {@link Link()}, {@link RelativeLink()} and {@link AbsoluteLink()}.
 * You can allow these segments to contain multibyte characters through {@link URLSegmentFilter::$default_allow_multibyte}.
 *
 * @property integer ID ID of the SiteTree object.
 * @property string URLSegment
 * @property string Title
 * @property string MenuTitle
 * @property string Content HTML content of the page.
 * @property string MetaDescription
 * @property string ExtraMeta
 * @property string ShowInMenus
 * @property string ShowInSearch
 * @property string Sort Integer value denoting the sort order.
 * @property string HasBrokenFile
 * @property string HasBrokenLink
 * @property string ReportClass
 * @property string CanViewType Type of restriction for viewing this object.
 * @property string CanEditType Type of restriction for editing this object.
 *
 * @method ManyManyList LinkTracking() List of site pages linked on this page.
 * @method ManyManyList ImageTracking() List of Images linked on this page.
 * @method ManyManyList ViewerGroups() List of groups that can view this object.
 * @method ManyManyList EditorGroups() List of groups that can edit this object.
 * @method ManyManyList BackLinkTracking() List of site pages that link to this page.
 *
 * @package cms
 */
class SiteTree extends DataObject implements PermissionProvider,i18nEntityProvider,CMSPreviewable {

	/**
	 * Indicates what kind of children this page type can have.
	 * This can be an array of allowed child classes, or the string "none" -
	 * indicating that this page type can't have children.
	 * If a classname is prefixed by "*", such as "*Page", then only that
	 * class is allowed - no subclasses. Otherwise, the class and all its
	 * subclasses are allowed.
	 * To control allowed children on root level (no parent), use {@link $can_be_root}.
	 * 
	 * Note that this setting is cached when used in the CMS, use the "flush" query parameter to clear it.
	 *
	 * @config
	 * @var array
	 */
	private static $allowed_children = array("SiteTree");

	/**
	 * The default child class for this page.
	 * Note: Value might be cached, see {@link $allowed_chilren}.
	 *
	 * @config
	 * @var string
	 */
	private static $default_child = "Page";

	/**
	 * The default parent class for this page.
	 * Note: Value might be cached, see {@link $allowed_chilren}.
	 *
	 * @config
	 * @var string
	 */
	private static $default_parent = null;

	/**
	 * Controls whether a page can be in the root of the site tree.
	 * Note: Value might be cached, see {@link $allowed_chilren}.
	 *
	 * @config
	 * @var bool
	 */
	private static $can_be_root = true;

	/**
	 * List of permission codes a user can have to allow a user to create a page of this type.
	 * Note: Value might be cached, see {@link $allowed_chilren}.
	 *
	 * @config
	 * @var array
	 */
	private static $need_permission = null;

	/**
	 * If you extend a class, and don't want to be able to select the old class
	 * in the cms, set this to the old class name. Eg, if you extended Product
	 * to make ImprovedProduct, then you would set $hide_ancestor to Product.
	 *
	 * @config
	 * @var string
	 */
	private static $hide_ancestor = null;

	private static $db = array(
		"URLSegment" => "Varchar(255)",
		"Title" => "Varchar(255)",
		"MenuTitle" => "Varchar(100)",
		"Content" => "HTMLText",
		"MetaDescription" => "Text",
		"ExtraMeta" => "HTMLText('meta, link')",
		"ShowInMenus" => "Boolean",
		"ShowInSearch" => "Boolean",
		"Sort" => "Int",
		"HasBrokenFile" => "Boolean",
		"HasBrokenLink" => "Boolean",
		"ReportClass" => "Varchar",
		"CanViewType" => "Enum('Anyone, LoggedInUsers, OnlyTheseUsers, Inherit', 'Inherit')",
		"CanEditType" => "Enum('LoggedInUsers, OnlyTheseUsers, Inherit', 'Inherit')",
	);

	private static $indexes = array(
		"URLSegment" => true,
	);

	private static $many_many = array(
		"LinkTracking" => "SiteTree",
		"ImageTracking" => "File",
		"ViewerGroups" => "Group",
		"EditorGroups" => "Group",
	);

	private static $belongs_many_many = array(
		"BackLinkTracking" => "SiteTree"
	);

	private static $many_many_extraFields = array(
		"LinkTracking" => array("FieldName" => "Varchar"),
		"ImageTracking" => array("FieldName" => "Varchar")
	);

	private static $casting = array(
		"Breadcrumbs" => "HTMLText",
		"LastEdited" => "SS_Datetime",
		"Created" => "SS_Datetime",
		'Link' => 'Text',
		'RelativeLink' => 'Text',
		'AbsoluteLink' => 'Text',
		'TreeTitle' => 'HTMLText',
	);

	private static $defaults = array(
		"ShowInMenus" => 1,
		"ShowInSearch" => 1,
		"CanViewType" => "Inherit",
		"CanEditType" => "Inherit"
	);

	private static $versioning = array(
		"Stage",  "Live"
	);

	private static $default_sort = "\"Sort\"";

	/**
	 * If this is false, the class cannot be created in the CMS by regular content authors, only by ADMINs.
	 * @var boolean
	* @config
	*/
	private static $can_create = true;

	/**
	 * Icon to use in the CMS page tree. This should be the full filename, relative to the webroot.
	 * Also supports custom CSS rule contents (applied to the correct selector for the tree UI implementation).
	 * 
	 * @see CMSMain::generateTreeStylingCSS()
	 * @config
	 * @var string
	 */
	private static $icon = null;
	
	/**
	 * @config
	 * @var String Description of the class functionality, typically shown to a user
	 * when selecting which page type to create. Translated through {@link provideI18nEntities()}.
	 */
	private static $description = 'Generic content page';

	private static $extensions = array(
		"Hierarchy",
		"Versioned('Stage', 'Live')",
		"SiteTreeLinkTracking"
	);
	
	private static $searchable_fields = array(
		'Title',
		'Content',
	);

	private static $field_labels = array(
		'URLSegment' => 'URL'
	);
	
	/**
	 * @config
	 */
	private static $nested_urls = true;
	
	/**
	 * @config
	*/
	private static $create_default_pages = true;
	
	/**
	 * This controls whether of not extendCMSFields() is called by getCMSFields.
	 */
	private static $runCMSFieldsExtensions = true;
	
	/**
	 * Cache for canView/Edit/Publish/Delete permissions.
	 * Keyed by permission type (e.g. 'edit'), with an array
	 * of IDs mapped to their boolean permission ability (true=allow, false=deny).
	 * See {@link batch_permission_check()} for details.
	 */
	private static $cache_permissions = array();

	/**
	 * @config
	 * @var boolean
	 */
	private static $enforce_strict_hierarchy = true;

	/**
	 * The value used for the meta generator tag.  Leave blank to omit the tag.
	 *
	 * @config
	 * @var string
	 */
	private static $meta_generator = 'SilverStripe - http://silverstripe.org';

	protected $_cache_statusFlags = null;
	
	/**
	 * Determines if the system should avoid orphaned pages
	 * by deleting all children when the their parent is deleted (TRUE),
	 * or rather preserve this data even if its not reachable through any navigation path (FALSE).
	 * 
	 * @deprecated 3.2 Use the "SiteTree.enforce_strict_hierarchy" config setting instead
	 * @param boolean
	 */
	static public function set_enforce_strict_hierarchy($to) {
		Deprecation::notice('3.2', 'Use the "SiteTree.enforce_strict_hierarchy" config setting instead');
		Config::inst()->update('SiteTree', 'enforce_strict_hierarchy', $to);
	}
	
	/**
	 * @deprecated 3.2 Use the "SiteTree.enforce_strict_hierarchy" config setting instead
	 * @return boolean
	 */
	static public function get_enforce_strict_hierarchy() {
		Deprecation::notice('3.2', 'Use the "SiteTree.enforce_strict_hierarchy" config setting instead');
		return Config::inst()->get('SiteTree', 'enforce_strict_hierarchy');
	}

	/**
	 * Returns TRUE if nested URLs (e.g. page/sub-page/) are currently enabled on this site.
	 *
	 * @deprecated 3.2 Use the "SiteTree.nested_urls" config setting instead
	 * @return bool
	 */
	static public function nested_urls() {
		Deprecation::notice('3.2', 'Use the "SiteTree.nested_urls" config setting instead');
		return Config::inst()->get('SiteTree', 'nested_urls');
	}
	
	/**
	 * @deprecated 3.2 Use the "SiteTree.nested_urls" config setting instead
	 */
	static public function enable_nested_urls() {
		Deprecation::notice('3.2', 'Use the "SiteTree.nested_urls" config setting instead');
		Config::inst()->update('SiteTree', 'nested_urls', true);
	}
	
	/**
	 * @deprecated 3.2 Use the "SiteTree.nested_urls" config setting instead
	 */
	static public function disable_nested_urls() {
		Deprecation::notice('3.2', 'Use the "SiteTree.nested_urls" config setting instead');
		Config::inst()->update('SiteTree', 'nested_urls', false);
	}
	
	/**
	 * Set the (re)creation of default pages on /dev/build
	 *
	 * @deprecated 3.2 Use the "SiteTree.create_default_pages" config setting instead
	 * @param bool $option
	 */
	static public function set_create_default_pages($option = true) {
		Deprecation::notice('3.2', 'Use the "SiteTree.create_default_pages" config setting instead');
		Config::inst()->update('SiteTree', 'create_default_pages', $option);
	}

	/**
	 * Return true if default pages should be created on /dev/build.
	 *
	 * @deprecated 3.2 Use the "SiteTree.create_default_pages" config setting instead
	 * @return bool
	 */
	static public function get_create_default_pages() {
		Deprecation::notice('3.2', 'Use the "SiteTree.create_default_pages" config setting instead');
		return Config::inst()->get('SiteTree', 'create_default_pages');
	}
	
	/**
	 * Fetches the {@link SiteTree} object that maps to a link.
	 *
	 * If you have enabled {@link SiteTree::config()->nested_urls} on this site, then you can use a nested link such as
	 * "about-us/staff/", and this function will traverse down the URL chain and grab the appropriate link.
	 *
	 * Note that if no model can be found, this method will fall over to a extended alternateGetByLink method provided
	 * by a extension attached to {@link SiteTree}
	 *
	 * @param string $link
	 * @param bool $cache
	 * @return SiteTree
	 */
	static public function get_by_link($link, $cache = true) {
		if(trim($link, '/')) {
			$link = trim(Director::makeRelative($link), '/');
		} else {
			$link = RootURLController::get_homepage_link();
		}
		
		$parts = Convert::raw2sql(preg_split('|/+|', $link));
		
		// Grab the initial root level page to traverse down from.
		$URLSegment = array_shift($parts);
		$sitetree   = DataObject::get_one (
			'SiteTree',
			"\"SiteTree\".\"URLSegment\" = '$URLSegment'" . (
				self::config()->nested_urls ? ' AND "SiteTree"."ParentID" = 0' : ''
			),
			$cache
		);
		
		/// Fall back on a unique URLSegment for b/c.
		if(!$sitetree
			&& self::config()->nested_urls
			&& $page = DataObject::get_one('SiteTree', "\"SiteTree\".\"URLSegment\" = '$URLSegment'", $cache)
		) {
			return $page;
		}
		
		// Attempt to grab an alternative page from extensions.
		if(!$sitetree) {
			$parentID = self::config()->nested_urls ? 0 : null;
			
			if($alternatives = singleton('SiteTree')->extend('alternateGetByLink', $URLSegment, $parentID)) {
				foreach($alternatives as $alternative) if($alternative) $sitetree = $alternative;
			}
			
			if(!$sitetree) return false;
		}
		
		// Check if we have any more URL parts to parse.
		if(!self::config()->nested_urls || !count($parts)) return $sitetree;
		
		// Traverse down the remaining URL segments and grab the relevant SiteTree objects.
		foreach($parts as $segment) {
			$next = DataObject::get_one (
				'SiteTree',
				"\"SiteTree\".\"URLSegment\" = '$segment' AND \"SiteTree\".\"ParentID\" = $sitetree->ID",
				$cache
			);
			
			if(!$next) {
				$parentID = (int) $sitetree->ID;
				
				if($alternatives = singleton('SiteTree')->extend('alternateGetByLink', $segment, $parentID)) {
					foreach($alternatives as $alternative) if($alternative) $next = $alternative;
				}
				
				if(!$next) return false;
			}
			
			$sitetree->destroy();
			$sitetree = $next;
		}
		
		return $sitetree;
	}
	
	/**
	 * Return a subclass map of SiteTree
	 * that shouldn't be hidden through
	 * {@link SiteTree::$hide_ancestor}
	 *
	 * @return array
	 */
	static public function page_type_classes() {
		$classes = ClassInfo::getValidSubClasses();

		$baseClassIndex = array_search('SiteTree', $classes);
		if($baseClassIndex !== FALSE) unset($classes[$baseClassIndex]);

		$kill_ancestors = array();

		// figure out if there are any classes we don't want to appear
		foreach($classes as $class) {
			$instance = singleton($class);

			// do any of the progeny want to hide an ancestor?
			if($ancestor_to_hide = $instance->stat('hide_ancestor')) {
				// note for killing later
				$kill_ancestors[] = $ancestor_to_hide;
			}
		}

		// If any of the descendents don't want any of the elders to show up, cruelly render the elders surplus to requirements.
		if($kill_ancestors) {
			$kill_ancestors = array_unique($kill_ancestors);
			foreach($kill_ancestors as $mark) {
				// unset from $classes
				$idx = array_search($mark, $classes);
				unset($classes[$idx]);
			}
		}

		return $classes;
	}
	
	/**
	 * Replace a "[sitetree_link id=n]" shortcode with a link to the page with the corresponding ID.
	 *
	 * @param array $arguments
	 * @param mixed $content
	 * @param object|null $parser
	 * @return string|void
	 */
	static public function link_shortcode_handler($arguments, $content = null, $parser = null) {
		if(!isset($arguments['id']) || !is_numeric($arguments['id'])) return;
		
		if (
			   !($page = DataObject::get_by_id('SiteTree', $arguments['id']))         // Get the current page by ID.
			&& !($page = Versioned::get_latest_version('SiteTree', $arguments['id'])) // Attempt link to old version.
			&& !($page = DataObject::get_one('ErrorPage', '"ErrorPage"."ErrorCode" = \'404\'')) // Link to 404 page.
		) {
			 return; // There were no suitable matches at all.
		}
		
		$link = Convert::raw2att($page->Link());
		
		if($content) {
			return sprintf('<a href="%s">%s</a>', $link, $parser->parse($content));
		} else {
			return $link;
		}
	}
	
	/**
	 * Return the link for this {@link SiteTree} object, with the {@link Director::baseURL()} included.
	 *
	 * @param string $action Optional controller action (method). 
	 *  Note: URI encoding of this parameter is applied automatically through template casting,
	 *  don't encode the passed parameter.
	 *  Please use {@link Controller::join_links()} instead to append GET parameters.
	 * @return string
	 */
	public function Link($action = null) {
		return Controller::join_links(Director::baseURL(), $this->RelativeLink($action));
	}
	
	/**
	 * Get the absolute URL for this page, including protocol and host.
	 *
	 * @param string $action See {@link Link()}
	 * @return string
	 */
	public function AbsoluteLink($action = null) {
		if($this->hasMethod('alternateAbsoluteLink')) {
			return $this->alternateAbsoluteLink($action);
		} else {
			return Director::absoluteURL($this->Link($action));
		}
	}
	
	/**
	 * Base link used for previewing. Defaults to absolute URL,
	 * in order to account for domain changes, e.g. on multi site setups.
	 * Does not contain hints about the stage, see {@link SilverStripeNavigator} for details.
	 * 
	 * @param string $action See {@link Link()}
	 * @return string
	 */
	public function PreviewLink($action = null) {
		if($this->hasMethod('alternatePreviewLink')) {
			return $this->alternatePreviewLink($action);
		} else {
			return $this->AbsoluteLink($action);
		}
	}
	
	/**
	 * Return the link for this {@link SiteTree} object relative to the SilverStripe root.
	 *
	 * By default, it this page is the current home page, and there is no action specified then this will return a link
	 * to the root of the site. However, if you set the $action parameter to TRUE then the link will not be rewritten
	 * and returned in its full form.
	 *
	 * @uses RootURLController::get_homepage_link()
	 * 
	 * @param string $action See {@link Link()}
	 * @return string
	 */
	public function RelativeLink($action = null) {
		if($this->ParentID && self::config()->nested_urls) {
			$base = $this->Parent()->RelativeLink($this->URLSegment);
		} elseif(!$action && $this->URLSegment == RootURLController::get_homepage_link()) {
			// Unset base for root-level homepages.
			// Note: Homepages with action parameters (or $action === true) 
			// need to retain their URLSegment.
			$base = null;
		} else {
			$base = $this->URLSegment;
		}
		
		$this->extend('updateRelativeLink', $base, $action);
		
		// Legacy support: If $action === true, retain URLSegment for homepages,
		// but don't append any action
		if($action === true) $action = null;

		return Controller::join_links($base, '/', $action);
	}
	
	/**
	 * Get the absolute URL for this page on the Live site.
	 */
	public function getAbsoluteLiveLink($includeStageEqualsLive = true) {
		$oldStage = Versioned::current_stage();
		Versioned::reading_stage('Live');
		$live = Versioned::get_one_by_stage('SiteTree', 'Live', '"SiteTree"."ID" = ' . $this->ID);
		if($live) {
			$link = $live->AbsoluteLink();
			if($includeStageEqualsLive) $link .= '?stage=Live';
		} else {
			$link = null;
		}

		Versioned::reading_stage($oldStage);
		return $link;
	}
	
	/**
	 * @return String
	 */
	public function CMSEditLink() {
		return Controller::join_links(singleton('CMSPageEditController')->Link('show'), $this->ID);
	}
	
		
	/**
	 * Return a CSS identifier generated from this page's link.
	 *
	 * @return string The URL segment
	 */
	public function ElementName() {
		return str_replace('/', '-', trim($this->RelativeLink(true), '/'));
	}
	
	/**
	 * Returns TRUE if this is the currently active page that is being used to handle a request.
	 *
	 * @return bool
	 */
	public function isCurrent() {
		return $this->ID ? $this->ID == Director::get_current_page()->ID : $this === Director::get_current_page();
	}
	
	/**
	 * Check if this page is in the currently active section (e.g. it is either current or one of it's children is
	 * currently being viewed.
	 *
	 * @return bool
	 */
	public function isSection() {
		return $this->isCurrent() || (
			Director::get_current_page() instanceof SiteTree && in_array($this->ID, Director::get_current_page()->getAncestors()->column())
		);
	}
	
	/**
	 * Return "link" or "current" depending on if this is the {@link SiteTree::isCurrent()} current page.
	 *
	 * @return string
	 */
	public function LinkOrCurrent() {
		return $this->isCurrent() ? 'current' : 'link';
	}
	
	/**
	 * Return "link" or "section" depending on if this is the {@link SiteTree::isSeciton()} current section.
	 *
	 * @return string
	 */
	public function LinkOrSection() {
		return $this->isSection() ? 'section' : 'link';
	}
	
	/**
	 * Return "link", "current" or section depending on if this page is the current page, or not on the current page but
	 * in the current section.
	 *
	 * @return string
	 */
	public function LinkingMode() {
		if($this->isCurrent()) {
			return 'current';
		} elseif($this->isSection()) {
			return 'section';
		} else {
			return 'link';
		}
	}
	
	/**
	 * Check if this page is in the given current section.
	 *
	 * @param string $sectionName Name of the section to check.
	 * @return boolean True if we are in the given section.
	 */
	public function InSection($sectionName) {
		$page = Director::get_current_page();
		while($page) {
			if($sectionName == $page->URLSegment)
				return true;
			$page = $page->Parent;
		}
		return false;
	}

	/**
	 * Create a duplicate of this node. Doesn't affect joined data - create a
	 * custom overloading of this if you need such behaviour.
	 *
	 * @param bool $doWrite
	 * @return SiteTree The duplicated object.
	 */
	 public function duplicate($doWrite = true) {
		
		$page = parent::duplicate(false);
		$page->Sort = 0;
		$this->invokeWithExtensions('onBeforeDuplicate', $page);
		
		if($doWrite) {
			$page->write();

			$page = $this->duplicateManyManyRelations($this, $page);
		}
		$this->invokeWithExtensions('onAfterDuplicate', $page);
		
		return $page;
	}


	/**
	 * Duplicates each child of this node recursively and returns the
	 * duplicate node.
	 *
	 * @return SiteTree The duplicated object.
	 */
	public function duplicateWithChildren() {
		$clone = $this->duplicate();
		$children = $this->AllChildren();

		if($children) {
			foreach($children as $child) {
				$childClone = method_exists($child, 'duplicateWithChildren')
					? $child->duplicateWithChildren()
					: $child->duplicate();
				$childClone->ParentID = $clone->ID;
				$childClone->write();
			}
		}

		return $clone;
	}


	/**
	 * Duplicate this node and its children as a child of the node with the
	 * given ID
	 *
	 * @param int $id ID of the new node's new parent
	 */
	public function duplicateAsChild($id) {
		$newSiteTree = $this->duplicate();
		$newSiteTree->ParentID = $id;
		$newSiteTree->Sort = 0;
		$newSiteTree->write();
	}
	
	/**
	 * Return a breadcrumb trail to this page. Excludes "hidden" pages
	 * (with ShowInMenus=0).
	 *
	 * @param int $maxDepth The maximum depth to traverse.
	 * @param boolean $unlinked Do not make page names links
	 * @param boolean|string $stopAtPageType ClassName of a page to stop the upwards traversal.
	 * @param boolean $showHidden Include pages marked with the attribute ShowInMenus = 0
	 * @return HTMLText The breadcrumb trail.
	 */
	public function Breadcrumbs($maxDepth = 20, $unlinked = false, $stopAtPageType = false, $showHidden = false) {
		$page = $this;
		$pages = array();
		
		while(
			$page  
 			&& (!$maxDepth || count($pages) < $maxDepth) 
 			&& (!$stopAtPageType || $page->ClassName != $stopAtPageType)
 		) {
			if($showHidden || $page->ShowInMenus || ($page->ID == $this->ID)) { 
				$pages[] = $page;
			}
			
			$page = $page->Parent;
		}
		
		$template = new SSViewer('BreadcrumbsTemplate');
		
		return $template->process($this->customise(new ArrayData(array(
			'Pages' => new ArrayList(array_reverse($pages))
		))));
	}

	/**
	 * Make this page a child of another page.
	 * 
	 * If the parent page does not exist, resolve it to a valid ID
	 * before updating this page's reference.
	 *
	 * @param SiteTree|int $item Either the parent object, or the parent ID
	 */
	public function setParent($item) {
		if(is_object($item)) {
			if (!$item->exists()) $item->write();
			$this->setField("ParentID", $item->ID);
		} else {
			$this->setField("ParentID", $item);
		}
	}
 	
	/**
	 * Get the parent of this page.
	 *
	 * @return SiteTree Parent of this page.
	 */
	public function getParent() {
		if ($this->getField("ParentID")) {
			return DataObject::get_one("SiteTree", "\"SiteTree\".\"ID\" = " . $this->getField("ParentID"));
		}
	}

	/**
	 * Return a string of the form "parent - page" or
	 * "grandparent - parent - page".
	 *
	 * @param int $level The maximum amount of levels to traverse.
	 * @param string $separator Seperating string
	 * @return string The resulting string
	 */
	public function NestedTitle($level = 2, $separator = " - ") {
		$item = $this;
		while($item && $level > 0) {
			$parts[] = $item->Title;
			$item = $item->Parent;
			$level--;
		}
		return implode($separator, array_reverse($parts));
	}

	/**
	 * This function should return true if the current user can execute this action. 
	 * It can be overloaded to customise the security model for an application.
	 * 
	 * Slightly altered from parent behaviour in {@link DataObject->can()}:
	 * - Checks for existence of a method named "can<$perm>()" on the object
	 * - Calls decorators and only returns for FALSE "vetoes"
	 * - Falls back to {@link Permission::check()}
	 * - Does NOT check for many-many relations named "Can<$perm>"
	 *
	 * @uses DataObjectDecorator->can()
	 *
	 * @param string $perm The permission to be checked, such as 'View'.
	 * @param Member $member The member whose permissions need checking.
	 *        Defaults to the currently logged in user.
	 * @return boolean True if the the member is allowed to do the given action.
	 */
	public function can($perm, $member = null) {
		if(!$member || !(is_a($member, 'Member')) || is_numeric($member)) {
			$member = Member::currentUserID();
		}

		if($member && Permission::checkMember($member, "ADMIN")) return true;
		
		if(is_string($perm) && method_exists($this, 'can' . ucfirst($perm))) {
			$method = 'can' . ucfirst($perm);
			return $this->$method($member);
		}
		
		$results = $this->extend('can', $member);
		if($results && is_array($results)) if(!min($results)) return false;

		return ($member && Permission::checkMember($member, $perm));
	}


	/**
	 * This function should return true if the current user can add children
	 * to this page. It can be overloaded to customise the security model for an
	 * application.
	 * 
	 * Denies permission if any of the following conditions is TRUE:
	 * - alternateCanAddChildren() on a extension returns FALSE
	 * - canEdit() is not granted
	 * - There are no classes defined in {@link $allowed_children}
	 * 
	 * @uses SiteTreeExtension->canAddChildren()
	 * @uses canEdit()
	 * @uses $allowed_children
	 *
	 * @param Member|int|null $member
	 * @return boolean True if the current user can add children.
	 */
	public function canAddChildren($member = null) {
		if(!$member || !(is_a($member, 'Member')) || is_numeric($member)) {
			$member = Member::currentUserID();
		}

		if($member && Permission::checkMember($member, "ADMIN")) return true;
		
		// Standard mechanism for accepting permission changes from extensions
		$extended = $this->extendedCan('canAddChildren', $member);
		if($extended !== null) return $extended;
		
		return $this->canEdit($member) && $this->stat('allowed_children') != 'none';
	}


	/**
	 * This function should return true if the current user can view this
	 * page. It can be overloaded to customise the security model for an
	 * application.
	 * 
	 * Denies permission if any of the following conditions is TRUE:
	 * - canView() on any extension returns FALSE
	 * - "CanViewType" directive is set to "Inherit" and any parent page return false for canView()
	 * - "CanViewType" directive is set to "LoggedInUsers" and no user is logged in
	 * - "CanViewType" directive is set to "OnlyTheseUsers" and user is not in the given groups
	 *
	 * @uses DataExtension->canView()
	 * @uses ViewerGroups()
	 *
	 * @param Member|int|null $member
	 * @return boolean True if the current user can view this page.
	 */
	public function canView($member = null) {
		if(!$member || !(is_a($member, 'Member')) || is_numeric($member)) {
			$member = Member::currentUserID();
		}

		// admin override
		if($member && Permission::checkMember($member, array("ADMIN", "SITETREE_VIEW_ALL"))) return true;

		// make sure we were loaded off an allowed stage

		// Were we definitely loaded directly off Live during our query?
		$fromLive = true;

		foreach (array('mode' => 'stage', 'stage' => 'live') as $param => $match) {
			$fromLive = $fromLive && strtolower((string)$this->getSourceQueryParam("Versioned.$param")) == $match;
		}

		if(!$fromLive
			&& !Session::get('unsecuredDraftSite')
			&& !Permission::checkMember($member, array('CMS_ACCESS_CMSMain', 'VIEW_DRAFT_CONTENT'))) {
			// If we weren't definitely loaded from live, and we can't view non-live content, we need to
			// check to make sure this version is the live version and so can be viewed
			if (Versioned::get_versionnumber_by_stage($this->class, 'Live', $this->ID) != $this->Version) return false;
		}

		// Standard mechanism for accepting permission changes from extensions
		$extended = $this->extendedCan('canView', $member);
		if($extended !== null) return $extended;
		
		// check for empty spec
		if(!$this->CanViewType || $this->CanViewType == 'Anyone') return true;

		// check for inherit
		if($this->CanViewType == 'Inherit') {
			if($this->ParentID) return $this->Parent()->canView($member);
			else return $this->getSiteConfig()->canView($member);
		}
		
		// check for any logged-in users
		if($this->CanViewType == 'LoggedInUsers' && $member) {
			return true;
		}
		
		// check for specific groups
		if($member && is_numeric($member)) $member = DataObject::get_by_id('Member', $member);
		if(
			$this->CanViewType == 'OnlyTheseUsers' 
			&& $member 
			&& $member->inGroups($this->ViewerGroups())
		) return true;
		
		return false;
	}
	
	/**
	 * Determines canView permissions for the latest version of this Page on a specific stage (see {@link Versioned}).
	 * Usually the stage is read from {@link Versioned::current_stage()}.
	 * 
	 * @todo Implement in CMS UI.
	 * 
	 * @param String $stage
	 * @param Member $member
	 * @return boolean
	 */
	public function canViewStage($stage = 'Live', $member = null) {
		$oldMode = Versioned::get_reading_mode();
		Versioned::reading_stage($stage);

		$versionFromStage = DataObject::get($this->class)->byID($this->ID);
		
		Versioned::set_reading_mode($oldMode);
		return $versionFromStage ? $versionFromStage->canView($member) : false;
	}

	/**
	 * This function should return true if the current user can delete this
	 * page. It can be overloaded to customise the security model for an
	 * application.
	 * 
	 * Denies permission if any of the following conditions is TRUE:
	 * - canDelete() returns FALSE on any extension
	 * - canEdit() returns FALSE
	 * - any descendant page returns FALSE for canDelete()
	 * 
	 * @uses canDelete()
	 * @uses SiteTreeExtension->canDelete()
	 * @uses canEdit()
	 *
	 * @param Member $member
	 * @return boolean True if the current user can delete this page.
	 */
	public function canDelete($member = null) {
		if($member instanceof Member) $memberID = $member->ID;
		else if(is_numeric($member)) $memberID = $member;
		else $memberID = Member::currentUserID();
		
		if($memberID && Permission::checkMember($memberID, array("ADMIN", "SITETREE_EDIT_ALL"))) {
			return true;
		}
		
		// Standard mechanism for accepting permission changes from extensions
		$extended = $this->extendedCan('canDelete', $memberID);
		if($extended !== null) return $extended;
				
		// Regular canEdit logic is handled by can_edit_multiple
		$results = self::can_delete_multiple(array($this->ID), $memberID);
		
		// If this page no longer exists in stage/live results won't contain the page.
		// Fail-over to false
		return isset($results[$this->ID]) ? $results[$this->ID] : false;
	}

	/**
	 * This function should return true if the current user can create new
	 * pages of this class. It can be overloaded to customise the security model for an
	 * application.
	 * 
	 * Denies permission if any of the following conditions is TRUE:
	 * - canCreate() returns FALSE on any extension
	 * - $can_create is set to FALSE and the site is not in "dev mode"
	 * 
	 * Use {@link canAddChildren()} to control behaviour of creating children under this page.
	 * 
	 * @uses $can_create
	 * @uses DataExtension->canCreate()
	 *
	 * @param Member $member
	 * @return boolean True if the current user can create pages on this class.
	 */
	public function canCreate($member = null) {
		if(!$member || !(is_a($member, 'Member')) || is_numeric($member)) {
			$member = Member::currentUserID();
		}

		if($member && Permission::checkMember($member, "ADMIN")) return true;
		
		// Standard mechanism for accepting permission changes from extensions
		$extended = $this->extendedCan('canCreate', $member);
		if($extended !== null) return $extended;
		
		return $this->stat('can_create') != false || Director::isDev();
	}


	/**
	 * This function should return true if the current user can edit this
	 * page. It can be overloaded to customise the security model for an
	 * application.
	 * 
	 * Denies permission if any of the following conditions is TRUE:
	 * - canEdit() on any extension returns FALSE
	 * - canView() return false
	 * - "CanEditType" directive is set to "Inherit" and any parent page return false for canEdit()
	 * - "CanEditType" directive is set to "LoggedInUsers" and no user is logged in or doesn't have the CMS_Access_CMSMAIN permission code
	 * - "CanEditType" directive is set to "OnlyTheseUsers" and user is not in the given groups
	 * 
	 * @uses canView()
	 * @uses EditorGroups()
	 * @uses DataExtension->canEdit()
	 *
	 * @param Member $member Set to FALSE if you want to explicitly test permissions without a valid user (useful for unit tests)
	 * @return boolean True if the current user can edit this page.
	 */
	public function canEdit($member = null) {
		if($member instanceof Member) $memberID = $member->ID;
		else if(is_numeric($member)) $memberID = $member;
		else $memberID = Member::currentUserID();
		
		if($memberID && Permission::checkMember($memberID, array("ADMIN", "SITETREE_EDIT_ALL"))) return true;
		
		// Standard mechanism for accepting permission changes from extensions
		$extended = $this->extendedCan('canEdit', $memberID);
		if($extended !== null) return $extended;

		if($this->ID) {
			// Regular canEdit logic is handled by can_edit_multiple
			$results = self::can_edit_multiple(array($this->ID), $memberID);

			// If this page no longer exists in stage/live results won't contain the page.
			// Fail-over to false
			return isset($results[$this->ID]) ? $results[$this->ID] : false;
			
		// Default for unsaved pages
		} else {
			return $this->getSiteConfig()->canEdit($member);
		}
	}

	/**
	 * This function should return true if the current user can publish this
	 * page. It can be overloaded to customise the security model for an
	 * application.
	 * 
	 * Denies permission if any of the following conditions is TRUE:
	 * - canPublish() on any extension returns FALSE
	 * - canEdit() returns FALSE
	 * 
	 * @uses SiteTreeExtension->canPublish()
	 *
	 * @param Member $member
	 * @return boolean True if the current user can publish this page.
	 */
	public function canPublish($member = null) {
		if(!$member || !(is_a($member, 'Member')) || is_numeric($member)) $member = Member::currentUser();
		
		if($member && Permission::checkMember($member, "ADMIN")) return true;

		// Standard mechanism for accepting permission changes from extensions
		$extended = $this->extendedCan('canPublish', $member);
		if($extended !== null) return $extended;

		// Normal case - fail over to canEdit()
		return $this->canEdit($member);
	}
	
	public function canDeleteFromLive($member = null) {
		// Standard mechanism for accepting permission changes from extensions
		$extended = $this->extendedCan('canDeleteFromLive', $member);
		if($extended !==null) return $extended;

		return $this->canPublish($member);
	}
	
	/**
	 * Stub method to get the site config, provided so it's easy to override
	 */
	public function getSiteConfig() {
		
		if($this->hasMethod('alternateSiteConfig')) {
			$altConfig = $this->alternateSiteConfig();
			if($altConfig) return $altConfig;
		}
		
		return SiteConfig::current_site_config();
	}

	/**
	 * Pre-populate the cache of canEdit, canView, canDelete, canPublish permissions.
	 * This method will use the static can_(perm)_multiple method for efficiency.
	 * 
	 * @param string $permission The permission: edit, view, publish, approve, etc.
	 * @param array $ids An array of page IDs
	 * @param callback|null $batchCallback The function/static method to call to calculate permissions.  Defaults
	 * to 'SiteTree::can_(permission)_multiple'
	 */
	static public function prepopulate_permission_cache($permission = 'CanEditType', $ids, $batchCallback = null) {
		if(!$batchCallback) $batchCallback = "SiteTree::can_{$permission}_multiple";
		
		if(is_callable($batchCallback)) {
			call_user_func($batchCallback, $ids, Member::currentUserID(), false);
		} else {
			user_error("SiteTree::prepopulate_permission_cache can't calculate '$permission' "
				. "with callback '$batchCallback'", E_USER_WARNING);
		}
	}
	
	/**
	 * This method is NOT a full replacement for the individual can*() methods, e.g. {@link canEdit()}.
	 * Rather than checking (potentially slow) PHP logic, it relies on the database group associations,
	 * e.g. the "CanEditType" field plus the "SiteTree_EditorGroups" many-many table.
	 * By batch checking multiple records, we can combine the queries efficiently.
	 * 
	 * Caches based on $typeField data. To invalidate the cache, use {@link SiteTree::reset()}
	 * or set the $useCached property to FALSE. 
	 * 
	 * @param Array $ids Of {@link SiteTree} IDs
	 * @param Int $memberID Member ID
	 * @param String $typeField A property on the data record, e.g. "CanEditType".
	 * @param String $groupJoinTable A many-many table name on this record, e.g. "SiteTree_EditorGroups"
	 * @param String $siteConfigMethod Method to call on {@link SiteConfig} for toplevel items, e.g. "canEdit"
	 * @param String $globalPermission If the member doesn't have this permission code, don't bother iterating deeper.
	 * @param Boolean $useCached
	 * @return Array An map of {@link SiteTree} ID keys, to boolean values
	 */
	static public function batch_permission_check($ids, $memberID, $typeField, $groupJoinTable, $siteConfigMethod, $globalPermission = 'CMS_ACCESS_CMSMain', $useCached = true) {
		// Sanitise the IDs
		$ids = array_filter($ids, 'is_numeric');
		
		// This is the name used on the permission cache
		// converts something like 'CanEditType' to 'edit'.
		$cacheKey = strtolower(substr($typeField, 3, -4)) . "-$memberID";

		// Default result: nothing editable
		$result = array_fill_keys($ids, false);
		if($ids) {

			// Look in the cache for values
			if($useCached && isset(self::$cache_permissions[$cacheKey])) {
				$cachedValues = array_intersect_key(self::$cache_permissions[$cacheKey], $result);
			
				// If we can't find everything in the cache, then look up the remainder separately
				$uncachedValues = array_diff_key($result, self::$cache_permissions[$cacheKey]);
				if($uncachedValues) {
					$cachedValues = self::batch_permission_check(array_keys($uncachedValues), $memberID, $typeField, $groupJoinTable, $siteConfigMethod, $globalPermission, false) + $cachedValues;
				}
				return $cachedValues;
			}
		
			// If a member doesn't have a certain permission then they can't edit anything
			if(!$memberID || ($globalPermission && !Permission::checkMember($memberID, $globalPermission))) {
				return $result;
			}

			$SQL_idList = implode($ids, ", ");

			// if page can't be viewed, don't grant edit permissions
			// to do - implement can_view_multiple(), so this can be enabled
			//$ids = array_keys(array_filter(self::can_view_multiple($ids, $memberID)));
		
			// Get the groups that the given member belongs to
			$groupIDs = DataObject::get_by_id('Member', $memberID)->Groups()->column("ID");
			$SQL_groupList = implode(", ", $groupIDs);
			if (!$SQL_groupList) $SQL_groupList = '0';
			
			$combinedStageResult = array();

			foreach(array('Stage', 'Live') as $stage) {
				// Start by filling the array with the pages that actually exist
				$table = ($stage=='Stage') ? "SiteTree" : "SiteTree_$stage";
				
				$result = array_fill_keys(
					($ids) ? DB::query("SELECT \"ID\" FROM \"$table\" WHERE \"ID\" IN (".implode(", ", $ids).")")->column() : array(), 
					false
				);
				
				// Get the uninherited permissions
				$uninheritedPermissions = Versioned::get_by_stage("SiteTree", $stage)
					->where("(\"$typeField\" = 'LoggedInUsers' OR
						(\"$typeField\" = 'OnlyTheseUsers' AND \"$groupJoinTable\".\"SiteTreeID\" IS NOT NULL))
						AND \"SiteTree\".\"ID\" IN ($SQL_idList)")
					->leftJoin($groupJoinTable, "\"$groupJoinTable\".\"SiteTreeID\" = \"SiteTree\".\"ID\" AND \"$groupJoinTable\".\"GroupID\" IN ($SQL_groupList)");
				
				if($uninheritedPermissions) {
					// Set all the relevant items in $result to true
					$result = array_fill_keys($uninheritedPermissions->column('ID'), true) + $result;
				}

				// Get permissions that are inherited
				$potentiallyInherited = Versioned::get_by_stage("SiteTree", $stage, "\"$typeField\" = 'Inherit'
					AND \"SiteTree\".\"ID\" IN ($SQL_idList)");

				if($potentiallyInherited) {
					// Group $potentiallyInherited by ParentID; we'll look at the permission of all those
					// parents and then see which ones the user has permission on
					$groupedByParent = array();
					foreach($potentiallyInherited as $item) {
						if($item->ParentID) {
							if(!isset($groupedByParent[$item->ParentID])) $groupedByParent[$item->ParentID] = array();
							$groupedByParent[$item->ParentID][] = $item->ID;
						} else {
							// Might return different site config based on record context, e.g. when subsites module is used
							$siteConfig = $item->getSiteConfig();
							$result[$item->ID] = $siteConfig->{$siteConfigMethod}($memberID);
						}
					}

					if($groupedByParent) {
						$actuallyInherited = self::batch_permission_check(array_keys($groupedByParent), $memberID, $typeField, $groupJoinTable, $siteConfigMethod);
						if($actuallyInherited) {
							$parentIDs = array_keys(array_filter($actuallyInherited));
							foreach($parentIDs as $parentID) {
								// Set all the relevant items in $result to true
								$result = array_fill_keys($groupedByParent[$parentID], true) + $result;
							}
						}
					}
				}
				
				$combinedStageResult = $combinedStageResult + $result;
				
			}
		}

		if(isset($combinedStageResult)) {
			// Cache the results
 			if(empty(self::$cache_permissions[$cacheKey])) self::$cache_permissions[$cacheKey] = array();
 			self::$cache_permissions[$cacheKey] = $combinedStageResult + self::$cache_permissions[$cacheKey];
 
  		return $combinedStageResult;
		} else {
			return array();
		}
	}
	
	/**
	 * Get the 'can edit' information for a number of SiteTree pages.
	 * 
	 * @param array $ids An array of IDs of the SiteTree pages to look up.
	 * @param int $memberID ID of member.
	 * @param bool $useCached Return values from the permission cache if they exist.
	 * @return array A map where the IDs are keys and the values are booleans stating whether the given
	 * page can be edited.
	 */
	static public function can_edit_multiple($ids, $memberID, $useCached = true) {
		return self::batch_permission_check($ids, $memberID, 'CanEditType', 'SiteTree_EditorGroups', 'canEdit', 'CMS_ACCESS_CMSMain', $useCached);
	}

	/**
	 * Get the 'can edit' information for a number of SiteTree pages.
	 * @param array $ids An array of IDs of the SiteTree pages to look up.
	 * @param int $memberID ID of member.
	 * @param bool $useCached Return values from the permission cache if they exist.
	 * @return array
	 */
	static public function can_delete_multiple($ids, $memberID, $useCached = true) {
		$deletable = array();
		$result = array_fill_keys($ids, false);
		$cacheKey = "delete-$memberID";
		
		// Look in the cache for values
		if($useCached && isset(self::$cache_permissions[$cacheKey])) {
			$cachedValues = array_intersect_key(self::$cache_permissions[$cacheKey], $result);
			
			// If we can't find everything in the cache, then look up the remainder separately
			$uncachedValues = array_diff_key($result, self::$cache_permissions[$cacheKey]);
			if($uncachedValues) {
				$cachedValues = self::can_delete_multiple(array_keys($uncachedValues), $memberID, false)
					+ $cachedValues;
			}
			return $cachedValues;
		}

		// You can only delete pages that you can edit
		$editableIDs = array_keys(array_filter(self::can_edit_multiple($ids, $memberID)));
		if($editableIDs) {
			$idList = implode(",", $editableIDs);
		
			// You can only delete pages whose children you can delete
			$childRecords = DataObject::get("SiteTree", "\"ParentID\" IN ($idList)");
			if($childRecords) {
				$children = $childRecords->map("ID", "ParentID");

				// Find out the children that can be deleted
				$deletableChildren = self::can_delete_multiple($children->keys(), $memberID);
				
				// Get a list of all the parents that have no undeletable children
				$deletableParents = array_fill_keys($editableIDs, true);
				foreach($deletableChildren as $id => $canDelete) {
					if(!$canDelete) unset($deletableParents[$children[$id]]);
				}

				// Use that to filter the list of deletable parents that have children
				$deletableParents = array_keys($deletableParents);

				// Also get the $ids that don't have children
				$parents = array_unique($children->values());
				$deletableLeafNodes = array_diff($editableIDs, $parents);

				// Combine the two
				$deletable = array_merge($deletableParents, $deletableLeafNodes);

			} else {
				$deletable = $editableIDs;
			}
		} else {
			$deletable = array();
		}
		
		// Convert the array of deletable IDs into a map of the original IDs with true/false as the
		// value
		return array_fill_keys($deletable, true) + array_fill_keys($ids, false);
	}
		

	/**
	 * Collate selected descendants of this page.
	 *
	 * {@link $condition} will be evaluated on each descendant, and if it is
	 * succeeds, that item will be added to the $collator array.
	 *
	 * @param string $condition The PHP condition to be evaluated. The page
	 *                          will be called $item
	 * @param array $collator An array, passed by reference, to collect all
	 *                        of the matching descendants.
	 * @return true|void
	 */
	public function collateDescendants($condition, &$collator) {
		if($children = $this->Children()) {
			foreach($children as $item) {
				if(eval("return $condition;")) $collator[] = $item;
				$item->collateDescendants($condition, $collator);
			}
			return true;
		}
	}


	/**
	 * Return the title, description, keywords and language metatags.
	 * 
	 * @todo Move <title> tag in separate getter for easier customization and more obvious usage
	 * 
	 * @param boolean|string $includeTitle Show default <title>-tag, set to false for custom templating
	 * @return string The XHTML metatags
	 */
	public function MetaTags($includeTitle = true) {
		$tags = "";
		if($includeTitle === true || $includeTitle == 'true') {
			$tags .= "<title>" . $this->Title . "</title>\n";
		}

		$generator = trim(Config::inst()->get('SiteTree', 'meta_generator'));
		if (!empty($generator)) {
			$tags .= "<meta name=\"generator\" content=\"" . Convert::raw2att($generator) . "\" />\n";
		}

		$charset = Config::inst()->get('ContentNegotiator', 'encoding');
		$tags .= "<meta http-equiv=\"Content-type\" content=\"text/html; charset=$charset\" />\n";
		if($this->MetaDescription) {
			$tags .= "<meta name=\"description\" content=\"" . Convert::raw2att($this->MetaDescription) . "\" />\n";
		}
		if($this->ExtraMeta) { 
			$tags .= $this->ExtraMeta . "\n";
		} 
		
		if(Permission::check('CMS_ACCESS_CMSMain') && in_array('CMSPreviewable', class_implements($this)) && !$this instanceof ErrorPage) {
			$tags .= "<meta name=\"x-page-id\" content=\"{$this->ID}\" />\n";
			$tags .= "<meta name=\"x-cms-edit-link\" content=\"" . $this->CMSEditLink() . "\" />\n";
		}

		$this->extend('MetaTags', $tags);

		return $tags;
	}


	/**
	 * Returns the object that contains the content that a user would
	 * associate with this page.
	 *
	 * Ordinarily, this is just the page itself, but for example on
	 * RedirectorPages or VirtualPages ContentSource() will return the page
	 * that is linked to.
	 *
	 * @return SiteTree The content source.
	 */
	public function ContentSource() {
		return $this;
	}


	/**
	 * Add default records to database.
	 *
	 * This function is called whenever the database is built, after the
	 * database tables have all been created. Overload this to add default
	 * records when the database is built, but make sure you call
	 * parent::requireDefaultRecords().
	 */
	public function requireDefaultRecords() {
		parent::requireDefaultRecords();
		
		// default pages
		if($this->class == 'SiteTree' && $this->config()->create_default_pages) {
			if(!SiteTree::get_by_link(Config::inst()->get('RootURLController', 'default_homepage_link'))) {
				$homepage = new Page();
				$homepage->Title = _t('SiteTree.DEFAULTHOMETITLE', 'Home');
				$homepage->Content = _t('SiteTree.DEFAULTHOMECONTENT', '<p>Welcome to SilverStripe! This is the default homepage. You can edit this page by opening <a href="admin/">the CMS</a>. You can now access the <a href="http://doc.silverstripe.org">developer documentation</a>, or begin <a href="http://doc.silverstripe.org/doku.php?id=tutorials">the tutorials.</a></p>');
				$homepage->URLSegment = Config::inst()->get('RootURLController', 'default_homepage_link');
				$homepage->Sort = 1;
				$homepage->write();
				$homepage->publish('Stage', 'Live');
				$homepage->flushCache();
				DB::alteration_message('Home page created', 'created');
			}

			if(DB::query("SELECT COUNT(*) FROM \"SiteTree\"")->value() == 1) {
				$aboutus = new Page();
				$aboutus->Title = _t('SiteTree.DEFAULTABOUTTITLE', 'About Us');
				$aboutus->Content = _t('SiteTree.DEFAULTABOUTCONTENT', '<p>You can fill this page out with your own content, or delete it and create your own pages.<br /></p>');
				$aboutus->Sort = 2;
				$aboutus->write();
				$aboutus->publish('Stage', 'Live');
				$aboutus->flushCache();
				DB::alteration_message('About Us page created', 'created');

				$contactus = new Page();
				$contactus->Title = _t('SiteTree.DEFAULTCONTACTTITLE', 'Contact Us');
				$contactus->Content = _t('SiteTree.DEFAULTCONTACTCONTENT', '<p>You can fill this page out with your own content, or delete it and create your own pages.<br /></p>');
				$contactus->Sort = 3;
				$contactus->write();
				$contactus->publish('Stage', 'Live');
				$contactus->flushCache();
				DB::alteration_message('Contact Us page created', 'created');
			}
		}
		
		// schema migration
		// @todo Move to migration task once infrastructure is implemented
		if($this->class == 'SiteTree') {
			$conn = DB::getConn();
			// only execute command if fields haven't been renamed to _obsolete_<fieldname> already by the task
			if(array_key_exists('Viewers', $conn->fieldList('SiteTree'))) {
				$task = new UpgradeSiteTreePermissionSchemaTask();
				$task->run(new SS_HTTPRequest('GET','/'));
			}
		}
	}


	//------------------------------------------------------------------------------------//

	protected function onBeforeWrite() {
		parent::onBeforeWrite();

		// If Sort hasn't been set, make this page come after it's siblings
		if(!$this->Sort) {
			$parentID = ($this->ParentID) ? $this->ParentID : 0;
			$this->Sort = DB::query("SELECT MAX(\"Sort\") + 1 FROM \"SiteTree\" WHERE \"ParentID\" = $parentID")->value();
		}

		// If there is no URLSegment set, generate one from Title
		if((!$this->URLSegment || $this->URLSegment == 'new-page') && $this->Title) {
			$this->URLSegment = $this->generateURLSegment($this->Title);
		} else if($this->isChanged('URLSegment', 2)) {
			// Do a strict check on change level, to avoid double encoding caused by
			// bogus changes through forceChange()
			$filter = URLSegmentFilter::create();
			$this->URLSegment = $filter->filter($this->URLSegment);
			// If after sanitising there is no URLSegment, give it a reasonable default
			if(!$this->URLSegment) $this->URLSegment = "page-$this->ID";
		}
		
		// Ensure that this object has a non-conflicting URLSegment value.
		$count = 2;
		while(!$this->validURLSegment()) {
			$this->URLSegment = preg_replace('/-[0-9]+$/', null, $this->URLSegment) . '-' . $count;
			$count++;
		}

		$this->syncLinkTracking();

		// Check to see if we've only altered fields that shouldn't affect versioning
		$fieldsIgnoredByVersioning = array('HasBrokenLink', 'Status', 'HasBrokenFile', 'ToDo', 'VersionID', 'SaveCount');
		$changedFields = array_keys($this->getChangedFields(true, 2));

		// This more rigorous check is inline with the test that write()
		// does to dedcide whether or not to write to the DB.  We use that
		// to avoid cluttering the system with a migrateVersion() call
		// that doesn't get used
		$oneChangedFields = array_keys($this->getChangedFields(true, 1));

		if($oneChangedFields && !array_diff($changedFields, $fieldsIgnoredByVersioning)) {
			// This will have the affect of preserving the versioning
			$this->migrateVersion($this->Version);
		}
	}
	
	public function syncLinkTracking() {
		$this->extend('augmentSyncLinkTracking');
	}
	
	public function onAfterWrite() {
		// Need to flush cache to avoid outdated versionnumber references
		$this->flushCache();
		
		$linkedPages = $this->VirtualPages();
		if($linkedPages) {
			// The only way after a write() call to determine if it was triggered by a writeWithoutVersion(),
			// which we have to pass on to the virtual page writes as well.
			$previous = ($this->Version > 1) ? Versioned::get_version($this->class, $this->ID, $this->Version-1) : null;
			$withoutVersion = $this->getExtensionInstance('Versioned')->_nextWriteWithoutVersion;
			foreach($linkedPages as $page) {
				 $page->copyFrom($page->CopyContentFrom());
				 if($withoutVersion) $page->writeWithoutVersion();
				 else $page->write();
			}
		}
		
		parent::onAfterWrite();
	}
	
	public function onBeforeDelete() {
		parent::onBeforeDelete();
		
		// If deleting this page, delete all its children.
		if(SiteTree::config()->enforce_strict_hierarchy && $children = $this->Children()) {
			foreach($children as $child) {
				$child->delete();
			}
		}
	}
	
	
	public function onAfterDelete() {
		// Need to flush cache to avoid outdated versionnumber references
		$this->flushCache();
		
		// Need to mark pages depending to this one as broken
		$dependentPages = $this->DependentPages();
		if($dependentPages) foreach($dependentPages as $page) {
			// $page->write() calls syncLinkTracking, which does all the hard work for us.
			$page->write();
		}
		
		parent::onAfterDelete();
	}

	public function flushCache($persistent = true) {
		parent::flushCache($persistent);
		$this->_cache_statusFlags = null;
	}
	
	public function validate() {
		$result = parent::validate();

		// Allowed children validation 
		$parent = $this->getParent();
		if($parent && $parent->exists()) {
			// No need to check for subclasses or instanceof, as allowedChildren() already 
			// deconstructs any inheritance trees already.
			$allowed = $parent->allowedChildren();
			$subject = ($this instanceof VirtualPage) ? $this->CopyContentFrom() : $this;
			if(!in_array($subject->ClassName, $allowed)) {
				
				$result->error(
					_t(
						'SiteTree.PageTypeNotAllowed', 
						'Page type "{type}" not allowed as child of this parent page', 
						array('type' => $subject->i18n_singular_name())
					),
					'ALLOWED_CHILDREN'
				);
			}
		}

		// "Can be root" validation
		if(!$this->stat('can_be_root') && !$this->ParentID) {
			$result->error(
				_t(
					'SiteTree.PageTypNotAllowedOnRoot', 
					'Page type "{type}" is not allowed on the root level', 
					array('type' => $this->i18n_singular_name())
				),
				'CAN_BE_ROOT'
			);
		}
		
		return $result;
	}
	
	/**
	 * Returns TRUE if this object has a URLSegment value that does not conflict with any other objects. This methods
	 * checks for:
	 *   - A page with the same URLSegment that has a conflict.
	 *   - Conflicts with actions on the parent page.
	 *   - A conflict caused by a root page having the same URLSegment as a class name.
	 *
	 * @return bool
	 */
	public function validURLSegment() {
		if(self::config()->nested_urls && $parent = $this->Parent()) {
			if($controller = ModelAsController::controller_for($parent)) {
				if($controller instanceof Controller && $controller->hasAction($this->URLSegment)) return false;
			}
		}
		
		if(!self::config()->nested_urls || !$this->ParentID) {
			if(class_exists($this->URLSegment) && is_subclass_of($this->URLSegment, 'RequestHandler')) return false;
		}
		
		$IDFilter     = ($this->ID) ? "AND \"SiteTree\".\"ID\" <> $this->ID" :  null;
		$parentFilter = null;
		
		if(self::config()->nested_urls) {
			if($this->ParentID) {
				$parentFilter = " AND \"SiteTree\".\"ParentID\" = $this->ParentID";
			} else {
				$parentFilter = ' AND "SiteTree"."ParentID" = 0';
			}
		}
		
		$votes = array_filter(
			(array)$this->extend('augmentValidURLSegment'), 
			function($v) {return !is_null($v);}
		);
		if($votes) {
			return min($votes);
		}

		$existingPage = DataObject::get_one(
			'SiteTree', 
			"\"SiteTree\".\"URLSegment\" = '$this->URLSegment' $IDFilter $parentFilter"
		);

		return !($existingPage);
		}
		
	/**
	 * Generate a URL segment based on the title provided.
	 * 
	 * If {@link Extension}s wish to alter URL segment generation, they can do so by defining
	 * updateURLSegment(&$url, $title).  $url will be passed by reference and should be modified.
	 * $title will contain the title that was originally used as the source of this generated URL.
	 * This lets extensions either start from scratch, or incrementally modify the generated URL.
	 * 
	 * @param string $title Page title.
	 * @return string Generated url segment
	 */
	public function generateURLSegment($title){
		$filter = URLSegmentFilter::create();
		$t = $filter->filter($title);
		
		// Fallback to generic page name if path is empty (= no valid, convertable characters)
		if(!$t || $t == '-' || $t == '-1') $t = "page-$this->ID";
		
		// Hook for extensions
		$this->extend('updateURLSegment', $t, $title);
		
		return $t;
	}
	
	/**
	 * @return string
	 */
	public function getStageURLSegment() {
		$stageRecord = Versioned::get_one_by_stage(
			'SiteTree', 
			'Stage', 
			"\"SiteTree\".\"ID\" = $this->ID"
		);
		return ($stageRecord) ? $stageRecord->URLSegment : null;
	}
	
	/**
	 * @return string
	 */
	public function getLiveURLSegment() {
		$liveRecord = Versioned::get_one_by_stage(
			'SiteTree', 
			'Live', 
			"\"SiteTree\".\"ID\" = $this->ID"
		);
		return ($liveRecord) ? $liveRecord->URLSegment : null;
	}
	
	/**
	 * Rewrite a file URL on this page, after its been renamed.
	 * Triggers the onRenameLinkedAsset action on extensions.
	 */
	public function rewriteFileURL($old, $new) {
		$fields = $this->inheritedDatabaseFields();
		// Update the content without actually creating a new version
		foreach(array("SiteTree_Live", "SiteTree") as $table) {
			// Published site
			$published = DB::query("SELECT * FROM  \"$table\" WHERE \"ID\" = $this->ID")->record();
			$origPublished = $published;

			foreach($fields as $fieldName => $fieldType) {
				if ($fieldType != 'HTMLText') continue;

				// TODO: This doesn't work for HTMLText fields on other tables.
				if(isset($published[$fieldName])) {
					$published[$fieldName] = str_replace($old, $new, $published[$fieldName], $numReplaced);
					if($numReplaced) {
						DB::query("UPDATE \"$table\" SET \"$fieldName\" = '" 
							. Convert::raw2sql($published[$fieldName]) . "' WHERE \"ID\" = $this->ID");
							
						// Tell static caching to update itself
						if($table == 'SiteTree_Live') {
							$publishedClass = $origPublished['ClassName'];
							$origPublishedObj = new $publishedClass($origPublished);
							$this->invokeWithExtensions('onRenameLinkedAsset', $origPublishedObj);
						}
					}
				}
			}
		}
	}
	
	/**
	 * Returns the pages that depend on this page.
	 * This includes virtual pages, pages that link to it, etc.
	 * 
	 * @param bool $includeVirtuals Set to false to exlcude virtual pages.
	 * @return ArrayList
	 */
	public function DependentPages($includeVirtuals = true) {
		if(class_exists('Subsite')) {
			$origDisableSubsiteFilter = Subsite::$disable_subsite_filter;
			Subsite::disable_subsite_filter(true);
		}
		
		// Content links
        $items = new ArrayList();

        // We merge all into a regular SS_List, because DataList doesn't support merge
        if($contentLinks = $this->BackLinkTracking()) {
        	$linkList = new ArrayList();
            foreach($contentLinks as $item) {
            	$item->DependentLinkType = 'Content link';
            	$linkList->push($item);
        }
			$items->merge($linkList);
        }
		
		// Virtual pages
		if($includeVirtuals) {
			$virtuals = $this->VirtualPages();
			if($virtuals) {
				$virtualList = new ArrayList();
				foreach($virtuals as $item) {
					$item->DependentLinkType = 'Virtual page';
					$virtualList->push($item);
			}
				$items->merge($virtualList);
		}
		}

		// Redirector pages
		$redirectors = DataObject::get("RedirectorPage", "\"RedirectorPage\".\"RedirectionType\" = 'Internal' AND \"LinkToID\" = $this->ID");
		if($redirectors) {
			$redirectorList = new ArrayList();
			foreach($redirectors as $item) {
				$item->DependentLinkType = 'Redirector page';
				$redirectorList->push($item);
		}
			$items->merge($redirectorList);
		}

		if(class_exists('Subsite')) Subsite::disable_subsite_filter($origDisableSubsiteFilter);
		
		return $items;
	}
	
	/**
	 * Return the number of {@link DependentPages()}
	 * 
	 * @deprecated 3.1 Use DependentPages()->Count() instead.
	 *
	 * @param bool $includeVirtuals Set to false to exlcude virtual pages.
	 * @return ArrayList
	 */
	public function DependentPagesCount($includeVirtuals = true) {
		Deprecation::notice('3.1', 'Use SiteTree->DependentPages()->Count() instead.');
		return $this->DependentPages($includeVirtuals)->Count();
	}

	/**
	 * Return all virtual pages that link to this page
	 */
	public function VirtualPages() {
		if(!$this->ID) return null;
		if(class_exists('Subsite')) {
			return Subsite::get_from_all_subsites('VirtualPage', "\"CopyContentFromID\" = " . (int)$this->ID);
		} elseif(class_exists('VirtualPage')) {
			return DataObject::get('VirtualPage', "\"CopyContentFromID\" = " . (int)$this->ID);
		}else{
			return null;
		}
	}

	/**
	 * Returns a FieldList with which to create the main editing form.
	 *
	 * You can override this in your child classes to add extra fields - first
	 * get the parent fields using parent::getCMSFields(), then use
	 * addFieldToTab() on the FieldList.
	 * 
	 * See {@link getSettingsFields()} for a different set of fields
	 * concerned with configuration aspects on the record, e.g. access control
	 *
	 * @return FieldList The fields to be displayed in the CMS.
	 */
	public function getCMSFields() {
		require_once("forms/Form.php");
		// Status / message
		// Create a status message for multiple parents
		if($this->ID && is_numeric($this->ID)) {
			$linkedPages = $this->VirtualPages();

			$parentPageLinks = array();

			if($linkedPages->Count() > 0) {
				foreach($linkedPages as $linkedPage) {
					$parentPage = $linkedPage->Parent;
					if($parentPage) {
						if($parentPage->ID) {
							$parentPageLinks[] = "<a class=\"cmsEditlink\" href=\"admin/pages/edit/show/$linkedPage->ID\">{$parentPage->Title}</a>";
						} else {
							$parentPageLinks[] = "<a class=\"cmsEditlink\" href=\"admin/pages/edit/show/$linkedPage->ID\">" .
								_t('SiteTree.TOPLEVEL', 'Site Content (Top Level)') .
								"</a>";
						}
					}
				}

				$lastParent = array_pop($parentPageLinks);
				$parentList = "'$lastParent'";

				if(count($parentPageLinks) > 0) {
					$parentList = "'" . implode("', '", $parentPageLinks) . "' and "
						. $parentList;
				}

				$statusMessage[] = _t(
					'SiteTree.APPEARSVIRTUALPAGES', 
					"This content also appears on the virtual pages in the {title} sections.",
					array('title' => $parentList)
				);
			}
		}

		if($this->HasBrokenLink || $this->HasBrokenFile) {
			$statusMessage[] = _t('SiteTree.HASBROKENLINKS', "This page has broken links.");
		}

		$dependentNote = '';
		$dependentTable = new LiteralField('DependentNote', '<p></p>');
		
		// Create a table for showing pages linked to this one
		$dependentPages = $this->DependentPages();
		$dependentPagesCount = $dependentPages->Count();
		if($dependentPagesCount) {
			$dependentColumns = array(
				'Title' => $this->fieldLabel('Title'),
				'AbsoluteLink' => _t('SiteTree.DependtPageColumnURL', 'URL'),
				'DependentLinkType' => _t('SiteTree.DependtPageColumnLinkType', 'Link type'),
			);
			if(class_exists('Subsite')) $dependentColumns['Subsite.Title'] = singleton('Subsite')->i18n_singular_name();
			
			$dependentNote = new LiteralField('DependentNote', '<p>' . _t('SiteTree.DEPENDENT_NOTE', 'The following pages depend on this page. This includes virtual pages, redirector pages, and pages with content links.') . '</p>');
			$dependentTable = GridField::create(
				'DependentPages',
				false,
				$dependentPages
			);
			$dependentTable->getConfig()->getComponentByType('GridFieldDataColumns')
				->setDisplayFields($dependentColumns)
				->setFieldFormatting(array(
					'Title' => function($value, &$item) {
						return sprintf(
							'<a href=\"admin/pages/edit/show/%d\">%s</a>',
							(int)$item->ID,
							Convert::raw2xml($item->Title)
						);
					},
					'AbsoluteLink' => function($value, &$item) {
						return sprintf(
							'<a href=\"%s\">%s</a>',
							Convert::raw2xml($value),
							Convert::raw2xml($value)
						);
					}
				));
		}
		
		$baseLink = Controller::join_links (
			Director::absoluteBaseURL(),
			(self::config()->nested_urls && $this->ParentID ? $this->Parent()->RelativeLink(true) : null)
		);
		
		$urlsegment = new SiteTreeURLSegmentField("URLSegment", $this->fieldLabel('URLSegment'));
		$urlsegment->setURLPrefix($baseLink);
		$helpText = (self::config()->nested_urls && count($this->Children())) ? $this->fieldLabel('LinkChangeNote') : '';
		if(!Config::inst()->get('URLSegmentFilter', 'default_allow_multibyte')) {
			$helpText .= $helpText ? '<br />' : '';
			$helpText .= _t('SiteTreeURLSegmentField.HelpChars', ' Special characters are automatically converted or removed.');
		}
		$urlsegment->setHelpText($helpText);
		
		$fields = new FieldList(
			$rootTab = new TabSet("Root",
				$tabMain = new Tab('Main',
					new TextField("Title", $this->fieldLabel('Title')),
					$urlsegment,
					new TextField("MenuTitle", $this->fieldLabel('MenuTitle')),
					$htmlField = new HtmlEditorField("Content", _t('SiteTree.HTMLEDITORTITLE', "Content", 'HTML editor title')),
					ToggleCompositeField::create('Metadata', _t('SiteTree.MetadataToggle', 'Metadata'),
						array(
							$metaFieldDesc = new TextareaField("MetaDescription", $this->fieldLabel('MetaDescription')),
							$metaFieldExtra = new TextareaField("ExtraMeta",$this->fieldLabel('ExtraMeta'))
						)
					)->setHeadingLevel(4)
				),
				$tabDependent = new Tab('Dependent',
					$dependentNote,
					$dependentTable
				)
			)
		);
		$htmlField->addExtraClass('stacked');
		
		// Help text for MetaData on page content editor
		$metaFieldDesc
			->setRightTitle(
				_t(
					'SiteTree.METADESCHELP', 
					"Search engines use this content for displaying search results (although it will not influence their ranking)."
				)
			)
			->addExtraClass('help');
		$metaFieldExtra
			->setRightTitle(
				_t(
					'SiteTree.METAEXTRAHELP', 
					"HTML tags for additional meta information. For example &lt;meta name=\"customName\" content=\"your custom content here\" /&gt;"
				)
			)
			->addExtraClass('help');

		// Conditional dependent pages tab
		if($dependentPagesCount) $tabDependent->setTitle(_t('SiteTree.TABDEPENDENT', "Dependent pages") . " ($dependentPagesCount)");
		else $fields->removeFieldFromTab('Root', 'Dependent');
		
		$tabMain->setTitle(_t('SiteTree.TABCONTENT', "Main Content"));

		if($this->ObsoleteClassName) {
			$obsoleteWarning = _t(
				'SiteTree.OBSOLETECLASS',
				"This page is of obsolete type {type}. Saving will reset its type and you may lose data",
				array('type' => $this->ObsoleteClassName)
			);

			$fields->addFieldToTab(
				"Root.Main",
				new LiteralField("ObsoleteWarningHeader", "<p class=\"message warning\">$obsoleteWarning</p>"),
				"Title"
			);
		}

		if(file_exists(BASE_PATH . '/install.php')) {
			$fields->addFieldToTab("Root.Main", new LiteralField("InstallWarningHeader", 
				"<p class=\"message warning\">" . _t("SiteTree.REMOVE_INSTALL_WARNING", 
				"Warning: You should remove install.php from this SilverStripe install for security reasons.")
				. "</p>"), "Title");
		}

		// Backwards compat: Rewrite nested "Content" tabs to toplevel
		$fields->setTabPathRewrites(array(
			'/^Root\.Content\.Main$/' => 'Root.Main',
			'/^Root\.Content\.([^.]+)$/' => 'Root.\\1',
		));
		
		if(self::$runCMSFieldsExtensions) {
			$this->extend('updateCMSFields', $fields);
		}

		return $fields;
	}
	
	
	/**
	 * Returns fields related to configuration aspects on this record, e.g. access control.
	 * See {@link getCMSFields()} for content-related fields.
	 * 
	 * @return FieldList
	 */
	public function getSettingsFields() {
		$groupsMap = array();
		foreach(Group::get() as $group) {
			// Listboxfield values are escaped, use ASCII char instead of &raquo;
			$groupsMap[$group->ID] = $group->getBreadcrumbs(' > ');
		}
		asort($groupsMap);
		
		$fields = new FieldList(
			$rootTab = new TabSet("Root",
				$tabBehaviour = new Tab('Settings',
					new DropdownField(
						"ClassName", 
						$this->fieldLabel('ClassName'), 
						$this->getClassDropdown()
					),
					$parentTypeSelector = new CompositeField(
						new OptionsetField("ParentType", _t("SiteTree.PAGELOCATION", "Page location"), array(
							"root" => _t("SiteTree.PARENTTYPE_ROOT", "Top-level page"),
							"subpage" => _t("SiteTree.PARENTTYPE_SUBPAGE", "Sub-page underneath a parent page"),
						)),
						$parentIDField = new TreeDropdownField("ParentID", $this->fieldLabel('ParentID'), 'SiteTree', 'ID', 'MenuTitle')
					),
					$visibility = new FieldGroup(
						new CheckboxField("ShowInMenus", $this->fieldLabel('ShowInMenus')),
						new CheckboxField("ShowInSearch", $this->fieldLabel('ShowInSearch'))
					),
					$viewersOptionsField = new OptionsetField(
						"CanViewType", 
						_t('SiteTree.ACCESSHEADER', "Who can view this page?")
					),
					$viewerGroupsField = ListboxField::create("ViewerGroups", _t('SiteTree.VIEWERGROUPS', "Viewer Groups"))
						->setMultiple(true)
						->setSource($groupsMap)
						->setAttribute(
							'data-placeholder', 
							_t('SiteTree.GroupPlaceholder', 'Click to select group')
						),
					$editorsOptionsField = new OptionsetField(
						"CanEditType", 
						_t('SiteTree.EDITHEADER', "Who can edit this page?")
					),
					$editorGroupsField = ListboxField::create("EditorGroups", _t('SiteTree.EDITORGROUPS', "Editor Groups"))
						->setMultiple(true)
						->setSource($groupsMap)
						->setAttribute(
							'data-placeholder', 
							_t('SiteTree.GroupPlaceholder', 'Click to select group')
				)
			)
			)
		);
		
		$visibility->setTitle($this->fieldLabel('Visibility'));
		
		/*
		 * This filter ensures that the ParentID dropdown selection does not show this node,
		 * or its descendents, as this causes vanishing bugs.
		 */
		$parentIDField->setFilterFunction(create_function('$node', "return \$node->ID != {$this->ID};"));
		$parentTypeSelector->addExtraClass('parentTypeSelector');
		
		$tabBehaviour->setTitle(_t('SiteTree.TABBEHAVIOUR', "Behavior"));
		
		// Make page location fields read-only if the user doesn't have the appropriate permission
		if(!Permission::check("SITETREE_REORGANISE")) {
			$fields->makeFieldReadonly('ParentType');
			if($this->ParentType == 'root') {
				$fields->removeByName('ParentID');
			} else {
				$fields->makeFieldReadonly('ParentID');
			}
		}
		
		$viewersOptionsSource = array();
		$viewersOptionsSource["Inherit"] = _t('SiteTree.INHERIT', "Inherit from parent page");
		$viewersOptionsSource["Anyone"] = _t('SiteTree.ACCESSANYONE', "Anyone");
		$viewersOptionsSource["LoggedInUsers"] = _t('SiteTree.ACCESSLOGGEDIN', "Logged-in users");
		$viewersOptionsSource["OnlyTheseUsers"] = _t('SiteTree.ACCESSONLYTHESE', "Only these people (choose from list)");
		$viewersOptionsField->setSource($viewersOptionsSource);
		
		$editorsOptionsSource = array();
		$editorsOptionsSource["Inherit"] = _t('SiteTree.INHERIT', "Inherit from parent page");
		$editorsOptionsSource["LoggedInUsers"] = _t('SiteTree.EDITANYONE', "Anyone who can log-in to the CMS");
		$editorsOptionsSource["OnlyTheseUsers"] = _t('SiteTree.EDITONLYTHESE', "Only these people (choose from list)");
		$editorsOptionsField->setSource($editorsOptionsSource);

		if(!Permission::check('SITETREE_GRANT_ACCESS')) {
			$fields->makeFieldReadonly($viewersOptionsField);
			if($this->CanViewType == 'OnlyTheseUsers') {
				$fields->makeFieldReadonly($viewerGroupsField);
			} else {
				$fields->removeByName('ViewerGroups');
			}
			
			$fields->makeFieldReadonly($editorsOptionsField);
			if($this->CanEditType == 'OnlyTheseUsers') {
				$fields->makeFieldReadonly($editorGroupsField);
			} else {
				$fields->removeByName('EditorGroups');
			}
		}
		
		if(self::$runCMSFieldsExtensions) {
			$this->extend('updateSettingsFields', $fields);
		}
		
		return $fields;
	}
	
	/**
	 *
	 * @param boolean $includerelations a boolean value to indicate if the labels returned include relation fields
	 * @return array|string
	 */
	public function fieldLabels($includerelations = true) {
		$cacheKey = $this->class . '_' . $includerelations;
		if(!isset(self::$_cache_field_labels[$cacheKey])) {
			$labels = parent::fieldLabels($includerelations);
			$labels['Title'] = _t('SiteTree.PAGETITLE', "Page name");
			$labels['MenuTitle'] = _t('SiteTree.MENUTITLE', "Navigation label");
			$labels['MetaDescription'] = _t('SiteTree.METADESC', "Meta Description");
			$labels['ExtraMeta'] = _t('SiteTree.METAEXTRA', "Custom Meta Tags");
			$labels['ClassName'] = _t('SiteTree.PAGETYPE', "Page type", 'Classname of a page object');
			$labels['ParentType'] = _t('SiteTree.PARENTTYPE', "Page location");
			$labels['ParentID'] = _t('SiteTree.PARENTID', "Parent page");
			$labels['ShowInMenus'] =_t('SiteTree.SHOWINMENUS', "Show in menus?");
			$labels['ShowInSearch'] = _t('SiteTree.SHOWINSEARCH', "Show in search?");
			$labels['ProvideComments'] = _t('SiteTree.ALLOWCOMMENTS', "Allow comments on this page?");
			$labels['ViewerGroups'] = _t('SiteTree.VIEWERGROUPS', "Viewer Groups");
			$labels['EditorGroups'] = _t('SiteTree.EDITORGROUPS', "Editor Groups");
			$labels['URLSegment'] = _t('SiteTree.URLSegment', 'URL Segment', 'URL for this page');
			$labels['Content'] = _t('SiteTree.Content', 'Content', 'Main HTML Content for a page');
			$labels['CanViewType'] = _t('SiteTree.Viewers', 'Viewers Groups');
			$labels['CanEditType'] = _t('SiteTree.Editors', 'Editors Groups');
			$labels['Comments'] = _t('SiteTree.Comments', 'Comments');
			$labels['Visibility'] = _t('SiteTree.Visibility', 'Visibility');
			$labels['LinkChangeNote'] = _t (
				'SiteTree.LINKCHANGENOTE', 'Changing this page\'s link will also affect the links of all child pages.'
			);
			
			if($includerelations){
				$labels['Parent'] = _t('SiteTree.has_one_Parent', 'Parent Page', 'The parent page in the site hierarchy');
				$labels['LinkTracking'] = _t('SiteTree.many_many_LinkTracking', 'Link Tracking');
				$labels['ImageTracking'] = _t('SiteTree.many_many_ImageTracking', 'Image Tracking');
				$labels['BackLinkTracking'] = _t('SiteTree.many_many_BackLinkTracking', 'Backlink Tracking');
			}

			self::$_cache_field_labels[$cacheKey] = $labels;
		}

		return self::$_cache_field_labels[$cacheKey];
	}

	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Get the actions available in the CMS for this page - eg Save, Publish.
	 *
	 * Frontend scripts and styles know how to handle the following FormFields:
	 * * top-level FormActions appear as standalone buttons
	 * * top-level CompositeField with FormActions within appear as grouped buttons
	 * * TabSet & Tabs appear as a drop ups
	 * * FormActions within the Tab are restyled as links
	 * * major actions can provide alternate states for richer presentation (see ssui.button widget extension).
	 *
	 * @return FieldList The available actions for this page.
	 */
	public function getCMSActions() {
		$existsOnLive = $this->getExistsOnLive();

		// Major actions appear as buttons immediately visible as page actions.
		$majorActions = CompositeField::create()->setName('MajorActions')->setTag('fieldset')->addExtraClass('ss-ui-buttonset');

		// Minor options are hidden behind a drop-up and appear as links (although they are still FormActions).
		$rootTabSet = new TabSet('ActionMenus');
		$moreOptions = new Tab(
			'MoreOptions', 
			_t('SiteTree.MoreOptions', 'More options', 'Expands a view for more buttons')
		);
		$rootTabSet->push($moreOptions);
		$rootTabSet->addExtraClass('ss-ui-action-tabset action-menus');

		// Render page information into the "more-options" drop-up, on the top.
		$live = Versioned::get_one_by_stage('SiteTree', 'Live', "\"SiteTree\".\"ID\"='$this->ID'");
		$moreOptions->push(
			new LiteralField('Information',
				$this->customise(array(
					'Live' => $live,
					'ExistsOnLive' => $existsOnLive
				))->renderWith('SiteTree_Information')
			)
		);

		// "readonly"/viewing version that isn't the current version of the record
		$stageOrLiveRecord = Versioned::get_one_by_stage($this->class, Versioned::current_stage(), sprintf('"SiteTree"."ID" = %d', $this->ID));
		if($stageOrLiveRecord && $stageOrLiveRecord->Version != $this->Version) {
			$moreOptions->push(FormAction::create('email', _t('CMSMain.EMAIL', 'Email')));
			$moreOptions->push(FormAction::create('rollback', _t('CMSMain.ROLLBACK', 'Roll back to this version')));

			$actions = new FieldList(array($majorActions, $rootTabSet));

			// getCMSActions() can be extended with updateCMSActions() on a extension
			$this->extend('updateCMSActions', $actions);

			return $actions;
		}

		if($this->isPublished() && $this->canPublish() && !$this->IsDeletedFromStage && $this->canDeleteFromLive()) {
			// "unpublish"
			$moreOptions->push(
				FormAction::create('unpublish', _t('SiteTree.BUTTONUNPUBLISH', 'Unpublish'), 'delete')
					->setDescription(_t('SiteTree.BUTTONUNPUBLISHDESC', 'Remove this page from the published site'))
					->addExtraClass('ss-ui-action-destructive')
			);
		}

		if($this->stagesDiffer('Stage', 'Live') && !$this->IsDeletedFromStage) {
			if($this->isPublished() && $this->canEdit())	{
				// "rollback"
				$moreOptions->push(
					FormAction::create('rollback', _t('SiteTree.BUTTONCANCELDRAFT', 'Cancel draft changes'), 'delete')
						->setDescription(_t('SiteTree.BUTTONCANCELDRAFTDESC', 'Delete your draft and revert to the currently published page'))
				);
			}
		}

		if($this->canEdit()) {
			if($this->IsDeletedFromStage) {
				// The usual major actions are not available, so we provide alternatives here.
				if($existsOnLive) {
					// "restore"
					$majorActions->push(FormAction::create('revert',_t('CMSMain.RESTORE','Restore')));
					if($this->canDelete() && $this->canDeleteFromLive()) {
						// "delete from live"
						$majorActions->push(
							FormAction::create('deletefromlive',_t('CMSMain.DELETEFP','Delete'))->addExtraClass('ss-ui-action-destructive')
						);
					}
				} else {
					// "restore"
					$majorActions->push(
						FormAction::create('restore',_t('CMSMain.RESTORE','Restore'))->setAttribute('data-icon', 'decline')
					);
				}
			} else {
				if($this->canDelete()) {
					// "delete"
					$moreOptions->push(
						FormAction::create('delete',_t('CMSMain.DELETE','Delete draft'))->addExtraClass('delete ss-ui-action-destructive')
					);
				}
			
				// "save", supports an alternate state that is still clickable, but notifies the user that the action is not needed.
				$majorActions->push(
					FormAction::create('save', _t('SiteTree.BUTTONSAVED', 'Saved'))
						->setAttribute('data-icon', 'accept')
						->setAttribute('data-icon-alternate', 'addpage')
						->setAttribute('data-text-alternate', _t('CMSMain.SAVEDRAFT','Save draft'))
				);
			}
		}

		if($this->canPublish() && !$this->IsDeletedFromStage) {
			// "publish", as with "save", it supports an alternate state to show when action is needed.
			$majorActions->push(
				$publish = FormAction::create('publish', _t('SiteTree.BUTTONPUBLISHED', 'Published'))
					->setAttribute('data-icon', 'accept')
					->setAttribute('data-icon-alternate', 'disk')
					->setAttribute('data-text-alternate', _t('SiteTree.BUTTONSAVEPUBLISH', 'Save & publish'))
			);

			// Set up the initial state of the button to reflect the state of the underlying SiteTree object.
			if($this->stagesDiffer('Stage', 'Live')) {
				$publish->addExtraClass('ss-ui-alternate');
		}
		}
		
		$actions = new FieldList(array($majorActions, $rootTabSet));
		
		// Hook for extensions to add/remove actions.
		$this->extend('updateCMSActions', $actions);
		
		return $actions;
	}
	
	/**
	 * Publish this page.
	 * 
	 * @uses SiteTreeExtension->onBeforePublish()
	 * @uses SiteTreeExtension->onAfterPublish()
	 */
	public function doPublish() {
		if (!$this->canPublish()) return false;
		
		$original = Versioned::get_one_by_stage("SiteTree", "Live", "\"SiteTree\".\"ID\" = $this->ID");
		if(!$original) $original = new SiteTree();

		// Handle activities undertaken by extensions
		$this->invokeWithExtensions('onBeforePublish', $original);
		//$this->PublishedByID = Member::currentUser()->ID;
		$this->write();
		$this->publish("Stage", "Live");

		DB::query("UPDATE \"SiteTree_Live\"
			SET \"Sort\" = (SELECT \"SiteTree\".\"Sort\" FROM \"SiteTree\" WHERE \"SiteTree_Live\".\"ID\" = \"SiteTree\".\"ID\")
			WHERE EXISTS (SELECT \"SiteTree\".\"Sort\" FROM \"SiteTree\" WHERE \"SiteTree_Live\".\"ID\" = \"SiteTree\".\"ID\") AND \"ParentID\" = " . sprintf('%d', $this->ParentID) );
			
		// Publish any virtual pages that might need publishing
		$linkedPages = $this->VirtualPages();
		if($linkedPages) foreach($linkedPages as $page) {
			$page->copyFrom($page->CopyContentFrom());
			$page->write();
			if($page->ExistsOnLive) $page->doPublish();
		}
		
		// Need to update pages linking to this one as no longer broken, on the live site
		$origMode = Versioned::get_reading_mode();
		Versioned::reading_stage('Live');
		foreach($this->DependentPages(false) as $page) {
			// $page->write() calls syncLinkTracking, which does all the hard work for us.
			$page->write();
		}
		Versioned::set_reading_mode($origMode);
		
		// Handle activities undertaken by extensions
		$this->invokeWithExtensions('onAfterPublish', $original);
		
		return true;
	}
	
	/**
	 * Unpublish this page - remove it from the live site
	 * 
	 * @uses SiteTreeExtension->onBeforeUnpublish()
	 * @uses SiteTreeExtension->onAfterUnpublish()
	 */
	public function doUnpublish() {
		if(!$this->canDeleteFromLive()) return false;
		if(!$this->ID) return false;
		
		$this->invokeWithExtensions('onBeforeUnpublish', $this);
		
		$origStage = Versioned::current_stage();
		Versioned::reading_stage('Live');

		// We should only unpublish virtualpages that exist on live
		$virtualPages = $this->VirtualPages();

		// This way our ID won't be unset
		$clone = clone $this;
		$clone->delete();

		// Rewrite backlinks
		$dependentPages = $this->DependentPages(false);
		if($dependentPages) foreach($dependentPages as $page) {
			// $page->write() calls syncLinkTracking, which does all the hard work for us.
			$page->write();
		}
		Versioned::reading_stage($origStage);

		// Unpublish any published virtual pages
		if ($virtualPages) foreach($virtualPages as $vp) $vp->doUnpublish();

		// If we're on the draft site, then we can update the status.
		// Otherwise, these lines will resurrect an inappropriate record
		if(DB::query("SELECT \"ID\" FROM \"SiteTree\" WHERE \"ID\" = $this->ID")->value()
			&& Versioned::current_stage() != 'Live') {
			$this->write();
		}

		$this->invokeWithExtensions('onAfterUnpublish', $this);

		return true;
	}
	
	/**
	 * Revert the draft changes: replace the draft content with the content on live
	 */
	public function doRevertToLive() {
		$this->invokeWithExtensions('onBeforeRevertToLive', $this);

		$this->publish("Live", "Stage", false);

		// Use a clone to get the updates made by $this->publish
		$clone = DataObject::get_by_id("SiteTree", $this->ID);
		$clone->writeWithoutVersion();

		// Need to update pages linking to this one as no longer broken
		foreach($this->DependentPages(false) as $page) {
			// $page->write() calls syncLinkTracking, which does all the hard work for us.
			$page->write();
		}
		
		$this->invokeWithExtensions('onAfterRevertToLive', $this);
	}
	
	/**
	 * Restore the content in the active copy of this SiteTree page to the stage site.
	 * @return The SiteTree object.
	 */
	public function doRestoreToStage() {
		// if no record can be found on draft stage (meaning it has been "deleted from draft" before),
		// create an empty record
		if(!DB::query("SELECT \"ID\" FROM \"SiteTree\" WHERE \"ID\" = $this->ID")->value()) {
			$conn = DB::getConn();
			if(method_exists($conn, 'allowPrimaryKeyEditing')) $conn->allowPrimaryKeyEditing('SiteTree', true);
			DB::query("INSERT INTO \"SiteTree\" (\"ID\") VALUES ($this->ID)");
			if(method_exists($conn, 'allowPrimaryKeyEditing')) $conn->allowPrimaryKeyEditing('SiteTree', false);
		}
		
		$oldStage = Versioned::current_stage();
		Versioned::reading_stage('Stage');
		$this->forceChange();
		$this->write();
		
		$result = DataObject::get_by_id($this->class, $this->ID);

		// Need to update pages linking to this one as no longer broken
		foreach($result->DependentPages(false) as $page) {
			// $page->write() calls syncLinkTracking, which does all the hard work for us.
			$page->write();
		}
		
		Versioned::reading_stage($oldStage);
		
		return $result;
	}

	/**
	 * Synonym of {@link doUnpublish}
	 */
	public function doDeleteFromLive() {
		return $this->doUnpublish();
	}

	/**
	 * Check if this page is new - that is, if it has yet to have been written
	 * to the database.
	 *
	 * @return boolean True if this page is new.
	 */
	public function isNew() {
		/**
		 * This check was a problem for a self-hosted site, and may indicate a
		 * bug in the interpreter on their server, or a bug here
		 * Changing the condition from empty($this->ID) to
		 * !$this->ID && !$this->record['ID'] fixed this.
		 */
		if(empty($this->ID)) return true;

		if(is_numeric($this->ID)) return false;

		return stripos($this->ID, 'new') === 0;
	}


	/**
	 * Check if this page has been published.
	 *
	 * @return boolean True if this page has been published.
	 */
	public function isPublished() {
		if($this->isNew())
			return false;

		return (DB::query("SELECT \"ID\" FROM \"SiteTree_Live\" WHERE \"ID\" = $this->ID")->value())
			? true
			: false;
	}

	/**
	 * Get the class dropdown used in the CMS to change the class of a page.
	 * This returns the list of options in the drop as a Map from class name
	 * to text in dropdown. Filters by {@link SiteTree->canCreate()},
	 * as well as {@link SiteTree::$needs_permission}.
	 *
	 * @return array
	 */
	protected function getClassDropdown() {
		$classes = self::page_type_classes();
		$currentClass = null;
		$result = array();
		
		$result = array();
		foreach($classes as $class) {
			$instance = singleton($class);

			// if the current page type is this the same as the class type always show the page type in the list see open ticket 5880 for why
			if ($this->ClassName != $instance->ClassName) {
				if((($instance instanceof HiddenClass) || !$instance->canCreate())) continue;
			}
			
			if($perms = $instance->stat('need_permission')) {
				if(!$this->can($perms)) continue;
			}

			$pageTypeName = $instance->i18n_singular_name();

			$currentClass = $class;
			$result[$class] = $pageTypeName;

			// if we're in translation mode, the link between the translated pagetype
			// title and the actual classname might not be obvious, so we add it in parantheses
			// Example: class "RedirectorPage" has the title "Weiterleitung" in German,
			// so it shows up as "Weiterleitung (RedirectorPage)"
			if(i18n::get_lang_from_locale(i18n::get_locale()) != 'en') {
				$result[$class] = $result[$class] .  " ({$class})";
			}
		}
		
		// sort alphabetically, and put current on top
		asort($result);
		if($currentClass) {
			$currentPageTypeName = $result[$currentClass];
			unset($result[$currentClass]);
			$result = array_reverse($result);
			$result[$currentClass] = $currentPageTypeName;
			$result = array_reverse($result);
		}
		
		return $result;
	}


	/**
	 * Returns an array of the class names of classes that are allowed
	 * to be children of this class.
	 *
	 * @return array
	 */
	public function allowedChildren() {
		$allowedChildren = array();
		$candidates = $this->stat('allowed_children');
		if($candidates && $candidates != "none" && $candidates != "SiteTree_root") {
			foreach($candidates as $candidate) {
				// If a classname is prefixed by "*", such as "*Page", then only that
				// class is allowed - no subclasses. Otherwise, the class and all its subclasses are allowed.
				if(substr($candidate,0,1) == '*') {
					$allowedChildren[] = substr($candidate,1);
				} else {
					$subclasses = ClassInfo::subclassesFor($candidate);
					foreach($subclasses as $subclass) {
						if($subclass != "SiteTree_root") $allowedChildren[] = $subclass;
					}
				}
			}
		}
		
		return $allowedChildren;
	}


	/**
	 * Returns the class name of the default class for children of this page.
	 *
	 * @return string
	 */
	public function defaultChild() {
		$default = $this->stat('default_child');
		$allowed = $this->allowedChildren();
		if($allowed) {
			if(!$default || !in_array($default, $allowed))
				$default = reset($allowed);
			return $default;
		}
	}


	/**
	 * Returns the class name of the default class for the parent of this
	 * page.
	 *
	 * @return string
	 */
	public function defaultParent() {
		return $this->stat('default_parent');
	}

	/**
	 * Get the title for use in menus for this page. If the MenuTitle
	 * field is set it returns that, else it returns the Title field.
	 *
	 * @return string
	 */
	public function getMenuTitle(){
		if($value = $this->getField("MenuTitle")) {
			return $value;
		} else {
			return $this->getField("Title");
		}
	}


	/**
	 * Set the menu title for this page.
	 *
	 * @param string $value
	 */
	public function setMenuTitle($value) {
		if($value == $this->getField("Title")) {
			$this->setField("MenuTitle", null);
		} else {
			$this->setField("MenuTitle", $value);
		}
	}
	
	/**
	 * A flag provides the user with additional data about the current page status,
	 * for example a "removed from draft" status. Each page can have more than one status flag.
	 * Returns a map of a unique key to a (localized) title for the flag.
	 * The unique key can be reused as a CSS class.
	 * Use the 'updateStatusFlags' extension point to customize the flags.
	 * 
	 * Example (simple): 
	 * "deletedonlive" => "Deleted"
	 * 
	 * Example (with optional title attribute): 
	 * "deletedonlive" => array('text' => "Deleted", 'title' => 'This page has been deleted')
	 *
	 * @param Boolean $cached
	 * @return array
	 */
	public function getStatusFlags($cached = true) {
		if(!$this->_cache_statusFlags || !$cached) {
			$flags = array();
			if($this->IsDeletedFromStage) {
				if($this->ExistsOnLive) {
					$flags['removedfromdraft'] = array(
						'text' => _t('SiteTree.REMOVEDFROMDRAFTSHORT', 'Removed from draft'),
						'title' => _t('SiteTree.REMOVEDFROMDRAFTHELP', 'Page is published, but has been deleted from draft'),
					);
				} else {
					$flags['deletedonlive'] = array(
						'text' => _t('SiteTree.DELETEDPAGESHORT', 'Deleted'),
						'title' => _t('SiteTree.DELETEDPAGEHELP', 'Page is no longer published'),
					);
				}
			} else if($this->IsAddedToStage) {
				$flags['addedtodraft'] = array(
					'text' => _t('SiteTree.ADDEDTODRAFTSHORT', 'Draft'),
					'title' => _t('SiteTree.ADDEDTODRAFTHELP', "Page has not been published yet")
				);
			} else if($this->IsModifiedOnStage) {
				$flags['modified'] = array(
					'text' => _t('SiteTree.MODIFIEDONDRAFTSHORT', 'Modified'),
					'title' => _t('SiteTree.MODIFIEDONDRAFTHELP', 'Page has unpublished changes'),
				);
			}

			$this->extend('updateStatusFlags', $flags);

			$this->_cache_statusFlags = $flags;
		}
		
		return $this->_cache_statusFlags;
	}

	/**
	 * getTreeTitle will return three <span> html DOM elements, an empty <span> with
	 * the class 'jstree-pageicon' in front, following by a <span> wrapping around its
	 * MenutTitle, then following by a <span> indicating its publication status. 
	 *
	 * @return string a html string ready to be directly used in a template
	 */
	public function getTreeTitle() {
		$flags = $this->getStatusFlags();
		$treeTitle = sprintf(
			"<span class=\"jstree-pageicon\"></span><span class=\"item\">%s</span>",
			Convert::raw2xml(str_replace(array("\n","\r"),"",$this->MenuTitle))
		);
		foreach($flags as $class => $data) {
			if(is_string($data)) $data = array('text' => $data);
			$treeTitle .= sprintf(
				"<span class=\"badge %s\"%s>%s</span>",
				'status-' . Convert::raw2xml($class),
				(isset($data['title'])) ? sprintf(' title="%s"', Convert::raw2xml($data['title'])) : '',
				Convert::raw2xml($data['text'])
			);
		}
		
		return $treeTitle;
	}

	/**
	 * Returns the page in the current page stack of the given level.
	 * Level(1) will return the main menu item that we're currently inside, etc.
	 */
	public function Level($level) {
		$parent = $this;
		$stack = array($parent);
		while($parent = $parent->Parent) {
			array_unshift($stack, $parent);
		}

		return isset($stack[$level-1]) ? $stack[$level-1] : null;
	}
	
	/**
	 * Return the CSS classes to apply to this node in the CMS tree
	 *
	 * @return string
	 */
	public function CMSTreeClasses() {
		$classes = sprintf('class-%s', $this->class);
		if($this->HasBrokenFile || $this->HasBrokenLink) {
			$classes .= " BrokenLink";
		}

		if(!$this->canAddChildren()) {
			$classes .= " nochildren";
		}

		if(!$this->canEdit() && !$this->canAddChildren()) {
			if (!$this->canView()) {
			$classes .= " disabled";
			} else {
				$classes .= " edit-disabled";
			}
		}

		if(!$this->ShowInMenus) {
			$classes .= " notinmenu";
		}
			
		//TODO: Add integration
		/*
		if($this->hasExtension('Translatable') && $controller->Locale != Translatable::default_locale() && !$this->isTranslation())
			$classes .= " untranslated ";
		*/
		$classes .= $this->markingClasses();

		return $classes;
	}
	
	/**
	 * Compares current draft with live version,
	 * and returns TRUE if no draft version of this page exists,
	 * but the page is still published (after triggering "Delete from draft site" in the CMS).
	 * 
	 * @return boolean
	 */
	public function getIsDeletedFromStage() {
		if(!$this->ID) return true;
		if($this->isNew()) return false;
		
		$stageVersion = Versioned::get_versionnumber_by_stage('SiteTree', 'Stage', $this->ID);

		// Return true for both completely deleted pages and for pages just deleted from stage.
		return !($stageVersion);
	}
	
	/**
	 * Return true if this page exists on the live site
	 */
	public function getExistsOnLive() {
		return (bool)Versioned::get_versionnumber_by_stage('SiteTree', 'Live', $this->ID);
	}

	/**
	 * Compares current draft with live version,
	 * and returns TRUE if these versions differ,
	 * meaning there have been unpublished changes to the draft site.
	 * 
	 * @return boolean
	 */
	public function getIsModifiedOnStage() {
		// new unsaved pages could be never be published
		if($this->isNew()) return false;
		
		$stageVersion = Versioned::get_versionnumber_by_stage('SiteTree', 'Stage', $this->ID);
		$liveVersion =	Versioned::get_versionnumber_by_stage('SiteTree', 'Live', $this->ID);

		return ($stageVersion && $stageVersion != $liveVersion);
	}
	
	/**
	 * Compares current draft with live version,
	 * and returns true if no live version exists,
	 * meaning the page was never published.
	 * 
	 * @return boolean
	 */
	public function getIsAddedToStage() {
		// new unsaved pages could be never be published
		if($this->isNew()) return false;
		
		$stageVersion = Versioned::get_versionnumber_by_stage('SiteTree', 'Stage', $this->ID);
		$liveVersion =	Versioned::get_versionnumber_by_stage('SiteTree', 'Live', $this->ID);

		return ($stageVersion && !$liveVersion);
	}
	
	/**
	 * Stops extendCMSFields() being called on getCMSFields().
	 * This is useful when you need access to fields added by subclasses
	 * of SiteTree in a extension. Call before calling parent::getCMSFields(),
	 * and reenable afterwards.
	 */
	static public function disableCMSFieldsExtensions() {
		self::$runCMSFieldsExtensions = false;
	}
	
	/**
	 * Reenables extendCMSFields() being called on getCMSFields() after
	 * it has been disabled by disableCMSFieldsExtensions().
	 */
	static public function enableCMSFieldsExtensions() {
		self::$runCMSFieldsExtensions = true;
	}

	public function providePermissions() {
		return array(
			'SITETREE_GRANT_ACCESS' => array(
				'name' => _t('SiteTree.PERMISSION_GRANTACCESS_DESCRIPTION', 'Manage access rights for content'),
				'help' => _t('SiteTree.PERMISSION_GRANTACCESS_HELP',  'Allow setting of page-specific access restrictions in the "Pages" section.'),
				'category' => _t('Permissions.PERMISSIONS_CATEGORY', 'Roles and access permissions'),
				'sort' => 100
			),
			'SITETREE_VIEW_ALL' => array(
				'name' => _t('SiteTree.VIEW_ALL_DESCRIPTION', 'View any page'),
				'category' => _t('Permissions.CONTENT_CATEGORY', 'Content permissions'),
				'sort' => -100,
				'help' => _t('SiteTree.VIEW_ALL_HELP', 'Ability to view any page on the site, regardless of the settings on the Access tab.  Requires the "Access to \'Pages\' section" permission')
			),
			'SITETREE_EDIT_ALL' => array(
				'name' => _t('SiteTree.EDIT_ALL_DESCRIPTION', 'Edit any page'),
				'category' => _t('Permissions.CONTENT_CATEGORY', 'Content permissions'),
				'sort' => -50,
				'help' => _t('SiteTree.EDIT_ALL_HELP', 'Ability to edit any page on the site, regardless of the settings on the Access tab.  Requires the "Access to \'Pages\' section" permission')
			),
			'SITETREE_REORGANISE' => array(
				'name' => _t('SiteTree.REORGANISE_DESCRIPTION', 'Change site structure'),
				'category' => _t('Permissions.CONTENT_CATEGORY', 'Content permissions'),
				'help' => _t('SiteTree.REORGANISE_HELP', 'Rearrange pages in the site tree through drag&drop.'),
				'sort' => 100
			),
			'VIEW_DRAFT_CONTENT' => array(
				'name' => _t('SiteTree.VIEW_DRAFT_CONTENT', 'View draft content'),
				'category' => _t('Permissions.CONTENT_CATEGORY', 'Content permissions'),
				'help' => _t('SiteTree.VIEW_DRAFT_CONTENT_HELP', 'Applies to viewing pages outside of the CMS in draft mode. Useful for external collaborators without CMS access.'),
				'sort' => 100
			)
		);
	}
	
	/**
	 * Return the translated Singular name 
	 * 
	 * @return String
	 */
	public function i18n_singular_name() {
		// Convert 'Page' to 'SiteTree' for correct localization lookups
		$class = ($this->class == 'Page') ? 'SiteTree' : $this->class;
		return _t($class.'.SINGULARNAME', $this->singular_name());
	}
	
	/**
	 * Overloaded to also provide entities for 'Page' class which is usually
	 * located in custom code, hence textcollector picks it up for the wrong folder.
	 */
	public function provideI18nEntities() {
		$entities = parent::provideI18nEntities();
		
		if(isset($entities['Page.SINGULARNAME'])) $entities['Page.SINGULARNAME'][3] = CMS_DIR;
		if(isset($entities['Page.PLURALNAME'])) $entities['Page.PLURALNAME'][3] = CMS_DIR;		

		$entities[$this->class . '.DESCRIPTION'] = array(
			$this->stat('description'),
			'Description of the page type (shown in the "add page" dialog)'
		);

		$entities['SiteTree.SINGULARNAME'][0] = 'Page';
		$entities['SiteTree.PLURALNAME'][0] = 'Pages';

		return $entities;
	}
	
	public function getParentType() {
		return $this->ParentID == 0 ? 'root' : 'subpage';
	}
	
	static public function reset() {
		self::$cache_permissions = array();
	}
	
	static public function on_db_reset() {
		self::$cache_permissions = array();
	}

}
