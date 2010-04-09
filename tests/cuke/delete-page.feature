Feature: Page deletion in the CMS
	As a content author
	I want to delete pages in the CMS
	So that out of date content can be removed
			
	Scenario: User can delete a page without making any changes
		Given I log into the CMS as admin
		And there are 0 root pages with navigation label "delete-page.scenario1"
		And I create a new page called "delete-page.scenario1"
		And there are 1 root pages with navigation label "delete-page.scenario1"
		When I delete the current page
		Then there are 0 root pages with navigation label "delete-page.scenario1"

	Scenario: A deleted page can't be viewed
		And there are 0 root pages with navigation label "delete-page.scenario2"
		Given I create a new page called "delete-page.scenario2"
		And there is 1 root page with navigation label "delete-page.scenario2"
		When I delete the current page
		And there are 0 root pages with navigation label "delete-page.scenario2"
		And I log out
		Then url delete-page-scenario2 does not exist

	Scenario: A deleted URL can be re-used
		Given I log into the CMS as admin
		And there are 0 root pages with navigation label "delete-page.scenario3"
		And I create a new page called "delete-page.scenario3"
		And there are 1 root pages with navigation label "delete-page.scenario3"
		And I click on the "Metadata" tab
		And the "URLSegment" field is "delete-page-scenario3"
		And I delete the current page
		And there are 0 root pages with navigation label "delete-page.scenario3"
		When I create a new page called "delete-page.scenario3"
		And I click on the "Metadata" tab
		Then the "URLSegment" field is "delete-page-scenario3"
		Then delete the current page
		
	Scenario: A deleted page doesn't appear after re-login
		Given there are 0 root pages with navigation label "delete-page.scenario4"
		And I create a new page called "delete-page.scenario4"
		And there is 1 root page with navigation label "delete-page.scenario4"
		And I save the page
		And I delete the current page
		And there are 0 root pages with navigation label "delete-page.scenario4"
		When I log out
		And I log into the CMS as admin
		Then there are 0 root pages with navigation label "delete-page.scenario4"
	
