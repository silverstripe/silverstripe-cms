@database-defaults
Feature: Preview a page
  As an author
  I want to preview the page I'm editing in the CMS
  So that I can see how it would look like to my visitors

  @javascript
  Scenario: I can show a preview of the current page from the pages section
    Given I am logged in with "ADMIN" permissions
    And I go to "/admin/pages"
    Then I should see "About Us" in CMS Tree

    When I follow "About Us"
    And I set the CMS mode to "Preview mode"
    Then I can see the preview panel
    And the preview contains "About Us"

  # TODO:
  # - Only tests correctly on fresh database
  # - We should continue testing against it after we have fixtures ready
  @javascript
  Scenario: I can see an updated preview when editing content
    Given I am logged in with "ADMIN" permissions
    And I go to "/admin/pages"
    Then I should see "About Us" in CMS Tree

    When I follow "About Us"
    And I fill in the "Content" HTML field with "my new content"
    And I press the "Save Draft" button
    And I set the CMS mode to "Preview mode"

    When I switch the preview to "Published"
    Then the preview does not contain "my new content"
    And the preview contains "You can fill"

    When I switch the preview to "Draft"
    Then the preview does not contain "You can fill"
    And the preview contains "my new content"

    And I set the CMS mode to "Edit mode"
    Then I should see an edit page form