<?php

namespace SilverStripe\CMS\Controllers;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Flushable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\Deprecation;
use SilverStripe\View\Requirements;

/**
 * Extension to include custom page icons
 *
 * @extends Extension<LeftAndMain>
 * @deprecated 5.4.0 Will be renamed to SilverStripe\CMS\Controllers\LeftAndMainRecordIconsExtension
 */
class LeftAndMainPageIconsExtension extends Extension implements Flushable
{
    public function __construct()
    {
        Deprecation::noticeWithNoReplacment(
            '5.4.0',
            'Will be renamed to SilverStripe\CMS\Controllers\LeftAndMainRecordIconsExtension in a future major release',
            Deprecation::SCOPE_CLASS
        );
        parent::__construct();
    }

    /**
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public function init()
    {
        Requirements::customCSS($this->generateRecordIconsCss(), CMSMain::CMS_RECORD_ICONS_ID);
    }

    /**
     * Just broadly clears the cache on flush
     */
    public static function flush()
    {
        Injector::inst()->get(CacheInterface::class . '.SiteTree_PageIcons')->clear();
    }

    /**
     * Include CSS for page icons. We're not using the JSTree 'types' option
     * because it causes too much performance overhead just to add some icons.
     *
     * @return string CSS
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @deprecated 5.4.0 Use generateRecordIconsCss() instead.
     */
    public function generatePageIconsCss()
    {
        Deprecation::notice('5.4.0', 'Use generateRecordIconsCss() instead.');
        return $this->generateRecordIconsCss();
    }

    /**
     * Include CSS for page icons. We're not using the JSTree 'types' option
     * because it causes too much performance overhead just to add some icons.
     *
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public function generateRecordIconsCss(): string
    {
        /** @var CacheInterface $cache */
        $cache = Injector::inst()->get(CacheInterface::class . '.SiteTree_PageIcons');

        if ($cache->has('css')) {
            return $cache->get('css');
        }

        $css = '';
        $classes = ClassInfo::subclassesFor(SiteTree::class);
        foreach ($classes as $class) {
            if (!empty(Config::inst()->get($class, 'icon_class', Config::UNINHERITED))) {
                continue;
            }
            $iconURL = SiteTree::singleton($class)->getPageIconURL();
            if ($iconURL) {
                $cssClass = Convert::raw2htmlid($class);
                $selector = sprintf('.page-icon.class-%1$s, li.class-%1$s > a .jstree-pageicon', $cssClass);
                $css .= sprintf('%s { background: transparent url(\'%s\') 0 0 no-repeat; }', $selector, $iconURL);
            }
        }

        $cache->set('css', $css);

        return $css;
    }
}
