@retry @job1
Feature: Create a page
  As an author
  I want to create a page in the CMS
  So that I can grow my website

  Background:
    Given I add an extension "SilverStripe\BehatExtension\Extensions\ActivateSudoModeServiceExtension" to the "SilverStripe\Security\SudoMode\SudoModeService" class
    And a "page" "MyPage"
    And a "virtual page" "MyVirtualPage"
    And the "group" "EDITOR" has permissions "Access to 'Pages' section"

  @javascript
  Scenario: I can create a page from the pages section
    When I am logged in as a member of "EDITOR" group
    And I go to "/admin/pages"
    Then I should see "MyPage" in the tree
    And I should see a "Add new" button in CMS Content Toolbar
    When I press the "Add new" button
    # default selected option is "Page"
    Then I should see "Generic content page" in the "#Form_AddForm_PageType div.radio.selected" element
    Then the "Generic content page" checkbox should be checked
    When I press the "Create" button
    Then I should see an edit page form

  @javascript
  Scenario: I can create a page under another page
    When I am logged in as a member of "EDITOR" group
    And I go to "/admin/pages"
    Then I should see "MyPage" in the tree
    And I should see a "Add new" button in CMS Content Toolbar
    When I press the "Add new" button
    And I select the "Under another page" radio button
    # Virtual page doesn't allow children, page radio button below should be disabled
    And I select "MyVirtualPage" in the "#Form_AddForm_ParentID_Holder" tree dropdown
    And I wait for 2 seconds
    Then I should see a "#Form_AddForm_PageType_Page[disabled]" element
    # Normal pages allows children, page radio button below should not be disabled
    When I select "MyPage" in the "#Form_AddForm_ParentID_Holder" tree dropdown
    And I wait for 2 seconds
    And I select the "Page" radio button
    Then I should not see a "#Form_AddForm_PageType_Page[disabled]" element
    And I press the "Create" button
    Then I should see an edit page form

  Scenario: I cannot add root level pages without permission
    When I am logged in with "ADMIN" permissions
    And I go to "/admin/settings"
    And I click the "Access" CMS tab
    And I click on the "#Form_EditForm_CanCreateTopLevelType_OnlyTheseUsers" element
    And I press the "Save" button
    And I click on the ".cms-login-status__logout-link" element
    When I am logged in as a member of "EDITOR" group
    And I press the "Add new" button
    Then I see the "Top level" radio button "disabled" attribute equals "1"

  Scenario: I can change the default page type for new pages
    Given I add an extension "SilverStripe\CMS\Tests\Behaviour\DefaultAddPageOptionExtension" to the "SilverStripe\CMS\Controllers\CMSPageAddController" class
    And I am logged in as a member of "EDITOR" group
    When I go to "/admin/pages"
    And I press the "Add new" button
    Then I should see "Virtual Page" in the "#Form_AddForm_PageType div.radio.selected" element
    # Use the class description when selecting so the subsites virtual page isn't found in kitchen sink
    Then the "Displays the content of another page" checkbox should be checked

  Scenario: I can create a page using the context menu
    Given I am logged in as a member of "EDITOR" group
    When I go to "/admin/pages"
    And I right click on "MyPage" in the tree
    And I hover on "Add new page here" in the context menu
    And I click on "Page" in the context menu
    Then I should see "New Page" in the tree
    And I should see an edit page form

  Scenario: Failed validation during page creation shows the validation error
    Given I add an extension "SilverStripe\CMS\Tests\Behaviour\ValidationFailedAddPageExtension" to the "SilverStripe\CMS\Controllers\CMSPageAddController" class
    And I am logged in as a member of "EDITOR" group
    When I go to "/admin/pages"
    # First use the context menu approach
    And I right click on "MyPage" in the tree
    And I hover on "Add new page here" in the context menu
    And I click on "Page" in the context menu
    And I should see a "Validation Error" error toast
    And I should see a "form#Form_AddForm" element
    And I should see "This field failed validation"
    Then I should not see an edit page form
    # Then check we get the same result with normal form submission
    When I go to "/admin/pages"
    And I press the "Add new" button
    And I press the "Create" button
    And I should see a "Validation Error" error toast
    And I should see a "form#Form_AddForm" element
    And I should see "This field failed validation"
    Then I should not see an edit page form
