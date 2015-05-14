Feature: Publish a page
As a site owner
I want content to go to a draft site before being published
So that only high quality changes are seen by our visitors

	Background:
		Given a "page" "My Page" with "URLSegment"="my-page" and "Content"="initial content"
		And the "page" "My Page" is not published

	@javascript
	Scenario: I can have a unpublished version of a page that is not publicly available
		Given I go to "/my-page"
		Then the page can't be found

	@javascript
	Scenario: I can publish a previously never published page
		Given I am logged in with "ADMIN" permissions
		And I go to "/admin/pages"
		And I should see "My Page" in the tree
		And I click on "My Page" in the tree
		And I press the "Publish" button

		Then I press the "Log out" button
		And I go to "/my-page"

		Then I should see "initial content"

	@javascript
	Scenario: I will get different options depending on the current publish state of the page
		Given I am logged in with "ADMIN" permissions
		And I go to "/admin/pages"
		And I should see "My Page" in the tree
		And I click on "My Page" in the tree

		When I click "More options" in the "#ActionMenus" element
		Then I should not see "Unpublish" in the "#ActionMenus_MoreOptions" element
		And I should see "Not published" in the "#ActionMenus_MoreOptions" element
		And I should see "Archive" in the "#ActionMenus_MoreOptions" element
		And I should see a "Save & publish" button
		And I should see a "Saved" button

		When I fill in the "Content" HTML field with "my new content"
		And I click "More options" in the "#ActionMenus" element
		Then I should not see "Unpublish" in the "#ActionMenus_MoreOptions" element
		And I should see a "Save & publish" button
		And I should see a "Save draft" button

		When I press the "Publish" button
		And I click "More options" in the "#ActionMenus" element
		Then I should see "Unpublish" in the "#ActionMenus_MoreOptions" element
		And I should see "Archive" in the "#ActionMenus_MoreOptions" element
		And I should see a "Published" button
		And I should see a "Saved" button

	@javascript
	Scenario: I can unpublish a page
		Given a "page" "Hello" with "URLSegment"="hello" and "Content"="hello world"
		And I go to "/hello"
		Then I should see "hello world"

		Given I am logged in with "ADMIN" permissions
		And I go to "/admin/pages"
		And I should see "Hello" in the tree
		And I click on "Hello" in the tree

		When I click "More options" in the "#ActionMenus" element
		And I press the "Unpublish" button, confirming the dialog

		Then I press the "Log out" button
		And I go to "/hello"
		Then the page can't be found

	@javascript
	Scenario: I can delete a page from live and draft stage to completely remove it
		Given I am logged in with "ADMIN" permissions
		And I go to "/admin/pages"
		And I should see "My Page" in the tree
		And I click on "My Page" in the tree
		And I press the "Publish" button
		And I click "More options" in the "#ActionMenus" element
		Then I should see "Unpublish" in the "#ActionMenus_MoreOptions" element

		When I press the "Unpublish" button, confirming the dialog
		And I click "More options" in the "#ActionMenus" element
		Then I should see "Archive" in the "#ActionMenus_MoreOptions" element

		When I press the "Archive" button, confirming the dialog
		Then I should see a "Restore" button
		And I should not see a "Published" button
		And I should not see a "Save & publish" button
		And I should not see a "Saved" button
		And I should not see a "Save draft" button

		When I press the "Restore" button, confirming the dialog
		Then I should see a "Save & publish" button
