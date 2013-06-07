Feature: Access the User Help
	As a CMS user 
	I want to access the user help in the CMS
	So that I can get help about the CMS features

  @javascript
  Scenario: I can get to http://3.0.userhelp.silverstripe.org from anywhere in the CMS
	Given I am logged in with "ADMIN" permissions
	And I go to "/admin/pages"
    Then I should see "Help" in the "#Menu-Help" element
    
    Given I go to "/admin/settings"
    Then I should see "Help" in the "#Menu-Help" element

    Given I go to "http://3.0.userhelp.silverstripe.org"
    Then I should see "SilverStripe User Help!"

    #Given I click "Help" in the "#Menu-Help" element
    #Then the url should match "http://3.0.userhelp.silverstripe.org"