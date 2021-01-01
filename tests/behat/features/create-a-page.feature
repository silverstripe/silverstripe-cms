Feature: Create a page
  As an author
  I want to create a page in the CMS
  So that I can grow my website

  Background:
    Given a "page" "MyPage"
    And a "group" "AUTHOR group" has permissions "Access to 'Pages' section"
    And I am logged in with "ADMIN" permissions

  @javascript
  Scenario: I can create a page from the pages section
    When I go to "/admin/pages"
    Then I should see "MyPage" in the tree
    And I should see a "Add new" button in CMS Content Toolbar
    When I press the "Add new" button
    And I select the "Page" radio button
    And I press the "Create" button
    Then I should see an edit page form

  @javascript
  Scenario: I can create a page under another page
    When I go to "/admin/pages"
    Then I should see "MyPage" in the tree
    And I should see a "Add new" button in CMS Content Toolbar
    When I press the "Add new" button
    And I select the "Under another page" radio button
    And I select "MyPage" in the "#Form_AddForm_ParentID_Holder" tree dropdown
    And I select the "Page" radio button
    And I press the "Create" button
    Then I should see an edit page form

  Scenario: I cannot add root level pages without permission
    When I go to "/admin/settings"
    And I click the "Access" CMS tab
    And I click the "#Form_EditForm_CanCreateTopLevelType_OnlyTheseUsers" element
    And I press the "Save" button
    And I click the ".cms-login-status__logout-link" element
    When I am logged in with "AUTHOR" permissions
    And I press the "Add new" button
    Then I see the "Top level" radio button "disabled" attribute equals "1"
    And I see the "Under another page" radio button "checked" attribute equals "1"
