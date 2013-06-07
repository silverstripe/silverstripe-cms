Feature: Search for a page
	As an author
	I want to search for a page in the CMS
	So that I can efficiently navigate nested content structures

	Background:
		Given a "page" "About Us"
		And a "page" "Contact Us"

	@javascript
	Scenario: I can search for a page by its title
		Given I am logged in with "ADMIN" permissions
		And I go to "/admin/pages"
		Then I should see "About Us" in CMS Tree
		And I should see "Contact Us" in CMS Tree

		When I expand the "Filter" CMS Panel
		And I fill in "Content" with "About Us"
		And I press the "Apply Filter" button
		Then I should see "About Us" in CMS Tree
		But I should not see "Contact Us" in CMS Tree
