<?php

namespace SilverStripe\CMS\Tests\Behaviour;

use Page;
use SilverStripe\BehatExtension\Context\LoginContext as BehatLoginContext;

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
        assertNotNull($page, 'A page exists');
        $email = "{$permCode}@example.org";
        $password = 'Password!456';
        $member = $this->generateMemberWithPermission($email, $password, $permCode);
        $canEdit = strstr($negative, 'not') ? false : true;

        if ($canEdit) {
            assertTrue($page->canEdit($member), 'The member can edit this page');
        } else {
            assertFalse($page->canEdit($member), 'The member cannot edit this page');
        }
    }
}
