<?php

namespace SilverStripe\CMS\Controllers;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Extension;
use SilverStripe\View\Requirements;

/**
 * Extension to include custom page icons
 */
class LeftAndMainPageIconsExtension extends Extension
{

    public function init()
    {
        Requirements::customCSS($this->generatePageIconsCss(), CMSMain::PAGE_ICONS_ID);
    }

    /**
     * Include CSS for page icons. We're not using the JSTree 'types' option
     * because it causes too much performance overhead just to add some icons.
     *
     * @return string CSS
     */
    public function generatePageIconsCss()
    {
        $css = '';

        $classes = ClassInfo::subclassesFor(SiteTree::class);
        foreach ($classes as $class) {
            $icon = Config::inst()->get($class, 'icon');
            if (!$icon) {
                continue;
            }

            $cssClass = Convert::raw2htmlid($class);
            $selector = ".page-icon.class-$cssClass, li.class-$cssClass > a .jstree-pageicon";
            $iconURL = SiteTree::singleton($class)->getPageIconURL();
            if ($iconURL) {
                $css .= "$selector { background: transparent url('$iconURL') 0 0 no-repeat; }\n";
            }
        }

        return $css;
    }
}
