<?php

namespace SilverStripe\CMS\Controllers;

use SilverStripe\CMS\Controllers\SilverStripeNavigatorItem;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Security\Member;
use SilverStripe\Versioned\Versioned;

class SilverStripeNavigatorItem_Unversioned extends SilverStripeNavigatorItem
{
    public function getHTML()
    {
        $recordLink = Convert::raw2att($this->getLink());
        $linkTitle = _t('SilverStripe\\CMS\\Controllers\\ContentController.UNVERSIONEDPREVIEW', 'Preview');
        return "<a class=\"current\" href=\"$recordLink\">$linkTitle</a>";
    }

    public function getLink()
    {
        return $this->getRecord()->PreviewLink() ?? '';
    }

    public function getTitle()
    {
        return  _t(
            'SilverStripe\\CMS\\Controllers\\ContentController.UNVERSIONEDPREVIEW',
            'Preview',
            'Used for the Switch between states (if any other other states are added). Needs to be a short label'
        );
    }

    /**
     * True if the record doesn't have the Versioned extension and is configured to display this item.
     *
     * @param Member $member
     * @return bool
     */
    public function canView($member = null)
    {
        return (
            !$this->getRecord()->hasExtension(Versioned::class)
            && $this->showUnversionedLink()
            && $this->getLink()
        );
    }

    /**
     * True if the record is configured to display this item.
     *
     * @return bool
     */
    public function showUnversionedLink(): bool
    {
        return (bool) Config::inst()->get(get_class($this->record), 'show_unversioned_preview_link');
    }

    /**
     * This item is always active, as there are unlikely to be other preview states available for the record.
     *
     * @return bool
     */
    public function isActive()
    {
        return true;
    }
}
