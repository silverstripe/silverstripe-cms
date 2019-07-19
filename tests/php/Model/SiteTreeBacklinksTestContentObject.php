<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class SiteTreeBacklinksTestContentObject extends DataObject implements TestOnly
{
    private static $table_name = 'BacklinkContent';

    private static $db = array(
        'Title' => 'Text',
        'Content' => 'HTMLText',
    );

    private static $singular_name = 'Backlink test content object';
}
