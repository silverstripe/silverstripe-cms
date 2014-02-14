Feature: Edit site wide settings
	As a site administrator
	I want to configure the sites title, tagline and theme
	So that I dont have to change any templates

	Background:
		Given I am logged in with "ADMIN" permissions
		And a "page" "home"
		And I go to "/admin/settings"

	@javascript
	Scenario: I can edit my Site title and Tagline
		Given I should see an edit page form
		And I should see "Site title"
		And I should see "Tagline"

		When I fill in "Site title" with "Test Site"
		And I fill in "Tagline" with "Site is under construction"
		And I press the "Save" button
		And I reload the page
		Then I should see "Test Site" in the ".cms-logo" element

		When I go to "/home"
		Then I should see "Test Site"
		And I should see "Site is under construction"

	Scenario: I can change the theme of the website
		Given a theme "behattest"
		And a template "Page.ss" in theme "behattest" with content "<h1>This is the behat test theme</h1>"
		When I go to "/admin/settings"
		Then I should see an edit page form
		And I should see "Theme"

		When I select "behattest" from "Theme"
		And I press the "Save" button
		And I go to "/home?flush=1"
		Then I should see "This is the behat test theme"