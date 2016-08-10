<?php

/**
 * - CMS_DIR: Path relative to webroot, e.g. "cms"
 * - CMS_PATH: Absolute filepath, e.g. "/var/www/my-webroot/cms"
 */
define('CMS_DIR', 'cms');
define('CMS_PATH', BASE_PATH . '/' . CMS_DIR);

/**
 * Register the default internal shortcodes.
 */
ShortcodeParser::get('default')->register(
	'sitetree_link',
	array('SilverStripe\\CMS\\Model\\SiteTree', 'link_shortcode_handler')
);

File::add_extension('SilverStripe\\CMS\\Model\\SiteTreeFileExtension');

// TODO Remove once we can configure CMSMenu through static, nested configuration files
CMSMenu::remove_menu_class('SilverStripe\\CMS\\Controllers\\CMSMain');
CMSMenu::remove_menu_class('SilverStripe\\CMS\\Controllers\\CMSPageEditController');
CMSMenu::remove_menu_class('SilverStripe\\CMS\\Controllers\\CMSPageSettingsController');
CMSMenu::remove_menu_class('SilverStripe\\CMS\\Controllers\\CMSPageHistoryController');
CMSMenu::remove_menu_class('SilverStripe\\CMS\\Controllers\\CMSPageAddController');

CMSMenu::remove_menu_item("SiteConfigLeftAndMain");
