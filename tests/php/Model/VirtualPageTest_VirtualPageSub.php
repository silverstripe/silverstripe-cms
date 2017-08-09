<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\CMS\Model\VirtualPage;
use SilverStripe\Dev\TestOnly;

class VirtualPageTest_VirtualPageSub extends VirtualPage implements TestOnly
{
    private static $table_name = 'VirtualPageTest_VirtualPageSub';

    private static $db = array(
        'MyProperty' => 'Varchar',
    );
}
