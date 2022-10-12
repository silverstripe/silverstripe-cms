<?php

namespace SilverStripe\CMS\Tests\CMSEditLinkExtensionTest;

use SilverStripe\Admin\CMSEditLinkExtension;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class BelongsToPage extends DataObject implements TestOnly
{
    private static $table_name = 'CMSEditLinkTest_BelongsToPage';

    private static $cms_edit_owner = 'Parent';

    private static $db = [
        'Name' => 'Varchar(25)',
    ];

    private static $has_one = [
        'Parent' => SiteTree::class,
    ];

    private static $extensions = [
        CMSEditLinkExtension::class,
    ];
}
