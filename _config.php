<?php

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\View\Parsers\ShortcodeParser;

/**
 * Register the default internal shortcodes.
 */
ShortcodeParser::get('default')->register(
    'sitetree_link',
    [SiteTree::class, 'link_shortcode_handler']
);
