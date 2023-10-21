Feature: Preview a page
  As an author
  I want to preview the page I'm editing in the CMS
  So that I can see how it would look like to my visitors

  Background:
    Given a "page" "About Us"
    And the "group" "EDITOR" has permissions "Access to 'Pages' section"
    And I am logged in as a member of "EDITOR" group

  @javascript
  Scenario: I can show a preview of the current page from the pages section
    And I go to "/admin/pages"
    Then I should see "About Us" in the tree

    When I click on "About Us" in the tree
    And I set the CMS mode to "Preview mode"
    Then I can see the preview panel
    And the preview contains "About Us"
    Then I set the CMS mode to "Edit mode"

  Scenario: I can see an updated preview when editing content
    And I go to "/admin/pages"
    Then I should see "About Us" in the tree

    When I click on "About Us" in the tree
    And I fill in the "Content" HTML field with "<p>first content</p>"
    And I press the "Publish" button
    And I fill in the "Content" HTML field with "<p>my new content</p>"
    And I press the "Save" button
    And I set the CMS mode to "Preview mode"

    When I switch the preview to "Published"
    Then the preview does not contain "my new content"
    And the preview contains "first content"

    When I switch the preview to "Draft"
    Then the preview does not contain "first content"
    And the preview contains "my new content"

    And I set the CMS mode to "Edit mode"
    Then I should see an edit page form
