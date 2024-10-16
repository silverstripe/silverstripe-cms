<?php

namespace SilverStripe\CMS\Tests\Controllers\CMSMainTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Hierarchy\Hierarchy;

class TestHierarchicalDataObject extends DataObject implements TestOnly
{
    private static array $db = [
        'Sort' => 'Int',
    ];

    private static array $extensions = [
        Hierarchy::class,
    ];
}
