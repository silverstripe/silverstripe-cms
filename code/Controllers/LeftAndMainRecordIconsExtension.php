<?php

namespace SilverStripe\CMS\Controllers;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Flushable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\Requirements;

/**
 * Extension to include custom icons.
 * These icons are mostly for use in CMSMain but could be used elsewhere as well.
 *
 * @extends Extension<LeftAndMain>
 */
class LeftAndMainRecordIconsExtension extends Extension implements Flushable
{
    /**
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    protected function onInit()
    {
        Requirements::customCSS($this->generateRecordIconsCss(), CMSMain::CMS_RECORD_ICONS_ID);
    }

    /**
     * Just broadly clears the cache on flush
     */
    public static function flush()
    {
        Injector::inst()->get(CacheInterface::class . '.CMS_RecordIcons')->clear();
    }

    /**
     * Include CSS for record icons. We're not using the JSTree 'types' option
     * because it causes too much performance overhead just to add some icons.
     *
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public function generateRecordIconsCss(): string
    {
        /** @var CacheInterface $cache */
        $cache = Injector::inst()->get(CacheInterface::class . '.CMS_RecordIcons');

        if ($cache->has('css')) {
            return $cache->get('css');
        }

        $css = '';
        $classes = ClassInfo::subclassesFor(DataObject::class);
        foreach ($classes as $class) {
            // If there's a specifically configured CSS class, don't generate any CSS for this record type.
            if (!empty(Config::inst()->get($class, 'cms_icon_class', Config::UNINHERITED))) {
                continue;
            }
            // Generate a record type for this class if there's a relevant icon URL
            $iconURL = CMSMain::singleton()->getRecordIconUrl($class);
            if ($iconURL) {
                $cssClass = Convert::raw2htmlid($class);
                $selector = sprintf('.record-icon.class-%1$s, li.class-%1$s > a .jstree-recordicon', $cssClass);
                $css .= sprintf('%s { background: transparent url(\'%s\') 0 0 no-repeat; }', $selector, $iconURL);
            }
        }

        $cache->set('css', $css);

        return $css;
    }
}
