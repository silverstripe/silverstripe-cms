<?php

use SilverStripe\Admin\CMSMenu;
use SilverStripe\CMS\Controllers\CMSMain;
use SilverStripe\CMS\Controllers\CMSPageAddController;
use SilverStripe\CMS\Controllers\CMSPageEditController;
use SilverStripe\CMS\Controllers\CMSPageHistoryController;
use SilverStripe\CMS\Controllers\CMSPageSettingsController;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\HTMLEditor\TinyMCEConfig;
use SilverStripe\View\Parsers\ShortcodeParser;

/**
 * Define constants
 *
 * - CMS_DIR: Path relative to webroot, e.g. "cms"
 * - CMS_PATH: Absolute filepath, e.g. "/var/www/my-webroot/cms"
 */
call_user_func(function () {
    // Check if CMS is root dir, or subdir
    if (strcasecmp(__DIR__, BASE_PATH) === 0) {
        $clientPath = 'client';
    } else {
        $clientPath = basename(__DIR__) . '/client';
    }

    // Enable insert-link to internal pages
    TinyMCEConfig::get('cms')
        ->enablePlugins(array(
            'sslinkinternal' => "{$clientPath}/dist/js/TinyMCE_sslink-internal.js",
            'sslinkanchor' => "{$clientPath}/dist/js/TinyMCE_sslink-anchor.js",
        ));
});


/**
 * Register the default internal shortcodes.
 */
ShortcodeParser::get('default')->register(
    'sitetree_link',
    array(SiteTree::class, 'link_shortcode_handler')
);

// TODO Remove once we can configure CMSMenu through static, nested configuration files
CMSMenu::remove_menu_class(CMSMain::class);
CMSMenu::remove_menu_class(CMSPageEditController::class);
CMSMenu::remove_menu_class(CMSPageSettingsController::class);
CMSMenu::remove_menu_class(CMSPageHistoryController::class);
CMSMenu::remove_menu_class(CMSPageAddController::class);
