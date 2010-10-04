Feature: Log in
	As a CMS user
	I want to security log into the CMS
	So that I can make changes, knowing that other people can't.

	Background:
		Given I visit Security/logout
	
	Scenario: opening admin asks user log-in
		And I visit admin
		Then I am sent to Security/login

	Scenario: valid login
		When I fill out the login form with user "admin" and password "password"
		Then I see "You're logged in as"

	Scenario: no password login
		When I fill out the log in form with user "admin" and password ""
		Then I see "That doesn't seem to be the right e-mail address or password."

	Scenario: no user login
		When I fill out the log in form with user "" and password "password"
		Then I see "That doesn't seem to be the right e-mail address or password."

	Scenario: invalid login, getting right 2nd time
		Given I visit admin
		And I put "admin" in the "Email" field
		And I put "wrongpassword" in the "Password" field
		And I click the "Log in" button
		Then I am sent to Security/login 
		And I see "That doesn't seem to be the right e-mail address or password."
		Given I put "admin" in the "Email" field
		And I put "password" in the "Password" field
		And I click the "Log in" button
		Then I am sent to admin

	Scenario: Re-login
		Given I visit Security/logout
		Then I log into the CMS as admin
