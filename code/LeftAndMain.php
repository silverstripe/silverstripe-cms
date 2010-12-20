<?php
/**
 * LeftAndMain is the parent class of all the two-pane views in the CMS.
 * If you are wanting to add more areas to the CMS, you can do it by subclassing LeftAndMain.
 * 
 * This is essentially an abstract class which should be subclassed.
 * See {@link CMSMain} for a good example.
 * 
 * @package cms
 * @subpackage core
 */
class LeftAndMain extends Controller {
	
	/**
	 * The 'base' url for CMS administration areas.
	 * Note that if this is changed, many javascript
	 * behaviours need to be updated with the correct url
	 *
	 * @var string $url_base
	 */
	static $url_base = "admin";
	
	static $url_segment;
	
	static $url_rule = '/$Action/$ID/$OtherID';
	
	static $menu_title;
	
	static $menu_priority = 0;
	
	static $url_priority = 50;

	static $tree_class = null;
	
	static $ForceReload;

	/**
	* The url used for the link in the Help tab in the backend
	* Value can be overwritten if required in _config.php
	*/
	static $help_link = 'http://userhelp.silverstripe.org';

	static $allowed_actions = array(
		'index',
		'ajaxupdateparent',
		'ajaxupdatesort',
		'callPageMethod',
		'deleteitems',
		'getitem',
		'getsubtree',
		'myprofile',
		'printable',
		'show',
		'Member_ProfileForm',
		'EditorToolbar',
		'EditForm',
	);
	
	/**
	 * Register additional requirements through the {@link Requirements class}.
	 * Used mainly to work around the missing "lazy loading" functionality
	 * for getting css/javascript required after an ajax-call (e.g. loading the editform).
	 *
	 * @var array $extra_requirements
	 */
	protected static $extra_requirements = array(
		'javascript' => array(),
		'css' => array(),
		'themedcss' => array(),
	);
	
	/**
	 * @param Member $member
	 * @return boolean
	 */
	function canView($member = null) {
		if(!$member && $member !== FALSE) {
			$member = Member::currentUser();
		}
		
		// cms menus only for logged-in members
		if(!$member) return false;
		
		// alternative decorated checks
		if($this->hasMethod('alternateAccessCheck')) {
			$alternateAllowed = $this->alternateAccessCheck();
			if($alternateAllowed === FALSE) return false;
		}
			
		// Default security check for LeftAndMain sub-class permissions
		if(!Permission::checkMember($member, "CMS_ACCESS_$this->class") && 
		   !Permission::checkMember($member, "CMS_ACCESS_LeftAndMain")) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * @uses LeftAndMainDecorator->init()
	 * @uses LeftAndMainDecorator->accessedCMS()
	 * @uses CMSMenu
	 */
	function init() {
		parent::init();
		
		// set language
		$member = Member::currentUser();
		if(!empty($member->Locale)) i18n::set_locale($member->Locale);
		if(!empty($member->DateFormat)) i18n::set_date_format($member->DateFormat);
		if(!empty($member->TimeFormat)) i18n::set_time_format($member->TimeFormat);
		
		// can't be done in cms/_config.php as locale is not set yet
		CMSMenu::add_link(
			'Help', 
			_t('LeftAndMain.HELP', 'Help', PR_HIGH, 'Menu title'), 
			self::$help_link
		);
		
		// set reading lang
		if(Object::has_extension('SiteTree', 'Translatable') && !Director::is_ajax()) {
			Translatable::choose_site_locale(array_keys(Translatable::get_existing_content_languages('SiteTree')));
		}

		// Allow customisation of the access check by a decorator
		// Also all the canView() check to execute Director::redirect()
		if(!$this->canView() && !$this->response->isFinished()) {
			// When access /admin/, we should try a redirect to another part of the admin rather than be locked out
			$menu = $this->MainMenu();
			foreach($menu as $candidate) {
				if(
					$candidate->Link && 
					$candidate->Link != $this->Link() 
					&& $candidate->MenuItem->controller 
					&& singleton($candidate->MenuItem->controller)->canView()
				) {
					return Director::redirect($candidate->Link);
				}
			}
			
			if(Member::currentUser()) {
				Session::set("BackURL", null);
			}
			
			// if no alternate menu items have matched, return a permission error
			$messageSet = array(
				'default' => _t('LeftAndMain.PERMDEFAULT',"Please choose an authentication method and enter your credentials to access the CMS."),
				'alreadyLoggedIn' => _t('LeftAndMain.PERMALREADY',"I'm sorry, but you can't access that part of the CMS.  If you want to log in as someone else, do so below"),
				'logInAgain' => _t('LeftAndMain.PERMAGAIN',"You have been logged out of the CMS.  If you would like to log in again, enter a username and password below."),
			);

			return Security::permissionFailure($this, $messageSet);
		}
		
		// Don't continue if there's already been a redirection request.
		if(Director::redirected_to()) return;

		// Audit logging hook
		if(empty($_REQUEST['executeForm']) && !Director::is_ajax()) $this->extend('accessedCMS');

		// Set the members html editor config
		HtmlEditorConfig::set_active(Member::currentUser()->getHtmlEditorConfigForCMS());
		
		
		// Set default values in the config if missing.  These things can't be defined in the config
		// file because insufficient information exists when that is being processed
		$htmlEditorConfig = HtmlEditorConfig::get_active();
		$htmlEditorConfig->setOption('language', i18n::get_tinymce_lang());
		if(!$htmlEditorConfig->getOption('content_css')) {
			$cssFiles = 'cms/css/editor.css';
			
			// Use theme from the site config
			if(($config = SiteConfig::current_site_config()) && $config->Theme) {
				$theme = $config->Theme;
			} elseif(SSViewer::current_theme()) {
				$theme = SSViewer::current_theme();
			} else {
				$theme = false;
			}
			
			if($theme) $cssFiles .= ', ' . THEMES_DIR . "/{$theme}/css/editor.css";
			else if(project()) $cssFiles .= ', ' . project() . '/css/editor.css';

			$htmlEditorConfig->setOption('content_css', $cssFiles);
		}
		

		Requirements::css(CMS_DIR . '/css/typography.css');
		Requirements::css(CMS_DIR . '/css/layout.css');
		Requirements::css(CMS_DIR . '/css/cms_left.css');
		Requirements::css(CMS_DIR . '/css/cms_right.css');
		Requirements::css(SAPPHIRE_DIR . '/css/Form.css');
		
		if(isset($_REQUEST['debug_firebug'])) {
			// Firebug is a useful console for debugging javascript
			// Its available as a Firefox extension or a javascript library
			// for easy inclusion in other browsers (just append ?debug_firebug=1 to the URL)
			Requirements::javascript(THIRDPARTY_DIR . '/firebug-lite/firebug.js');
		} else {
			// By default, we include fake-objects for all firebug calls
			// to avoid javascript errors when referencing console.log() etc in javascript code
			Requirements::javascript(THIRDPARTY_DIR . '/firebug-lite/firebugx.js');
		}
		
		Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/prototype/prototype.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/jquery_improvements.js');
		Requirements::javascript(THIRDPARTY_DIR . '/behaviour/behaviour.js');
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/core/jquery.ondemand.js');
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/prototype_improvements.js');
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/loader.js');
		Requirements::javascript(CMS_DIR . '/javascript/hover.js');
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/layout_helpers.js');
		Requirements::add_i18n_javascript(SAPPHIRE_DIR . '/javascript/lang');
		Requirements::add_i18n_javascript(CMS_DIR . '/javascript/lang');
		
		Requirements::javascript(THIRDPARTY_DIR . '/scriptaculous/effects.js');
		Requirements::javascript(THIRDPARTY_DIR . '/scriptaculous/dragdrop.js');
		Requirements::javascript(THIRDPARTY_DIR . '/scriptaculous/controls.js');

		Requirements::css(THIRDPARTY_DIR . '/greybox/greybox.css');
		Requirements::javascript(THIRDPARTY_DIR . '/greybox/AmiJS.js');
		Requirements::javascript(THIRDPARTY_DIR . '/greybox/greybox.js');
		
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/tree/tree.js');
		Requirements::css(THIRDPARTY_DIR . '/tree/tree.css');
		
		Requirements::javascript(CMS_DIR . '/javascript/LeftAndMain.js');
		Requirements::javascript(CMS_DIR . '/javascript/LeftAndMain_left.js');
		Requirements::javascript(CMS_DIR . '/javascript/LeftAndMain_right.js');
	
		Requirements::javascript(CMS_DIR . '/javascript/SideTabs.js');
		Requirements::javascript(CMS_DIR . '/javascript/SideReports.js');
		Requirements::javascript(CMS_DIR . '/javascript/LangSelector.js');
		Requirements::javascript(CMS_DIR . '/javascript/TranslationTab.js');
		
		// navigator
		Requirements::css(SAPPHIRE_DIR . '/css/SilverStripeNavigator.css');
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/SilverStripeNavigator.js');

		Requirements::themedCSS('typography');

		foreach (self::$extra_requirements['javascript'] as $file) {
			Requirements::javascript($file[0]);
		}
		
		foreach (self::$extra_requirements['css'] as $file) {
			Requirements::css($file[0], $file[1]);
		}
		
		foreach (self::$extra_requirements['themedcss'] as $file) {
			Requirements::themedCSS($file[0], $file[1]);
		}
		
		Requirements::customScript('Behaviour.addLoader(hideLoading);');

		// Javascript combined files
		Requirements::combine_files(
			'base.js',
			array(
				THIRDPARTY_DIR . '/prototype/prototype.js',
				THIRDPARTY_DIR . '/behaviour/behaviour.js',
				SAPPHIRE_DIR . '/javascript/prototype_improvements.js',
				THIRDPARTY_DIR .'/jquery/jquery.js',
				THIRDPARTY_DIR . '/jquery-effen/jquery.fn.js',
				SAPPHIRE_DIR . '/javascript/core/jquery.ondemand.js',
				SAPPHIRE_DIR . '/javascript/jquery_improvements.js',
				THIRDPARTY_DIR . '/firebug-lite/firebugx.js',
				SAPPHIRE_DIR . '/javascript/i18n.js',
			)
		);

		Requirements::combine_files(
			'leftandmain.js',
			array(
				SAPPHIRE_DIR . '/javascript/loader.js',
				CMS_DIR . '/javascript/hover.js',
				SAPPHIRE_DIR . '/javascript/layout_helpers.js',
				THIRDPARTY_DIR . '/scriptaculous/effects.js',
				THIRDPARTY_DIR . '/scriptaculous/dragdrop.js',
				THIRDPARTY_DIR . '/scriptaculous/controls.js',
				THIRDPARTY_DIR . '/greybox/AmiJS.js',
				THIRDPARTY_DIR . '/greybox/greybox.js',
				CMS_DIR . '/javascript/LeftAndMain.js',
				CMS_DIR . '/javascript/LeftAndMain_left.js',
				CMS_DIR . '/javascript/LeftAndMain_right.js',
				SAPPHIRE_DIR . '/javascript/tree/tree.js',
				THIRDPARTY_DIR . '/tabstrip/tabstrip.js',
				SAPPHIRE_DIR . '/javascript/TreeSelectorField.js',
		 		CMS_DIR . '/javascript/ThumbnailStripField.js',
			)
		);

		Requirements::combine_files(
			'cmsmain.js',
			array(
				CMS_DIR . '/javascript/CMSMain.js',
				CMS_DIR . '/javascript/CMSMain_left.js',
				CMS_DIR . '/javascript/CMSMain_right.js',
				CMS_DIR . '/javascript/SideTabs.js',
				CMS_DIR . '/javascript/SideReports.js',
				CMS_DIR . '/javascript/LangSelector.js',
				CMS_DIR . '/javascript/TranslationTab.js',
				THIRDPARTY_DIR . '/calendar/calendar.js',
				THIRDPARTY_DIR . '/calendar/lang/calendar-en.js',
				THIRDPARTY_DIR . '/calendar/calendar-setup.js',
				CMS_DIR . "/javascript/SitetreeAccess.js",
			)
		);

		$dummy = null;
		$this->extend('init', $dummy);

		// The user's theme shouldn't affect the CMS, if, for example, they have replaced
		// TableListField.ss or Form.ss.
		SSViewer::set_theme(null);
	}

	
	/**
	 * If this is set to true, the "switchView" context in the
	 * template is shown, with links to the staging and publish site.
	 *
	 * @return boolean
	 */
	function ShowSwitchView() {
		return false;
	}

	//------------------------------------------------------------------------------------------//
	// Main controllers

	/**
	 * You should implement a Link() function in your subclass of LeftAndMain,
	 * to point to the URL of that particular controller.
	 * 
	 * @return string
	 */
	public function Link($action = null) {
		// Handle missing url_segments
		if(!$this->stat('url_segment', true))
			self::$url_segment = $this->class;
		return Controller::join_links(
			$this->stat('url_base', true),
			$this->stat('url_segment', true),
			'/', // trailing slash needed if $action is null!
			"$action"
		);
	}
	
	
	/**
 	* Returns the menu title for the given LeftAndMain subclass.
 	* Implemented static so that we can get this value without instantiating an object.
 	* Menu title is *not* internationalised.
 	*/
	static function menu_title_for_class($class) {
		$title = eval("return $class::\$menu_title;");
		if(!$title) $title = preg_replace('/Admin$/', '', $class);
		return $title;
	}

	public function show() {
		$params = $this->getURLParams();
		if($params['ID']) $this->setCurrentPageID($params['ID']);
		if(isset($params['OtherID'])) {
			Session::set('currentMember', $params['OtherID']);
		}

		if(Director::is_ajax()) {
			SSViewer::setOption('rewriteHashlinks', false);
			return $this->EditForm()->formHtmlContent();

		} else {
			return array();
		}
	}


	public function getitem() {
		$this->setCurrentPageID($_REQUEST['ID']);
		SSViewer::setOption('rewriteHashlinks', false);

		if(isset($_REQUEST['ID']) && is_numeric($_REQUEST['ID'])) {
			$record = DataObject::get_by_id($this->stat('tree_class'), $_REQUEST['ID']);
			if($record && !$record->canView()) return Security::permissionFailure($this);
		}

		$form = $this->EditForm();
		if ($form) {
			$content =  $form->formHtmlContent();
			if($this->ShowSwitchView()) {
				$content .= '<div id="AjaxSwitchView">' . $this->SwitchView() . '</div>';
			}
			
			return $content;
		}
		else return "";
	}
	public function getLastFormIn($html) {
		$parts = split('</?form[^>]*>', $html);
		return $parts[sizeof($parts)-2];
	}

	//------------------------------------------------------------------------------------------//
	// Main UI components

	/**
	 * Returns the main menu of the CMS.  This is also used by init() to work out which sections the user
	 * has access to.
	 * 
	 * @return DataObjectSet
	 */
	public function MainMenu() {
		// Don't accidentally return a menu if you're not logged in - it's used to determine access.
		if(!Member::currentUser()) return new DataObjectSet();

		// Encode into DO set
		$menu = new DataObjectSet();
		$menuItems = CMSMenu::get_viewable_menu_items();
		if($menuItems) foreach($menuItems as $code => $menuItem) {
			// alternate permission checks (in addition to LeftAndMain->canView())
			if(
				isset($menuItem->controller) 
				&& $this->hasMethod('alternateMenuDisplayCheck')
				&& !$this->alternateMenuDisplayCheck($menuItem->controller)
			) {
				continue;
			}

			$linkingmode = "";
			
			if(strpos($this->Link(), $menuItem->url) !== false) {
				if($this->Link() == $menuItem->url) {
					$linkingmode = "current";
				
				// default menu is the one with a blank {@link url_segment}
				} else if(singleton($menuItem->controller)->stat('url_segment') == '') {
					if($this->Link() == $this->stat('url_base').'/') $linkingmode = "current";

				} else {
					$linkingmode = "current";
				}
			}
		
			// already set in CMSMenu::populate_menu(), but from a static pre-controller
			// context, so doesn't respect the current user locale in _t() calls - as a workaround,
			// we simply call LeftAndMain::menu_title_for_class() again if we're dealing with a controller
			if($menuItem->controller) {
				$defaultTitle = LeftAndMain::menu_title_for_class($menuItem->controller);
				$title = _t("{$menuItem->controller}.MENUTITLE", $defaultTitle);
			} else {
				$title = $menuItem->title;
			}
			
			$menu->push(new ArrayData(array(
				"MenuItem" => $menuItem,
				"Title" => Convert::raw2xml($title),
				"Code" => $code,
				"Link" => $menuItem->url,
				"LinkingMode" => $linkingmode
			)));
		}
		
		// if no current item is found, assume that first item is shown
		//if(!isset($foundCurrent)) 
		return $menu;
	}


	public function CMSTopMenu() {
		return $this->renderWith(array('CMSTopMenu_alternative','CMSTopMenu'));
	}

  /**
   * Return a list of appropriate templates for this class, with the given suffix
   */
  protected function getTemplatesWithSuffix($suffix) {
    $classes = array_reverse(ClassInfo::ancestry($this->class));
    foreach($classes as $class) {
      $templates[] = $class . $suffix;
      if($class == 'LeftAndMain') break;
    }
    return $templates;
  }

	public function Left() {
		return $this->renderWith($this->getTemplatesWithSuffix('_left'));
	}

	public function Right() {
		return $this->renderWith($this->getTemplatesWithSuffix('_right'));
	}

	public function getRecord($id, $className = null) {
		if($id && is_numeric($id)) {
			if(!$className) $className = $this->stat('tree_class');
			return DataObject::get_by_id($className, $id);
		}
	}

	/**
	 * Get a site tree displaying the nodes under the given objects
	 * @param $className The class of the root object
	 * @param $rootID The ID of the root object.  If this is null then a complete tree will be
	 *                shown
	 * @param $childrenMethod The method to call to get the children of the tree.  For example,
	 *                        Children, AllChildrenIncludingDeleted, or AllHistoricalChildren
	 */
	function getSiteTreeFor($className, $rootID = null, $childrenMethod = null, $numChildrenMethod = null, $filterFunction = null, $minNodeCount = 30) {
		// Default childrenMethod and numChildrenMethod
		if (!$childrenMethod) $childrenMethod = 'AllChildrenIncludingDeleted';
		if (!$numChildrenMethod) $numChildrenMethod = 'numChildren';
		
		// Get the tree root
		$obj = $rootID ? $this->getRecord($rootID) : singleton($className);
		
		// Mark the nodes of the tree to return
		if ($filterFunction) $obj->setMarkingFilterFunction($filterFunction);

		$obj->markPartialTree($minNodeCount, $this, $childrenMethod, $numChildrenMethod);
		
		// Ensure current page is exposed
		if($p = $this->currentPage()) $obj->markToExpose($p);
		
		// NOTE: SiteTree/CMSMain coupling :-(
		SiteTree::prepopuplate_permission_cache('CanEditType', $obj->markedNodeIDs(), 'SiteTree::can_edit_multiple');

		// getChildrenAsUL is a flexible and complex way of traversing the tree
		$titleEval = '
					"<li id=\"record-$child->ID\" class=\"" . $child->CMSTreeClasses($extraArg) . "\">" .
					"<a href=\"" . Controller::join_links(substr($extraArg->Link(),0,-1), "show", $child->ID) . "\" class=\"" . $child->CMSTreeClasses($extraArg) . "\" title=\"' . _t('LeftAndMain.PAGETYPE','Page type: ') . '".$child->class."\" >" . 
					($child->TreeTitle()) . 
					"</a>"
';
		$siteTree = $obj->getChildrenAsUL(
			"", 
			$titleEval,
			$this, 
			true, 
			$childrenMethod,
			$numChildrenMethod,
			$minNodeCount
		);

		// Wrap the root if needs be.

		if(!$rootID) {
			$rootLink = $this->Link('show') . '/root';
			
			// This lets us override the tree title with an extension
			if($this->hasMethod('getCMSTreeTitle') && $customTreeTitle = $this->getCMSTreeTitle()) {
				$treeTitle = $customTreeTitle;
			} else {
				$siteConfig = SiteConfig::current_site_config();
				$treeTitle =  $siteConfig->Title;
			}
			
			$siteTree = "<ul id=\"sitetree\" class=\"tree unformatted\"><li id=\"record-0\" class=\"Root nodelete\"><a href=\"$rootLink\"><strong>$treeTitle</strong></a>"
				. $siteTree . "</li></ul>";
		}

		return $siteTree;
	}

	/**
	 * Get a subtree underneath the request param 'ID'.
	 * If ID = 0, then get the whole tree.
	 */
	public function getsubtree($request) {
		// Get the tree
		$minNodeCount = (is_numeric($request->getVar('minNodeCount'))) ? $request->getVar('minNodeCount') : NULL;
		$tree = $this->getSiteTreeFor(
			$this->stat('tree_class'), 
			$request->getVar('ID'), 
			null, 
			null, 
			null,
			$minNodeCount
		);

		// Trim off the outer tag
		$tree = ereg_replace('^[ \t\r\n]*<ul[^>]*>','', $tree);
		$tree = ereg_replace('</ul[^>]*>[ \t\r\n]*$','', $tree);
		
		return $tree;
	}

	/**
	 * Allows you to returns a new data object to the tree (subclass of sitetree)
	 * and updates the tree via javascript.
	 */
	public function returnItemToUser($p) {
		if(Director::is_ajax()) {
			// Prepare the object for insertion.
			$parentID = (int) $p->ParentID;
			$id = $p->ID ? $p->ID : "new-$p->class-$p->ParentID";
			$treeTitle = Convert::raw2js($p->TreeTitle());
			$hasChildren = (is_numeric($id) && $p->AllChildren() && $p->AllChildren()->Count()) ? ' unexpanded' : '';

			// Ensure there is definitly a node avaliable. if not, append to the home tree.
			$response = <<<JS
				var tree = $('sitetree');
				var newNode = tree.createTreeNode("$id", "$treeTitle", "{$p->class}{$hasChildren}");
				node = tree.getTreeNodeByIdx($parentID);
				if(!node) {
					node = tree.getTreeNodeByIdx(0);
				}
				node.open();
				node.appendTreeNode(newNode);
				newNode.selectTreeNode();
JS;
			FormResponse::add($response);

			return FormResponse::respond();
		} else {
			Director::redirect('admin/' . self::$url_segment . '/show/' . $p->ID);
		}
	}

	/**
	 * Save and Publish page handler
	 */
	public function save($urlParams, $form) {
		$className = $this->stat('tree_class');

		$SQL_id = Convert::raw2sql($_REQUEST['ID']);
		if(substr($SQL_id,0,3) != 'new') {
			$record = DataObject::get_one($className, "\"$className\".\"ID\" = {$SQL_id}");
			if($record && !$record->canEdit()) return Security::permissionFailure($this);
		} else {
			if(!singleton($this->stat('tree_class'))->canCreate()) return Security::permissionFailure($this);
			$record = $this->getNewItem($SQL_id, false);
		}

		// We don't want to save a new version if there are no changes
		$dataFields_new = $form->Fields()->dataFields();
		$dataFields_old = $record->getAllFields();
		$changed = false;
		$hasNonRecordFields = false;
		foreach($dataFields_new as $datafield) {
			// if the form has fields not belonging to the record
			if(!isset($dataFields_old[$datafield->Name()])) {
				$hasNonRecordFields = true;
			}
			// if field-values have changed
			if(!isset($dataFields_old[$datafield->Name()]) || $dataFields_old[$datafield->Name()] != $datafield->dataValue()) {
				$changed = true;
			}
		}

		if(!$changed && !$hasNonRecordFields) {
			// Tell the user we have saved even though we haven't, as not to confuse them
			if(is_a($record, "Page")) {
				$record->Status = "Saved (update)";
			}
			FormResponse::status_message(_t('LeftAndMain.SAVEDUP',"Saved"), "good");
			FormResponse::update_status($record->Status);
			return FormResponse::respond();
		}

		$form->dataFieldByName('ID')->Value = 0;

		if(isset($urlParams['Sort']) && is_numeric($urlParams['Sort'])) {
			$record->Sort = $urlParams['Sort'];
		}

		// HACK: This should be turned into something more general
		$originalClass = $record->ClassName;
		$originalStatus = $record->Status;
		$originalParentID = $record->ParentID;

		$originalBrokenLinkValues = $record->HasBrokenLink.$record->HasBrokenFile;

		$record->HasBrokenLink = 0;
		$record->HasBrokenFile = 0;

		$record->writeWithoutVersion();

		// HACK: This should be turned into something more general
		$originalURLSegment = $record->URLSegment;

		$form->saveInto($record, true);

		if(is_a($record, "Page")) {
			$record->Status = ($record->Status == "New page" || $record->Status == "Saved (new)") ? "Saved (new)" : "Saved (update)";
		}

		if(Director::is_ajax()) {
			if($SQL_id != $record->ID) {
				FormResponse::add("$('sitetree').setNodeIdx(\"{$SQL_id}\", \"$record->ID\");");
				FormResponse::add("$('Form_EditForm').elements.ID.value = \"$record->ID\";");
			}

			if($added = DataObjectLog::getAdded('SiteTree')) {
				foreach($added as $page) {
					if($page->ID != $record->ID) FormResponse::add($this->addTreeNodeJS($page));
				}
			}
			if($deleted = DataObjectLog::getDeleted('SiteTree')) {
				foreach($deleted as $page) {
					if($page->ID != $record->ID) FormResponse::add($this->deleteTreeNodeJS($page));
				}
			}
			if($changed = DataObjectLog::getChanged('SiteTree')) {
				foreach($changed as $page) {
					if($page->ID != $record->ID) {
						$title = Convert::raw2js($page->TreeTitle());
						FormResponse::add("$('sitetree').setNodeTitle($page->ID, \"$title\");");
					}
				}
			}

			$message = _t('LeftAndMain.SAVEDUP');

			// Update the class instance if necessary
			if($originalClass != $record->ClassName) {
				$newClassName = $record->ClassName;
				// The records originally saved attribute was overwritten by $form->saveInto($record) before.
				// This is necessary for newClassInstance() to work as expected, and trigger change detection
				// on the ClassName attribute
				$record->setClassName($originalClass);
				// Replace $record with a new instance
				$record = $record->newClassInstance($newClassName);
				
				// update the tree icon
				FormResponse::add("if(\$('sitetree').setNodeIcon) \$('sitetree').setNodeIcon($record->ID, '$originalClass', '$record->ClassName');");
			}

			// HACK: This should be turned into somethign more general
			// Removed virtualpage test as we need to draft/published links when url is changed
			if( (/*$record->class == 'VirtualPage' &&*/ $originalURLSegment != $record->URLSegment) ||
				($originalClass != $record->ClassName) || self::$ForceReload == true) {
				// avoid double loading by adding a uniqueness ID
				FormResponse::add($str = "$('Form_EditForm').getPageFromServer($record->ID);", $str);
			}

			// After reloading action
			if($originalStatus != $record->Status) {
				$message .= sprintf(_t('LeftAndMain.STATUSTO',"  Status changed to '%s'"),$record->Status);
			}
			
			if($originalParentID != $record->ParentID) {
				FormResponse::add("if(\$('sitetree').setNodeParentID) \$('sitetree').setNodeParentID($record->ID, $record->ParentID);");
			}

			

			$record->write();

			if( ($record->class != 'VirtualPage') && $originalURLSegment != $record->URLSegment) {
				$message .= sprintf(_t('LeftAndMain.CHANGEDURL',"  Changed URL to '%s'"),$record->URLSegment);
				FormResponse::add("\$('Form_EditForm').elements.URLSegment.value = \"$record->URLSegment\";");
				FormResponse::add("\$('Form_EditForm_StageURLSegment').value = \"" . $record->AbsoluteLink() . "\";");
			}
			
			if($virtualPages = DataObject::get("VirtualPage", "\"CopyContentFromID\" = $record->ID")) {
				foreach($virtualPages as $page) {
					if($page->ID != $record->ID) {
						$title = Convert::raw2js($page->TreeTitle());
						FormResponse::add("$('sitetree').setNodeTitle($page->ID, \"$title\");");
					}
				}
			}
			
			// If there has been a change in the broken link values, reload the page
			if ($originalBrokenLinkValues != $record->HasBrokenLink.$record->HasBrokenFile) {
				// avoid double loading by adding a uniqueness ID
				FormResponse::add($str = "$('Form_EditForm').getPageFromServer($record->ID);", $str);
			}

			// If the 'Save & Publish' button was clicked, also publish the page
			if (isset($urlParams['publish']) && $urlParams['publish'] == 1) {
				$this->extend('onAfterSave', $record);
			
				$record->doPublish();
				
				// Update classname with original and get new instance (see above for explanation)
				$record->setClassName($originalClass);
				$publishedRecord = $record->newClassInstance($record->ClassName);

				return $this->tellBrowserAboutPublicationChange(
					$publishedRecord, 
					sprintf(
						_t(
							'LeftAndMain.STATUSPUBLISHEDSUCCESS', 
							"Published '%s' successfully",
							PR_MEDIUM,
							'Status message after publishing a page, showing the page title'
						),
						$record->Title
					)
				);
			} else {
				// BUGFIX: Changed icon only shows after Save button is clicked twice http://support.silverstripe.com/gsoc/ticket/76
				$title = Convert::raw2js($record->TreeTitle());
				FormResponse::add("$('sitetree').setNodeTitle(\"$record->ID\", \"$title\");");
				FormResponse::add($this->getActionUpdateJS($record));
				FormResponse::status_message($message, "good");
				FormResponse::update_status($record->Status);

				$this->extend('onAfterSave', $record);

				return FormResponse::respond();
			}
		}
	}

	/**
	 * Returns a javascript snippet that will update the actions of the main form
	 * 
	 * @return string
	 */
	public function getActionUpdateJS($record) {
		// Get the new action buttons

		$tempForm = $this->getEditForm($record->ID);
		$actionList = '';
		foreach($tempForm->Actions() as $action) {
			$actionList .= $action->Field() . ' ';
		}

		return "$('Form_EditForm').loadActionsFromString('" . Convert::raw2js($actionList) . "');";
	}

	/**
	 * Returns a javascript snippet to generate a tree node for the given page, if visible
	 *
	 * @return string
	 */
	public function addTreeNodeJS($page, $select = false) {
		$parentID = (int)$page->ParentID;
		$title = Convert::raw2js($page->TreeTitle());
		$response = <<<JS
var newNode = $('sitetree').createTreeNode($page->ID, "$title", "$page->class");
var parentNode = $('sitetree').getTreeNodeByIdx($parentID); 
if(parentNode) parentNode.appendTreeNode(newNode);
JS;
		$response .= ($select ? "newNode.selectTreeNode();\n" : "") ;
		return $response;
	}
	/**
	 * Returns a javascript snippet to remove a tree node for the given page, if it exists.
	 *
	 * @return string
	 */
	public function deleteTreeNodeJS($page) {
		$id = $page->ID ? $page->ID : $page->OldID;
		$response = <<<JS
var node = $('sitetree').getTreeNodeByIdx($id);
if(node && node.parentTreeNode) node.parentTreeNode.removeTreeNode(node);
$('Form_EditForm').closeIfSetTo($id);
JS;

		// If we have that page selected currently, then clear that info from the session
		if(Session::get("{$this->class}.currentPage") == $id) {
			$this->setCurrentPageID(null);
		}
		
		return $response;
	}

	/**
	 * Sets a static variable on this class which means the panel will be reloaded.
	 */
	static function ForceReload(){
		self::$ForceReload = true;
	}

	/**
	 * Ajax handler for updating the parent of a tree node
	 */
	public function ajaxupdateparent($request) {
		// Protect against CSRF on destructive action
		if(!SecurityToken::inst()->checkRequest($request)) return $this->httpError(400);
		
		$id = $_REQUEST['ID'];
		$parentID = $_REQUEST['ParentID'];
		if($parentID == 'root'){
			$parentID = 0;
		}
		$_REQUEST['ajax'] = 1;
		$cleanupJS = '';
		
		if (!Permission::check('SITETREE_REORGANISE') && !Permission::check('ADMIN')) {
			FormResponse::status_message(_t('LeftAndMain.CANT_REORGANISE',"You do not have permission to rearange the site tree. Your change was not saved."),"bad");
			return FormResponse::respond();
		}

		if(is_numeric($id) && is_numeric($parentID) && $id != $parentID) {
			$node = DataObject::get_by_id($this->stat('tree_class'), $id);
			if($node){
				if($node && !$node->canEdit()) return Security::permissionFailure($this);
				
				$node->ParentID = $parentID;
				$node->Status = "Saved (update)";
				$node->write();

				if(is_numeric($_REQUEST['CurrentlyOpenPageID'])) {
					$currentPage = DataObject::get_by_id($this->stat('tree_class'), $_REQUEST['CurrentlyOpenPageID']);
					if($currentPage) {
						$cleanupJS = $currentPage->cmsCleanup_parentChanged();
					}
				}

				FormResponse::status_message(_t('LeftAndMain.SAVED','saved'), 'good');
				if($cleanupJS) FormResponse::add($cleanupJS);

			}else{
				FormResponse::status_message(_t('LeftAndMain.PLEASESAVE',"Please Save Page: This page could not be upated because it hasn't been saved yet."),"good");
			}


			return FormResponse::respond();
		} else {
			user_error("Error in ajaxupdateparent request; id=$id, parentID=$parentID", E_USER_ERROR);
		}
	}

	/**
	 * Ajax handler for updating the order of a number of tree nodes
	 * $_GET[ID]: An array of node ids in the correct order
	 * $_GET[MovedNodeID]: The node that actually got moved
	 */
	public function ajaxupdatesort($request) {
		// Protect against CSRF on destructive action
		if(!SecurityToken::inst()->checkRequest($request)) return $this->httpError(400);
		
		$className = $this->stat('tree_class');
		$counter = 0;
		$js = '';
		$_REQUEST['ajax'] = 1;
		
		if (!Permission::check('SITETREE_REORGANISE') && !Permission::check('ADMIN')) {
			FormResponse::status_message(_t('LeftAndMain.CANT_REORGANISE',"You do not have permission to rearange the site tree. Your change was not saved."),"bad");
			return FormResponse::respond();
		}

		if(is_array($_REQUEST['ID'])) {
			if($_REQUEST['MovedNodeID']==0){ //Sorting root
				$movedNode = DataObject::get($className, "\"ParentID\"=0");				
			}else{
				$movedNode = DataObject::get_by_id($className, $_REQUEST['MovedNodeID']);
			}
			foreach($_REQUEST['ID'] as $id) {
				if($id == $movedNode->ID) {
					$movedNode->Sort = ++$counter;
					$movedNode->Status = "Saved (update)";
					$movedNode->write();

					$title = Convert::raw2js($movedNode->TreeTitle());
					$js .="$('sitetree').setNodeTitle($movedNode->ID, \"$title\");\n";

				// Nodes that weren't "actually moved" shouldn't be registered as having been edited; do a direct SQL update instead
				} else if(is_numeric($id)) {
					++$counter;
					DB::query("UPDATE \"$className\" SET \"Sort\" = $counter WHERE \"ID\" = '$id'");
				}
			}
			FormResponse::status_message(_t('LeftAndMain.SAVED'), 'good');
		} else {
			FormResponse::error(_t('LeftAndMain.REQUESTERROR',"Error in request"));
		}

		return FormResponse::respond();
	}
	
	public function CanOrganiseSitetree() {
		return !Permission::check('SITETREE_REORGANISE') && !Permission::check('ADMIN') ? false : true;
	}

	/**
	 * Delete a number of items
	 */
	public function deleteitems($request) {
		// Protect against CSRF on destructive action
		if(!SecurityToken::inst()->checkRequest($request)) return $this->httpError(400);
		
		$ids = split(' *, *', $_REQUEST['csvIDs']);

		$script = "st = \$('sitetree'); \n";
		foreach($ids as $id) {
			if(is_numeric($id)) {
				$record = DataObject::get_by_id($this->stat('tree_class'), $id);
				if($record && !$record->canDelete()) return Security::permissionFailure($this);
				
				DataObject::delete_by_id($this->stat('tree_class'), $id);
				$script .= "node = st.getTreeNodeByIdx($id); if(node) node.parentTreeNode.removeTreeNode(node); $('Form_EditForm').closeIfSetTo($id); \n";
				
			}
		}

		FormResponse::add($script);

		return FormResponse::respond();
	}
		
	/**
	 * Returns a placeholder form, used by {@link getEditForm()} if no record is selected.
	 * Our javascript logic always requires a form to be present in the CMS interface.
	 * 
	 * @return Form
	 */
	function EmptyForm() {
		$form = new Form(
			$this, 
			"EditForm", 
			new FieldSet(
				new HeaderField(
					'WelcomeHeader',
					$this->getApplicationName()
				),
				new LiteralField(
					'WelcomeText',
					sprintf('<p id="WelcomeMessage">%s %s. %s</p>',
						_t('LeftAndMain_right.ss.WELCOMETO','Welcome to'),
						$this->getApplicationName(),
						_t('CHOOSEPAGE','Please choose an item from the left.')
					)
				)
			), 
			new FieldSet()
		);
		$form->unsetValidator();
		
		return $form;
	}

	public function EditForm() {
		// Include JavaScript to ensure HtmlEditorField works.
		HtmlEditorField::include_js();
		
		if ($this->currentPageID() != 0) {
			$record = $this->currentPage();
			if(!$record) return false;
			if($record && !$record->canView()) return Security::permissionFailure($this);
		}
		if ($this->hasMethod('getEditForm')) {
			return $this->getEditForm($this->currentPageID());
		}
		
		return false;
	}
	
	public function myprofile() {
		$form = $this->Member_ProfileForm();
		return $this->customise(array(
			'Form' => $form
		))->renderWith('BlankPage');
	}
	
	public function Member_ProfileForm() {
		return new Member_ProfileForm($this, 'Member_ProfileForm', Member::currentUser());
	}

	public function printable() {
		$id = $_REQUEST['ID'] ? $_REQUEST['ID'] : $this->currentPageID();

		if($id) $form = $this->getEditForm($id);
		$form->transform(new PrintableTransformation());
		$form->actions = null;

		Requirements::clear();
		Requirements::css(CMS_DIR . '/css/LeftAndMain_printable.css');
		return array(
			"PrintForm" => $form
		);
	}

	public function currentPageID() {
		if(isset($_REQUEST['ID']) && is_numeric($_REQUEST['ID']))	{
			return $_REQUEST['ID'];
		} elseif (isset($this->urlParams['ID']) && is_numeric($this->urlParams['ID'])) {
			return $this->urlParams['ID'];
		} elseif(Session::get("{$this->class}.currentPage")) {
			return Session::get("{$this->class}.currentPage");
		} else {
			return null;
		}
	}

	public function setCurrentPageID($id) {
		Session::set("{$this->class}.currentPage", $id);
	}

	public function currentPage() {
		return $this->getRecord($this->currentPageID());
	}

	public function isCurrentPage(DataObject $page) {
		return $page->ID == Session::get("{$this->class}.currentPage");
	}
	
	/**
	 * Get the staus of a certain page and version.
	 *
	 * This function is used for concurrent editing, and providing alerts
	 * when multiple users are editing a single page. It echoes a json
	 * encoded string to the UA.
	 */

	/**
	 * Return the CMS's HTML-editor toolbar
	 */
	public function EditorToolbar() {
		return Object::create('HtmlEditorField_Toolbar', $this, "EditorToolbar");
	}

	/**
	 * Return the version number of this application.
	 * Uses the subversion path information in <mymodule>/silverstripe_version
	 * (automacially replaced $URL$ placeholder).
	 * 
	 * @return string
	 */
	public function CMSVersion() {
		$sapphireVersionFile = file_get_contents(BASE_PATH . '/sapphire/silverstripe_version');
		$cmsVersionFile = file_get_contents(BASE_PATH . '/cms/silverstripe_version');
		
		$sapphireVersion = $this->versionFromVersionFile($sapphireVersionFile);
		$cmsVersion = $this->versionFromVersionFile($cmsVersionFile);

		if($sapphireVersion == $cmsVersion) {
			return $sapphireVersion;
		}	else {
			return "cms: $cmsVersion, sapphire: $sapphireVersion";
		}
	}
	
	/**
	 * Return the version from the content of a silverstripe_version file
	 */
	public function versionFromVersionFile($fileContent) {
		if(preg_match('/\/trunk\/silverstripe_version/', $fileContent)) {
			return "trunk";
		} else {
			preg_match("/\/(?:branches|tags\/rc|tags\/beta|tags\/alpha|tags)\/([A-Za-z0-9._-]+)\/silverstripe_version/", $fileContent, $matches);
			return ($matches) ? $matches[1] : null;
		}
	}
	
	/**
	 * @return array
	 */
	function SwitchView() { 
		if($page = $this->currentPage()) { 
			$nav = SilverStripeNavigator::get_for_record($page); 
			return $nav['items']; 
		} 
	}

	/**
	 * The application name. Customisable by calling
	 * LeftAndMain::setApplicationName() - the first parameter.
	 * 
	 * @var String
	 */
	static $application_name = 'SilverStripe CMS';
	
	/**
	 * The application logo text. Customisable by calling
	 * LeftAndMain::setApplicationName() - the second parameter.
	 *
	 * @var String
	 */
	static $application_logo_text = 'SilverStripe';

	/**
	 * Set the application name, and the logo text.
	 *
	 * @param String $name The application name
	 * @param String $logoText The logo text
	 */
	static $application_link = "http://www.silverstripe.org/";
	static function setApplicationName($name, $logoText = null, $link = null) {
		self::$application_name = $name;
		self::$application_logo_text = $logoText ? $logoText : $name;
		if($link) self::$application_link = $link;
	}

	/**
	 * Get the application name.
	 * @return String
	 */
	function getApplicationName() {
		return self::$application_name;
	}
	
	/**
	 * Get the application logo text.
	 * @return String
	 */
	function getApplicationLogoText() {
		return self::$application_logo_text;
	}
	function ApplicationLink() {
		return self::$application_link;
	}

	/**
	 * Return the title of the current section, as shown on the main menu
	 */
	function SectionTitle() {
		// Get menu - use obj() to cache it in the same place as the template engine
		$menu = $this->obj('MainMenu');
		
		foreach($menu as $menuItem) {
			if($menuItem->LinkingMode == 'current') return $menuItem->Title;
		}
	}

	/**
	 * The application logo path. Customisable by calling
	 * LeftAndMain::setLogo() - the first parameter.
	 *
	 * @var unknown_type
	 */
	static $application_logo = 'cms/images/mainmenu/logo.gif';

	/**
	 * The application logo style. Customisable by calling
	 * LeftAndMain::setLogo() - the second parameter.
	 *
	 * @var String
	 */
	static $application_logo_style = '';
	
	/**
	 * Set the CMS application logo.
	 *
	 * @param String $logo Relative path to the logo
	 * @param String $logoStyle Custom CSS styles for the logo
	 * 							e.g. "border: 1px solid red; padding: 5px;"
	 */
	static function setLogo($logo, $logoStyle) {
		self::$application_logo = $logo;
		self::$application_logo_style = $logoStyle;
		self::$application_logo_text = '';
	}
	
	protected static $loading_image = 'cms/images/loading.gif';
	
	/**
	 * Set the image shown when the CMS is loading.
	 */
	static function set_loading_image($loadingImage) {
		self::$loading_image = $loadingImage;
	}
	
	function LoadingImage() {
		return self::$loading_image;
	}
	
	function LogoStyle() {
		return "background: url(" . self::$application_logo . ") no-repeat; " . self::$application_logo_style;
	}

	/**
	 * Return the base directory of the tiny_mce codebase
	 */
	function MceRoot() {
		return MCE_ROOT;
	}

	/**
	 * Use this as an action handler for custom CMS buttons.
	 */
	function callPageMethod($data, $form) {
		$methodName = $form->buttonClicked()->extraData();
		$record = $this->currentPage();
		if(!$record) return false;
		
		return $record->$methodName($data, $form);
	}
	
	/**
	 * Register the given javascript file as required in the CMS.
	 * Filenames should be relative to the base, eg, SAPPHIRE_DIR . '/javascript/loader.js'
	 */
	public static function require_javascript($file) {
		self::$extra_requirements['javascript'][] = array($file);
	}
	
	/**
	 * Register the given stylesheet file as required.
	 * 
	 * @param $file String Filenames should be relative to the base, eg, THIRDPARTY_DIR . '/tree/tree.css'
	 * @param $media String Comma-separated list of media-types (e.g. "screen,projector") 
	 * @see http://www.w3.org/TR/REC-CSS2/media.html
	 */
	public static function require_css($file, $media = null) {
		self::$extra_requirements['css'][] = array($file, $media);
	}
	
	/**
	 * Register the given "themeable stylesheet" as required.
	 * Themeable stylesheets have globally unique names, just like templates and PHP files.
	 * Because of this, they can be replaced by similarly named CSS files in the theme directory.
	 * 
	 * @param $name String The identifier of the file.  For example, css/MyFile.css would have the identifier "MyFile"
	 * @param $media String Comma-separated list of media-types (e.g. "screen,projector") 
	 */
	static function require_themed_css($name, $media = null) {
		self::$extra_requirements['themedcss'][] = array($name, $media);
	}
	
}

?>
