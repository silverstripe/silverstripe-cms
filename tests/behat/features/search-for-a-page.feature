@retry
Feature: Search for a page
  As an author
  I want to search for a page in the CMS
  So that I can efficiently navigate nested content structures

  Background:
    Given a "page" "Home"
    And a "page" "About Us"
    And a "page" "Contact Us"
    And I am logged in with "ADMIN" permissions
    And I go to "/admin/pages"
    And I expand the content filters

  Scenario: I can search for a page by its title
    Given I fill in "Search" with "About Us"
    And I press the "Search" button
    Then I should see "About Us" in the cms list
    But I should not see "Contact Us" in the cms list

  Scenario: I can search for a page by its type
    Given a "Virtual Page" "My Virtual Page"
    When I select "Virtual Page" from "Page type"
    And I press the "Search" button
    Then I should see "My Virtual Page" in the cms list
    But I should not see "Contact Us" in the cms list

  Scenario: I can search for a page by its oldest last edited date
    Given a "page" "Recent Page"
    And a "page" "Old Page" was last edited "7 days ago"
    When I fill in "From" with "the date of 5 days ago"
    And I press the "Search" button
    Then I should see "Recent Page" in the cms list
    But I should not see "Old Page" in the cms list

  Scenario: I can search for a page by its newest last edited date
    Given a "page" "Recent Page"
    And a "page" "Old Page" was last edited "7 days ago"
    When I fill in "To" with "the date of 5 days ago"
    And I press the "Search" button
    Then I should not see "Recent Page" in the cms list
    But I should see "Old Page" in the cms list

  Scenario: I can include deleted pages in my search
    Given a "page" "Deleted Page"
    And the "page" "Deleted Page" is unpublished
    And the "page" "Deleted Page" is deleted
    When I press the "Search" button
    Then I should not see "Deleted Page" in the cms list
    When I expand the content filters
    And I select "All pages, including archived" from "Page status"
    And I press the "Search" button
    Then I should see "Deleted Page" in the cms list

  Scenario: I can include only deleted pages in my search
    Given a "page" "Deleted Page"
    And the "page" "Deleted Page" is unpublished
    And the "page" "Deleted Page" is deleted
    When I press the "Search" button
    Then I should not see "Deleted Page" in the cms list
    When I expand the content filters
    And I select "Archived pages" from "Page status"
    And I press the "Search" button
    Then I should see "Deleted Page" in the cms list
    And I should not see "About Us" in the cms list

  Scenario: I can include draft pages in my search
    Given a "page" "Draft Page"
    And the "page" "Draft Page" is not published
    When I press the "Search" button
    Then I should see "Draft Page" in the cms list
    When I expand the content filters
    And I select "Draft pages" from "Page status"
    And I press the "Search" button
    Then I should see "Draft Page" in the cms list
    And I should not see "About Us" in the cms list

  Scenario: I can include changed pages in my search
    When I click on "About Us" in the tree
    Then I should see an edit page form

    When I fill in the "Content" HTML field with "<p>my new content</p>"
    And I press the "Save draft" button
    Then I should see "Saved" in the "button#Form_EditForm_action_save" element

    When I go to "/admin/pages"
    And I expand the content filters
    When I select "Modified pages" from "Page status"
    And I press the "Search" button
    Then I should see "About Us" in the cms list
    And I should not see "Home" in the cms list

  Scenario: I can include live pages in my search
    Given a "page" "Live Page"
    And the "page" "Live Page" is published
    And the "page" "Live Page" is deleted
    When I press the "Search" button
    Then I should not see "Live Page" in the cms list
    When I expand the content filters
    And I select "Live but removed from draft" from "Page status"
    And I press the "Search" button
    Then I should see "Live Page" in the cms list
    And I should not see "About Us" in the cms list

  Scenario: I can include only live pages in my search
    Given a "page" "Live Page"
    And the "page" "Live Page" is published
    And a "page" "Draft Page"
    And a "page" "Draft Page" is unpublished
    And a "page" "Deleted Page"
    And the "page" "Deleted Page" is unpublished
    And the "page" "Deleted Page" is deleted

    When I select "Published pages" from "Page status"
    And I press the "Search" button
    Then I should not see "Draft Page" in the cms list
    And I should not see "Deleted Page" in the cms list
    But I should see "Live Page" in the cms list
