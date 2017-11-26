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
            $iconURL = SiteTree::singleton($class)->getPageIconURL();
            if ($iconURL) {
                $cssClass = Convert::raw2htmlid($class);
                $selector = sprintf('.page-icon.class-%1$s, li.class-%1$s > a .jstree-pageicon', $cssClass);
                $css .= sprintf('%s { background: transparent url(\'%s\') 0 0 no-repeat; }', $selector, $iconURL);
            }
        }
        return $css;
    }
}
