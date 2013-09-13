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
		And I expand the "Filter" CMS Panel

	Scenario: I can search for a page by its title
		Given I fill in "Content" with "About Us"
		And I press the "Apply Filter" button
		Then I should see "About Us" in the tree
		But I should not see "Contact Us" in the tree

	@todo
	Scenario: I can search for a page by its type
		Given a "page" "My Error Page" of type "Error Page"
		And I fill in "Page Type" with "Redirector Page"
		And I press the "Apply Filter" button
		Then I should see "My Error Page" in the tree
		But I should not see "Contact Us" in the tree

	@todo
	Scenario: I can search for a page by its oldest last edited date
		Given a "page" "Recent Page"
		And a "page" "Old Page" was last edited 7 days ago
		When I fill in "From" with "5 days ago"
		And I press the "Apply Filter" button
		Then I should see "Recent Page" in the tree
		But I should not see "Old Page" in the tree

	@todo
	Scenario: I can search for a page by its newest last edited date
		Given a "page" "Recent Page"
		And a "page" "Old Page" was last edited 7 days ago
		When I fill in "To" with "5 days ago"
		And I press the "Apply Filter" button
		Then I should not see "Recent Page" in the tree
		But I should see "Old Page" in the tree

	@todo
	Scenario: I can include deleted pages in my search
		Given a "page" "Deleted Page"
		And the "page" "Old Page" is deleted
		When I press the "Apply Filter" button
		Then I should not see "Deleted Page" in the tree
		When I fill in "Pages" with "All pages, including deleted"
		And I press the "Apply Filter" button
		Then I should see "Deleted Page" in the tree