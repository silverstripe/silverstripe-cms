Feature: Page creation in the CMS
	As a content author
	I want to create a basic text page at the root level and save it
	So that our website can be kept up to date
	
	Scenario: An initial change to page name modifies key fields
		Given I log into the CMS as admin
		And I create a new page
		When I put "Change Name" in the "Page name" field
		And I click on the "Metadata" tab
		Then the "URLSegment" field is "change-name"
		And the "MetaTitle" field is "Change Name"
		When I click on the "Main" tab
		Then the "Navigation label" field is "Change Name"
		
	Scenario: Every subsequent change does not change the key fields
		Given I save the page
		And I cancel pop-ups
		When I put "Change Again" in the "Page name" field
		And I click on the "Metadata" tab
		Then the "URLSegment" field is "change-name"
		And the "MetaTitle" field is "Change Name"
		When I click on the "Main" tab
		Then the "Navigation label" field is "Change Name"
		Then I delete the current page
		
	Scenario: I can populate all fields
		And I create a new page	
		And I set "Page name" to "Populate name"
		And I set "Navigation label" to "Populate label"
		And I click on the "Metadata" tab
		And I set "URLSegment" to "populate-url-segment"
		And I set "MetaTitle" to "Populate MetaTitle"
		And I set "Description" to "Populate Description"
		And I set "Keywords" to "Populate Keywords"
		And I set "Custom Meta Tags" to "Populate Custom Meta Tags"
		And I click on the "Main" tab
		And I save the page
		When I load the "Populate label" page
		Then the "Page name" field is "Populate name"
		And the "Navigation label" field is "Populate label"
		When I click on the "Metadata" tab
		Then the "URLSegment" field is "populate-url-segment"
		And the "MetaTitle" field is "Populate MetaTitle"
		And the "Description" field is "Populate Description"
		And the "Custom Meta Tags" field is "Populate Custom Meta Tags"
		And I click on the "Main" tab
		Then I delete the current page

	Scenario: I can create 2 identical pages
		When I create a new page
		And I create a new page
		Then there are 2 root pages with navigation label "New Page"
		Then I delete the current page
		And I load the "New Page" root-level page
		Then I delete the current page

	Scenario: Each change to page name changes the URL
		When I create a new page
		And I set "Page name" to "First Change"
		And I click on the "Metadata" tab
		Then the "URLSegment" field is "first-change"

		When I confirm pop-ups
		And I click on the "Main" tab
		And I set "Page name" to "Second Change"
		And I click on the "Metadata" tab
		Then the "URLSegment" field is "second-change"

		When I cancel pop-ups
		And I click on the "Main" tab
		And I set "Page name" to "Third Change"
		And I click on the "Metadata" tab
		Then the "URLSegment" field is "second-change"
		And I click on the "Main" tab

		Then I delete the current page

	Scenario: Changes aren't saved if I cancel the warning
		Given I create a new page
		And I set "Page name" to "Change name"
		When I confirm pop-ups to ignore the warning that their is unsaved content	
		And I load the "New Page" page
		Then the "Page name" field is "New Page"

		Then I delete the current page
		
	Scenario: Page name and navigation label default to new page
		Given I create a new page
		Then the "Page name" field is "New Page"
		And the "Navigation label" field is "New Page"

		When I click on the "Metadata" tab
		Then the "URLSegment" field is "new-page"
		And the "MetaTitle" field is blank
		And the "Description" field is blank
		And the "Keywords" field is blank
		And the "Custom Meta Tags" field is blank
		And I click on the "Main" tab

 		Then I delete the current page

	Scenario: The navigation label is displayed in the site tree
		Given I create a new page
		And I set "Navigation label" to "New Label"
		And I save the page
		When I load the "New Label" page
		Then the "Navigation label" field is "New Label"

	Scenario: If the navigation label is blanked out, it takes the value in the Page Name field
		Given I set "Page name" to "Page Title"
		When I set "Navigation label" to ""
		And I save the page
		And I load the "Page Title" page
		Then the "Navigation label" field is "Page Title"
		Then I delete the current page
		