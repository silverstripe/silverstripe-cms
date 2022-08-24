<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Security\Permission;
use SilverStripe\CMS\Model\SiteTree;

class SiteTreeActionsTest_Page extends SiteTree implements TestOnly
{
    public function canEdit($member = null)
    {
        return Permission::checkMember($member, 'SiteTreeActionsTest_Page_CANEDIT');
    }

    public function canDelete($member = null)
    {
        return Permission::checkMember($member, 'SiteTreeActionsTest_Page_CANDELETE');
    }
}
