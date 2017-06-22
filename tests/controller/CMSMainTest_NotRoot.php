<?php

use SilverStripe\Dev\TestOnly;

class CMSMainTest_NotRoot extends Page implements TestOnly
{
    private static $can_be_root = false;
}
