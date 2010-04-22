
# Log in
Given /log in as (.*)$/ do |user|
	Given "I fill out the log in form with user \"#{user}\" and password \"password\""
	And 'I see "You\'re logged in as"'
end

Given /log into the CMS as (.*)/ do |user|
  Given "I log in as #{user}"
  And  "I visit admin/"
  And "I load the root node"
end

Given /log out$/ do
  Given "I visit Security/logout"
end

Given /fill out the log(?:\s*)in form with user "(.*)" and password "(.*)"/ do |user, password|
	Given 'I visit Security/logout'
	And 'I visit Security/login?BackURL=Security/login'
	And "I put \"#{user}\" in the \"Email\" field"
	And "I put \"#{password}\" in the \"Password\" field"
	And "I click the \"Log in\" button"
end
  