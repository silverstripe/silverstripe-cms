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
ShortcodeParser::get('default')->register('sitetree_link', array('SiteTree', 'link_shortcode_handler'));

File::add_extension('SiteTreeFileExtension');

// TODO Remove once we can configure CMSMenu through static, nested configuration files
CMSMenu::remove_menu_item('CMSMain');
CMSMenu::remove_menu_item('CMSPageEditController');
CMSMenu::remove_menu_item('CMSPageSettingsController');
CMSMenu::remove_menu_item('CMSPageHistoryController');
CMSMenu::remove_menu_item('CMSPageReportsController');
CMSMenu::remove_menu_item('CMSPageAddController');
CMSMenu::remove_menu_item('CMSFileAddController');
