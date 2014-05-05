Feature: Create a page
	As an author
	I want to create a page in the CMS
	So that I can grow my website

	@javascript
	Scenario: I can create a page from the pages section
		Given I am logged in with "ADMIN" permissions
		And I go to "/admin/pages"
		And I should see a "Add new" button in CMS Content Toolbar
		When I press the "Add new" button
		And I select the "Page" radio button
		And I press the "Create" button
		Then I should see an edit page form
