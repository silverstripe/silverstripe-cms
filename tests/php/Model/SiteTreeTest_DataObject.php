<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

/**
 * Class SiteTreeTest_DataObject
 *
 * @property string $Title
 * @method Pages[]|ManyManyList $Pages
 */
class SiteTreeTest_DataObject extends DataObject implements TestOnly
{
    private static $table_name = 'SiteTreeTest_DataObject';

    /**
     * @var array
     */
    private static $db = [
        'Title' => 'Varchar',
    ];

    /**
     * @var array
     */
    private static $many_many = [
        'Pages' => SiteTree::class,
    ];
}
