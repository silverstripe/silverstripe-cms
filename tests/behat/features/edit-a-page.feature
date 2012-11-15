@database-defaults
Feature: Edit a page
  As an author
  I want to edit a page in the CMS
  So that I correct errors and provide new information

  Background:
    Given I am logged in with "ADMIN" permissions
    And I go to "/admin/pages"
    Then I should see "About Us" in CMS Tree

  @javascript
  Scenario: I can open a page for editing from the pages tree
    When I follow "About Us"
    Then I should see an edit page form

  @javascript
  Scenario: I can edit title and content and see the changes on draft
    When I follow "About Us"
    Then I should see an edit page form

    When I fill in "Title" with "About Us!"
    And I fill in the content form with "my new content"
    And I press the "Save Draft" button
    Then I should see a "Saved." notice

    When I follow "About Us"
    Then the "Title" field should contain "About Us!"
    And the content form should contain "my new content"