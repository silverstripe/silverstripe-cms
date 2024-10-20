@retry @job2
Feature: Publish a page
  As a site owner
  I want content to go to a draft site before being published
  So that only high quality changes are seen by our visitors

  Background:
    Given a "page" "My Page" with "URLSegment"="my-page" and "Content"="<p>initial content</p>"
    And the "page" "My Page" is not published
    And the "group" "EDITOR" has permissions "Access to 'Pages' section"
    And I am logged in as a member of "EDITOR" group

  @javascript
  Scenario: I can have a unpublished version of a page that is not publicly available
    Given I go to "/my-page"
    Then the page can't be found

  @javascript
  Scenario: I can publish a previously never published page
    Given I go to "/admin/pages"
    And I should see "My Page" in the tree
    And I click on "My Page" in the tree
    And I press the "Publish" button

    Then I am not logged in
    And I go to "/my-page"

    Then I should see "initial content"

  @javascript
  Scenario: I will get different options depending on the current publish state of the page
    Given I go to "/admin/pages"
    And I should see "My Page" in the tree
    And I click on "My Page" in the tree

    When I click "More options" in the "#ActionMenus" element
    Then I should see "Not published" in the "#ActionMenus_MoreOptions" element
    And I should not see an "Unpublish" button
    And I should see an "Archive" button
    And I should see a "Publish" button
    And I should see a "Saved" button

    When I fill in the "Content" HTML field with "<p>my new content</p>"
    And I click "More options" in the "#ActionMenus" element
    Then I should not see an "Unpublish" button
    And I should see a "Publish" button
    And I should see a "Save" button

    When I press the "Publish" button
    And I click "More options" in the "#ActionMenus" element
    Then I should see an "Unpublish" button
    And I should see an "Unpublish and archive" button
    And I should see a "Published" button
    And I should see a "Saved" button

  @javascript
  Scenario: I can unpublish a page
    Given a "page" "Hello" with "URLSegment"="hello" and "Content"="<p>hello world</p>"
    And I go to "/hello"
    Then I should see "hello world"

    And I go to "/admin/pages"
    And I should see "Hello" in the tree
    And I click on "Hello" in the tree

    When I click "More options" in the "#ActionMenus" element
    And I press the "Unpublish" button, confirming the dialog

    Then I am not logged in
    And I go to "/hello"
    Then the page can't be found

  @javascript
  Scenario: I can delete a page from live and draft stage to completely remove it
    Given I go to "/admin/pages"
    And I should see "My Page" in the tree
    And I click on "My Page" in the tree
    And I press the "Publish" button
    And I click "More options" in the "#ActionMenus" element
    Then I should see an "Unpublish" button

    When I press the "Unpublish" button, confirming the dialog
    And I click "More options" in the "#ActionMenus" element
    # Use a css-selector instead of the the "Archive" button otherwise it will get confused with
    # the "Archive" model admin
    Then I see the "#Form_EditForm_action_archive" element
    When I click on the "#Form_EditForm_action_archive" element, confirming the dialog

    Then I should see a "Restore" button
    And I should not see a "Published" button
    And I should not see a "Publish" button
    And I should not see a "Saved" button
    And I should not see a "Save" button

    When I press the "Restore" button, confirming the dialog
    Then I should see a "Publish" button
