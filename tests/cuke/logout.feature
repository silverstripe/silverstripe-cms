Feature: Log out
	As a CMS user
	I want to be able to log and be locked out of the CMS
	So that I can know other people can't edit my site
	
	Scenario: Log out from CMS
		Given I log into the CMS as admin
		And I click the "log out" link
		When I visit admin/
		Then I see "Enter your username and password to access the CMS."
		When I visit admin/assets/
		Then I see "Enter your username and password to access the CMS."
		When I visit admin/comments/
		Then I see "Enter your username and password to access the CMS."
		When I visit admin/reports/
		Then I see "Enter your username and password to access the CMS."
		When I visit admin/security/
		Then I see "Enter your username and password to access the CMS."
		When I visit admin/subsites/
		Then I see "Enter your username and password to access the CMS."