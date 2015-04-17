Feature: Edit a page
	As an author
	I want to edit a page in the CMS
	So that I correct errors and provide new information

	Background:
		Given a "page" "About Us"
		Given I am logged in with "ADMIN" permissions
		And I go to "/admin/pages"
		Then I should see "About Us" in the tree

	@javascript
	Scenario: I can open a page for editing from the pages tree
		When I click on "About Us" in the tree
		Then I should see an edit page form

	@javascript
	Scenario: I can edit title and content and see the changes on draft
		When I click on "About Us" in the tree
		Then I should see an edit page form

		When I fill in "Title" with "About Us!"
		And I fill in the "Content" HTML field with "my new content"
		And I press the "Save draft" button
		Then I should see "Saved" in the "button#Form_EditForm_action_save" element

		When I click on "About Us" in the tree
		Then the "Title" field should contain "About Us!"
		And the "Content" HTML field should contain "my new content"