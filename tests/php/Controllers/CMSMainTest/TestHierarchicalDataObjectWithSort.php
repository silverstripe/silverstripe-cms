<?php

namespace SilverStripe\CMS\Tests\Controllers\CMSMainTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Hierarchy\Hierarchy;

class TestHierarchicalDataObjectWithSort extends DataObject implements TestOnly
{
    private static array $db = [
        'Sort' => 'Int',
    ];

    private static string $sort_field = 'Sort';

    private static array $extensions = [
        Hierarchy::class,
    ];
}
