<?php

use SilverStripe\Dev\TestOnly;

class VirtualPageTest_NotRoot extends Page implements TestOnly
{
    private static $can_be_root = false;
}
