Feature: Manage global page permissions
As an administrator
I want to manage view and edit permission defaults on pages
In order to set good defaults and avoid repeating myself on each page

Background:
	Given a "page" "Home" with "Content"="Welcome"
	And a "group" "AUTHOR group" has permissions "Access to 'Pages' section"
	And a "group" "SECURITY group" has permissions "Access to 'Security' section"
	And I am logged in with "ADMIN" permissions
	And I go to "admin/settings"
	And I click the "Access" CMS tab

Scenario: I can open global view permissions to everyone
	Given I select "Anyone" from "Who can view pages on this site?" input group
	And I press the "Save" button
	When I am not logged in
	And I go to the homepage
	Then I should see "Welcome"

Scenario: I can limit global view permissions to logged-in users
	Given I select "Logged-in users" from "Who can view pages on this site?" input group
	And I press the "Save" button
	When I am not logged in
	And I go to the homepage
	Then I should see a log-in form
	When I am logged in with "AUTHOR" permissions
	And I go to the homepage
	Then I should see "Welcome"

Scenario: I can limit global view permissions to certain groups
	Given I select "Only these people (choose from list)" from "Who can view pages on this site?" input group
	And I select "AUTHOR group" from "Viewer Groups" with javascript
	And I press the "Save" button
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

Scenario: I can limit global edit permissions to logged-in users
	Given I select "Anyone who can log-in to the CMS" from "Who can edit pages on this site?" input group
	And I press the "Save" button
	Then pages should be editable by "AUTHOR"
	And pages should be editable by "ADMIN"

Scenario: I can limit global edit permissions to certain groups
	Given I select "Only these people (choose from list)" from "Who can edit pages on this site?" input group
	And I select "ADMIN group" from "Editor Groups" with javascript
	And I press the "Save" button
	Then pages should not be editable by "AUTHOR"
	But pages should be editable by "ADMIN"

