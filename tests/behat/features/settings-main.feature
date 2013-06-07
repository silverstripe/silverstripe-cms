Feature: Edit site wide title and tagline
	As a site administrator
	I want to configure the sites title and tagline
	So that I dont have to change any templates

	Background:
		Given a "page" "home"

	@javascript
	Scenario: I can edit my Site title and Tagline
		Given I am logged in with "ADMIN" permissions
		And I go to "/admin/settings"
		Then I should see an edit page form
		And I should see "Site title"
		And I should see "Tagline"
		And I should see "Theme"

		When I fill in "Site title" with "Test Site"
		And I fill in "Tagline" with "Site is under construction"
		And I press the "Save" button
		And I reload the page
		Then I should see "Test Site" in the ".cms-logo" element

		When I go to "/home"
		Then I should see "Test Site"
		And I should see "Site is under construction"