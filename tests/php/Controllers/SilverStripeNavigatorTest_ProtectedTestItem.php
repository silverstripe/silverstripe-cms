<?php

namespace SilverStripe\CMS\Tests\Controllers;

use SilverStripe\CMS\Controllers\SilverStripeNavigatorItem;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;

class SilverStripeNavigatorTest_ProtectedTestItem extends SilverStripeNavigatorItem implements TestOnly
{

    public function getTitle()
    {
        return self::class;
    }

    public function getHTML()
    {
        return null;
    }

    public function canView($member = null)
    {
        if (!$member) {
            $member = Security::getCurrentUser();
        }
        return Permission::checkMember($member, 'ADMIN');
    }
}
