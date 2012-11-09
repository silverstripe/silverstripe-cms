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
    And I press "Preview »" button
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
    And I fill in content form with "my new content"
    And I press "Save Draft" button
    And I press "Preview »" button

    When I follow "Published Site"
    Then the preview does not contain "my new content"
    And the preview contains "You can fill"

    When I follow "Draft Site"
    Then the preview does not contain "You can fill"
    And the preview contains "my new content"

    When I press "« Edit" button
    Then I should see an edit page form