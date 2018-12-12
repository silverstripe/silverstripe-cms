Feature: Create a page
  As an author
  I want to create a page in the CMS
  So that I can grow my website

  Background:
    Given a "page" "MyPage"
    Given I am logged in with "ADMIN" permissions
    And I go to "/admin/pages"
    Then I should see "MyPage" in the tree
    And I should see a "Add new" button in CMS Content Toolbar

  @javascript
  Scenario: I can create a page from the pages section
    Given I go to "/admin/pages"
    When I press the "Add new" button
    And I select the "Page" radio button
    And I press the "Create" button
    Then I should see an edit page form

  @javascript
  Scenario: I can create a page under another page
    Given I go to "/admin/pages"
    When I press the "Add new" button
    And I select the "Under another page" radio button
    And I select "MyPage" in the "#Form_AddForm_ParentID_Holder" tree dropdown
    And I select the "Page" radio button
    And I press the "Create" button
    Then I should see an edit page form
