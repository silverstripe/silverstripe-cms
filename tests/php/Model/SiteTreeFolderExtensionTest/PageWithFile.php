<?php

namespace SilverStripe\CMS\Tests\Model\SiteTreeFolderExtensionTest;

use Page;
use SilverStripe\Assets\File;
use SilverStripe\CMS\Model\SiteTreeFolderExtension;
use SilverStripe\Dev\TestOnly;

/**
 * @mixin SiteTreeFolderExtension
 */
class PageWithFile extends Page implements TestOnly
{
    private static $table_name = 'SiteTreeFolderExtensionTest_PageWithFile';

    private static $has_one = [
        'LinkedFile' => File::class,
    ];

    private static $extensions = [
        SiteTreeFolderExtension::class,
    ];
}
