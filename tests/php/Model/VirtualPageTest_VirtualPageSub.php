<?php

use SilverStripe\CMS\Model\VirtualPage;
use SilverStripe\Dev\TestOnly;

class VirtualPageTest_VirtualPageSub extends VirtualPage implements TestOnly
{
    private static $db = array(
        'MyProperty' => 'Varchar',
    );
}
