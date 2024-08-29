<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Core\Extension;

/**
 * An extension that can even deny actions to admins
 */
class SiteTreeTest_AdminDeniedExtension extends Extension implements TestOnly
{
    protected function canCreate($member)
    {
        return false;
    }

    protected function canEdit($member)
    {
        return false;
    }

    protected function canDelete($member)
    {
        return false;
    }

    protected function canAddChildren()
    {
        return false;
    }

    protected function canView()
    {
        return false;
    }
}
