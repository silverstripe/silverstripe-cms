#  Steps definitions for security

# Fixture instantiation
Given /a "(.*)" group/ do |group|
  Given 'I visit admin/security'
  And 'I click the "Security Groups" link'
  And 'I click the "Create" button'
  And "I put \"#{group}\" in the \"Title\" field"
  And 'I click the "Save" button'
end

Given /a user called "(.*)" in the "(.*)" group/ do |user, group|
  Given 'I visit admin/security'
  And "I click the \"#{group} (global group)\" link"
  And "I put \"#{user}\" in the \"FirstName\" field"
  And "I put \"#{user}\" in the \"Email\" field"
  And "I put \"password\" in the \"SetPassword\" field"
  And "I click the \"Add\" button"
end

Given /^I get a permission denied error$/ do
  pending
end
