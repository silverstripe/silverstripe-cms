<?php

namespace SilverStripe\CMS\Controllers;

use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Admin\Navigator\SilverStripeNavigatorItem;
use SilverStripe\CMS\Model\RedirectorPage;
use SilverStripe\Control\Controller;

class SilverStripeNavigatorItem_CMSLink extends SilverStripeNavigatorItem
{
    /** @config */
    private static $priority = 10;

    public function getHTML()
    {
        return sprintf(
            '<a href="%s">%s</a>',
            $this->record->CMSEditLink(),
            _t('SilverStripe\\CMS\\Controllers\\ContentController.CMS', 'CMS')
        );
    }

    public function getTitle()
    {
        return _t('SilverStripe\\CMS\\Controllers\\ContentController.CMS', 'CMS', 'Used in navigation. Should be a short label');
    }

    public function getLink()
    {
        return $this->record->CMSEditLink();
    }

    public function isActive()
    {
        return (Controller::curr() instanceof LeftAndMain);
    }

    public function canView($member = null)
    {
        return (
            // Don't show in CMS
            !(Controller::curr() instanceof LeftAndMain)
            // Don't follow redirects in preview, they break the CMS editing form
            && !($this->record instanceof RedirectorPage)
        );
    }
}
