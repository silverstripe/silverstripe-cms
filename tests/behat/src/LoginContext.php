<?php

namespace SilverStripe\CMS\Tests\Behaviour;

use Page;
use PHPUnit\Framework\Assert;
use SilverStripe\BehatExtension\Context\LoginContext as BehatLoginContext;
use SilverStripe\SiteConfig\SiteConfig;

class LoginContext extends BehatLoginContext
{
    /**
    *
    * Check if the user can edit a page
    *
    * Example: Then pages should be editable by "Admin"
    * Then pages should not be editable by "Admin"
    *
    * @Then /^pages should( not? |\s*)be editable by "([^"]*)"$/
    */
    public function pagesShouldBeEditableBy($negative, $permCode)
    {
        // Reset permission cache
        $page = Page::get()->First();
        Assert::assertNotNull($page, 'A page exists');
        $email = "{$permCode}@example.org";
        $password = 'Password!456';
        $member = $this->generateMemberWithPermission($email, $password, $permCode);
        $canEdit = strstr($negative ?? '', 'not') ? false : true;
        // Flush the SiteConfig cache so that siteconfig behat tests that update a
        // SiteConfig DataObject will not be referring to a stale verion of itself
        // which can happen because SiteConfig::current_site_config() uses DataObject::get_one()
        // which will caches its result by default
        SiteConfig::current_site_config()->flushCache();
        if ($canEdit) {
            Assert::assertTrue($page->canEdit($member), 'The member can edit this page');
        } else {
            Assert::assertFalse($page->canEdit($member), 'The member cannot edit this page');
        }
    }
}
