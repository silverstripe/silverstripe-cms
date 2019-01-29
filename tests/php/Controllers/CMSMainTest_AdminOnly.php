<?php

namespace SilverStripe\CMS\Tests\Controllers;

use SilverStripe\Dev\TestOnly;
use Page;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;

class CMSMainTest_AdminOnly extends Page implements TestOnly
{
    private static $table_name = 'CMSMainTest_AdminOnly';

    public function canCreate($member = null, $context = array())
    {
        if (!$member) {
            $member = Security::getCurrentUser();
        }

        if(!$member || !Permission::checkMember($member, 'ADMIN')) {
            return false;
        }

        return parent::canCreate($member, $context);
    }
}
