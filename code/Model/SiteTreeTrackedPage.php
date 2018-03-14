<?php


namespace SilverStripe\CMS\Model;

use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\HasManyList;

/**
 * Extension applied to SiteTree to provide BackLinkTracking() method.
 * Child extension to SiteTreeLinkTracking.
 *
 * Unlike {@see SiteTreeLinkTracking}, this extension must only be applied to {@see SiteTree}
 *
 * @method HasManyList|SiteTreeLink[] BackLinks()
 */
class SiteTreeTrackedPage extends SiteTreeExtension
{
    private static $has_many = [
        'BackLinks' => SiteTreeLink::class,
    ];

    /**
     * Get the back-link tracking objects
     *
     * @retun ArrayList
     */
    public function BackLinkTracking()
    {
        // @todo - Implement PolymorphicManyManyList to replace this
        $list = ArrayList::create();
        foreach ($this->owner->BackLinks() as $link) {
            // Ensure parent record exists
            $item = $link->Parent();
            if ($item && $item->isInDB()) {
                $list->push($item);
            }
        }
        return $list;
    }
}
