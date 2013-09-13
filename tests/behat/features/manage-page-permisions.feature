@todo
Feature: Manage global page permissions
As an administrator
I want to manage view and edit permission defaults on pages
In order to set good defaults and avoid repeating myself on each page

Background:
  Given I have an "Administrator" user in a "Administrators" Security Group
  Given I have an "Content Author" user in a "Content Authors" Security Group
  And I am logged in as an "ADMIN"
  And I navigate to the "Settings" CMS section

Scenario: I can open global view permissions to everyone
    Given I select the 'Access' tab
    And I select "Anyone" in the 'Who can view pages on this site?' field
    And press the "Save" button
   When I visit the homepage without being logged in
   Then I can see "Welcome"
   
Scenario: I can limit global view permissions to logged-in users
    Given I select the 'Access' tab
    And I select "Logged-in users" in 'Who can view pages on this site?'
    And press the 'Save' button
    When I visit the homepage without being logged in
   Then I am redirected to the log-in page
   When I visit the homepage as "Content Author"
   Then I can see "Welcome"

Scenario: I can limit global view permissions to certain groups
    Given I select the 'Access' tab
    And I select "Only these people (choose from list)" in 'Who can view pages on this site?'
    And I select "Administrators" in the "Viewer Groups" dropdown
    And press the 'Save' button
    When I visit the homepage without being logged in
   Then I am redirected to the log-in page
   When I visit the homepage as "Content Author"
   Then I am redirected to the log-in page
   When I visit the homepage as "Administrator"
   Then I can see "Welcome"

Scenario: I can limit global edit permissions to logged-in users
    Given I select the 'Access' tab
    And I select "Logged-in users" in 'Who can edit pages on this site?'
    And press the 'Save' button
    Then pages should be editable by "Content Authors"
    And pages should be editable by "Administrators"

Scenario: I can limit global edit permissions to certain groups
    Given I select the 'Access' tab
    And I select "Only these people (choose from list)" in 'Who can edit pages on this site?'
    And I select "Administrators" in the "Viewer Groups" dropdown
    And press the 'Save' button
    Then pages should not be editable by "Content Authors"
    But pages should be editable by "Administrators"

   