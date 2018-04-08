<?php

namespace SilverStripe\CMS\Model;

use SilverStripe\ORM\DataObject;

/**
 * Represents a link between a dataobject parent and a page in a HTML content area
 *
 * @method DataObject Parent() Parent object
 * @method SiteTree Linked() Page being linked to
 *
 * Run `MigrateSiteTreeLinkingTask` to migrate from old table to this.
 */
class SiteTreeLink extends DataObject
{
    private static $table_name = 'SiteTreeLink';

    private static $has_one = [
        'Parent' => DataObject::class,
        'Linked' => SiteTree::class,
    ];
}
