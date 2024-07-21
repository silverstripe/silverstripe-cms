@retry @job3
Feature: Redirector Pages
  As an author
  I want to redirect to a different location
  So that I can help users find specific content

  Background:
    Given a "page" "Page 1"
    And a "page" "My Redirect" which redirects to a "page" "Page 1"
    And a "image" "assets/file1.jpg"
    And the "group" "EDITOR" has permissions "Access to 'Pages' section"
    And I am logged in as a member of "EDITOR" group
    And I go to "/admin/pages"

  Scenario: Only the appropriate fields are shown
    When I click on "My Redirect" in the tree
    Then I should see an edit page form
    # distinct from the "A page on your website" text for the option itself
    And I should see "Page on your website" in the "#Form_EditForm_LinkToID_Holder" region
    And I should not see "Choose existing"
    And I should not see "Other website URL"

  Scenario: I can choose to redirect to a file
    When I click on "My Redirect" in the tree
    Then I should see an edit page form
    When I click on the "#Form_EditForm_RedirectionType_File" element
    Then I should see "Choose existing"
    And I should not see "Page on your website" in the "#Form_EditForm_LinkToID_Holder" region
    And I should not see "Other website URL"
    # Necessary to avoid a "unsaved changed" alert from breaking the test
    Given I press the "Save" button

  Scenario: I can choose to redirect to a URL
    When I click on "My Redirect" in the tree
    Then I should see an edit page form
    When I click on the "#Form_EditForm_RedirectionType_External" element
    Then I should see "Other website URL"
    And I should not see "Page on your website" in the "#Form_EditForm_LinkToID_Holder" region
    And I should not see "Choose existing"
    # Necessary to avoid a "unsaved changed" alert from breaking the test
    Given I press the "Save" button
