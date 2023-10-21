<?php

use SilverStripe\Admin\CMSMenu;
use SilverStripe\CMS\Controllers\CMSMain;
use SilverStripe\CMS\Controllers\CMSPageAddController;
use SilverStripe\CMS\Controllers\CMSPageEditController;
use SilverStripe\CMS\Controllers\CMSPageSettingsController;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\Forms\HTMLEditor\TinyMCEConfig;
use SilverStripe\View\Parsers\ShortcodeParser;

call_user_func(function () {
    $module = ModuleLoader::inst()->getManifest()->getModule('silverstripe/cms');

    // Enable insert-link to internal pages
    TinyMCEConfig::get('cms')
        ->enablePlugins([
            'sslinkinternal' => $module
                ->getResource('client/dist/js/TinyMCE_sslink-internal.js'),
            'sslinkanchor' => $module
                ->getResource('client/dist/js/TinyMCE_sslink-anchor.js'),
        ]);
});


/**
 * Register the default internal shortcodes.
 */
ShortcodeParser::get('default')->register(
    'sitetree_link',
    [SiteTree::class, 'link_shortcode_handler']
);

CMSMenu::remove_menu_class(CMSMain::class);
CMSMenu::remove_menu_class(CMSPageEditController::class);
CMSMenu::remove_menu_class(CMSPageSettingsController::class);
CMSMenu::remove_menu_class(CMSPageAddController::class);
