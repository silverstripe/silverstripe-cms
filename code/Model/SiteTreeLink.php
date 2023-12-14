<?php

namespace SilverStripe\CMS\Model;

use SilverStripe\ORM\DataObject;

/**
 * Represents a link between a dataobject parent and a page in a HTML content area
 *
 * @method SiteTree Linked()
 * @method DataObject Parent()
 */
class SiteTreeLink extends DataObject
{
    private static $table_name = 'SiteTreeLink';

    private static $has_one = [
        'Parent' => DataObject::class,
        'Linked' => SiteTree::class,
    ];
}
