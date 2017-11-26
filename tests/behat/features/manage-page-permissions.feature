Feature: Manage page permissions
  As an administrator
  I want to manage view and edit permissions on pages
  In order to allow certain groups of people to view or edit a given page

  Background:
    Given a "page" "Home" with "Content"="<p>Welcome</p>"
    And a "group" "AUTHOR group" has permissions "Access to 'Pages' section"
    And a "group" "SECURITY group" has permissions "Access to 'Security' section"
    And I am logged in with "ADMIN" permissions
    And I go to "/admin/pages"
    And I click on "Home" in the tree
    Then I should see an edit page form
    And I click the "Settings" CMS tab

  # BUG: https://github.com/silverstripe/silverstripe-cms/issues/1897
  # Scenario: I can open view permissions to everyone
  #   Given I select "Anyone" from "Who can view this page?" input group
  #   And I press the "Save" button
  #   When I am not logged in
  #   And I go to the homepage
  #   Then I should see "Welcome"

  Scenario: I can limit page view permissions to logged-in users
    Given I select "Logged-in users" from "Who can view this page?" input group
    And I press the "Save & publish" button
    When I am not logged in
    And I go to the homepage
    Then I should see a log-in form
    When I am logged in with "AUTHOR" permissions
    And I go to the homepage
    Then I should see "Welcome"

  Scenario: I can limit page view permissions to certain groups
    Given I select "Only these groups (choose from list)" from "Who can view this page?" input group
    And I select "AUTHOR group" in the "#Form_EditForm_ViewerGroups_Holder" tree dropdown
    And I press the "Save & publish" button
    When I am not logged in
    And I go to the homepage
    Then I should see a log-in form
    When I am logged in with "SECURITY" permissions
    And I go to the homepage
    Then I will see a "warning" log-in message
    When I am not logged in
    And I am logged in with "AUTHOR" permissions
    And I go to the homepage
    Then I should see "Welcome"

  Scenario: I can limit page edit permissions to logged-in users
    Given I select "Logged-in users" from "Who can edit this page?" input group
    And I press the "Save & publish" button
    Then pages should be editable by "AUTHOR"
    And pages should be editable by "ADMIN"

  Scenario: I can limit page edit permissions to certain groups
    Given I select "Only these groups (choose from list)" from "Who can edit this page?" input group
    And I select "ADMIN group" in the "#Form_EditForm_EditorGroups_Holder" tree dropdown
    And I press the "Save & publish" button
    Then pages should not be editable by "AUTHOR"
    But pages should be editable by "ADMIN"

