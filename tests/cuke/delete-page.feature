Feature: Page deletion in the CMS
	As a content author
	I want to delete pages in the CMS
	So that out of date content can be removed
	
	Scenario: User can delete a page without making any changes
		Given I log into the CMS as admin
		And I create a new page
		And I save the page
		When I delete the current page
		Then there are 0 root pages with navigation label "New Page"
		
	Scenario: A deleted page can't be viewed
		Given I create a new page
		And I save the page
		When I delete the current page
		And I log out
		Then url new-page does not exist
		
	Scenario: A deleted URL can be re-used
		Given I log into the CMS as admin
		And I create a new page
		And I click on the "Metadata" tab
		And the "URLSegment" field is "new-page"
		And I save the page
		And I delete the current page
		When I create a new page
		And I click on the "Metadata" tab
		Then the "URLSegment" field is "new-page"
		
		Then delete the current page
		
	Scenario: A deleted page doesn't appear after re-login
		Given I create a new page
		And I save the page
		And I delete the current page
		When I log out
		And I log into the CMS as admin
		Then there are 0 root pages with navigation label "New Page"
	