<?php

namespace SilverStripe\CMS\Tests;


use SilverStripe\Dev\TestOnly;
use Page;


class VirtualPageTest_NotRoot extends Page implements TestOnly
{
    private static $can_be_root = false;
}
