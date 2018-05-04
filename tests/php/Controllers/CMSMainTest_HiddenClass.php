<?php

namespace SilverStripe\CMS\Tests\Controllers;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\HiddenClass;

class CMSMainTest_HiddenClass extends SiteTree implements TestOnly, HiddenClass
{
}
