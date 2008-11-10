<?php
/**
 * The object manages the main CMS menu
 * 
 * @package cms
 * @subpackage content
 */
class CMSMenu extends Object implements Iterator
{

	protected static $menu_items = array();

	/**
	 * Generate CMS main menu items by collecting valid 
	 * subclasses of {@link LeftAndMain}
	 */
	public static function populate_menu() {
		$cmsClasses = self::get_cms_classes();
		foreach($cmsClasses as $cmsClass) {
			self::add_controller($cmsClass);
		}
		return true;
	}

	
	/**
	 * Add a LeftAndMain controller to the CMS menu.
	 *
	 * @param string $controllerClass The class name of the controller
	 * @return The result of the operation
	 * @todo A director rule is added when a controller link is added, but it won't be removed
	 *			when the item is removed. Functionality needed in {@link Director}.
	 */	
	public static function add_controller($controllerClass) {
		$controller = singleton($controllerClass);

		$link = $controller->Link();
		if(substr($link,-1) == '/') $link = substr($link,0,-1);
		$subRule = $controller->stat('url_rule', true);
		if($subRule[0] == '/') $subRule = substr($subRule,1);
		$rule = $link . '//' . $subRule;
		
		Director::addRules($controller->stat('url_priority', true), array(
			$rule => $controllerClass
			
		));
		
		return self::add_menu_item(
			$controllerClass, 
			$controller->getMenuTitle(), 
			$controller->Link(), 
			$controllerClass,
			$controller->stat('menu_priority')
		);
	}
	
	/**
	 * Add an arbitrary URL to the CMS menu.
	 *
	 * @param string $code A unique identifier (used to create a CSS ID and as it's key in {@link $menu_items}
	 * @param string $menuTitle The link's title in the CMS menu
	 * @param string $url The url of the link
	 * @param integer $priority The menu priority (sorting order) of the menu item
	 * @return boolean The result of the operation.
	 */
	public static function add_link($code, $menuTitle, $url, $priority = -1) {
		return self::add_menu_item($code, $menuTitle, $url, null, $priority);
	}
	
	/**
	 * Add a navigation item to the main administration menu showing in the top bar.
	 *
	 * @uses {@link CMSMenu::$menu_items}
	 *
	 * @param string $code Unique identifier for this menu item (e.g. used by {@link replace_menu_item()} and
	 * 					{@link remove_menu_item}. Also used as a CSS-class for icon customization.
	 * @param string $menuTitle Localized title showing in the menu bar 
	 * @param string $url A relative URL that will be linked in the menu bar.
	 * @param string $controllerClass The controller class for this menu, used to check permisssions.  
	 * 					If blank, it's assumed that this is public, and always shown to users who 
	 * 					have the rights to access some other part of the admin area.
	 * @return boolean Success
	 */
	public static function add_menu_item($code, $menuTitle, $url, $controllerClass = null, $priority = -1) {
		$menuItems = self::$menu_items;
		
		if(isset($menuItems[$code])) return false;
	
		return self::replace_menu_item($code, $menuTitle, $url, $controllerClass, $priority);
	}
	
	/**
	 * Get a single menu item by its code value.
	 *
	 * @param string $code
	 * @return array
	 */
	public static function get_menu_item($code) {
		$menuItems = self::$menu_items;
		return (isset($menuItems[$code])) ? $menuItems[$code] : false; 
	}
	
	/**
	 * Get all menu entries.
	 *
	 * @return array
	 */
	public static function get_menu_items() {
		return self::$menu_items;
	}
	
	/**
	 * Removes an existing item from the menu.
	 *
	 * @param string $code Unique identifier for this menu item
	 */
	public static function remove_menu_item($code) {
		$menuItems = self::$menu_items;
		if(isset($menuItems[$code])) unset($menuItems[$code]);
		// replace the whole array
		self::$menu_items = $menuItems;
	}
	
	/**
	 * Clears the entire menu
	 *
	 */
	public static function clear_menu() {
		self::$menu_items = array();
	}

	/**
	 * Replace a navigation item to the main administration menu showing in the top bar.
	 *
	 * @param string $code Unique identifier for this menu item (e.g. used by {@link replace_menu_item()} and
	 * 					{@link remove_menu_item}. Also used as a CSS-class for icon customization.
	 * @param string $menuTitle Localized title showing in the menu bar 
	 * @param string $url A relative URL that will be linked in the menu bar.
	 * 					Make sure to add a matching route via {@link Director::addRules()} to this url.
	 * @param string $controllerClass The controller class for this menu, used to check permisssions.  
	 * 					If blank, it's assumed that this is public, and always shown to users who 
	 * 					have the rights to access some other part of the admin area.
	 * @return boolean Success
	 */
	public static function replace_menu_item($code, $menuTitle, $url, $controllerClass = null, $priority = -1) {
		$menuItems = self::$menu_items;
		$menuItems[$code] = new CMSMenuItem($menuTitle, $url, $controllerClass, $priority);
		foreach($menuItems as $key => $menuItem) {
			$menuPriority[$key] = $menuItem->priority;
		}
		array_multisort($menuPriority, SORT_DESC, $menuItems);

		self::$menu_items = $menuItems;
		return true;
	}	

	/**
	 * A utility funciton to retrieve subclasses of a given class that
	 * are instantiable (ie, not abstract) and have a valid menu title.
	 *
	 * @todo A variation of this function could probably be moved to {@link ClassInfo}
	 * @param string $root The root class to begin finding subclasses
	 * @param boolean $recursive Look for subclasses recursively?
	 * @return array Valid, unique subclasses
	 */
	public static function get_cms_classes($root = 'LeftAndMain', $recursive = true) {
		$subClasses = array_values(ClassInfo::subclassesFor($root));
		foreach($subClasses as $className) {
			if($recursive && $className != $root) {
				$subClasses = array_merge($subClasses, array_values(ClassInfo::subclassesFor($className)));
			}
		}
		$subClasses = array_unique($subClasses);
		foreach($subClasses as $key => $className) {
			// Remove abstract classes and LeftAndMain
			$classReflection = new ReflectionClass($className);
			if(!$classReflection->isInstantiable() || 'LeftAndMain' == $className) {
				unset($subClasses[$key]);
			} else {
				if(singleton($className)->getMenuTitle() == '') {
					unset($subClasses[$key]);
				}
			}
			
		}
		return $subClasses;
	}
	
	// Iterator Interface Methods	
	public function key() {
		return key(self::$menu_items);
	}
	
	public function current() {
		return current(self::$menu_items);
	}
	
	public function next() {
		return next(self::$menu_items);
	}
	
	public function rewind() {
		return reset(self::$menu_items);
	}
	
	public function valid() {
		return (bool)self::current();
	}
	
}
?>
