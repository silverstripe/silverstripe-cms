## FIXTURE GENERATON

Given /^the site can be edited by the "([^\"]*)" group$/i do |arg1|
  pending
end

Given /^the "([^\"]*)" page can be edited by the "([^\"]*)" group$/i do |arg1, arg2|
  pending
end

Given /a "(.*)" page called "(.*)" as a child of "(.*)"/i do |type, title, parent|
    Given "I click the \"#{parent}\" link"
    And 'I wait 2s'
    And 'I click the "Create" button'
    And "I select \"#{type}\" from \"PageType\""
    And 'I click the "Go" button'
    And 'I click the "Create" button'
    And "I put \"#{title}\" in the \"Title\" field"
    And "I click \"Save\""
end

Given /^a top\-level "(.*)" page called "(.*)"$/i do |type,title|
  Given "I click the \"Site Content\" link"
  And "I create a new page "
  And "I click on the \"Main\" tab"
  And "I put \"#{title}\" in the \"Title\" field"
  And "I click \"Save\""
end

## ACTIONS

Given /load the "(.*)" page/i do |title|
  Given "I click the \"#{title}\" link"
end

Given /I load the "(.*)" root-level page/ do |nav|
  @browser.link(:xpath, "//ul[@id='sitetree']/li/ul/li//a[.='#{nav}']").click
end

Given /I load the root node/ do
  Given 'I click the "admin/show/root" link'
end

Given /create a new page$/i do
  Given "I create a new page using template \"Page\""
end

Given /create a new page called "(.*)"$/i do |title|
  Given "I create a new page using template \"Page\""
  And "I click on the \"Main\" tab"
  And "I put \"#{title}\" in the \"Page name\" field"
  And "I click the \"Save\" button"
end

Given /create a new page using template \"(.*)\"/i do |type|
  Given 'I load the root node (ajax)'
  And 'I click the "Create" button'
  And "I select \"#{type}\" from \"PageType\""
  And 'I click the "Go" button (ajax)'
  And 'I click the "Create" button'
end

Given /save the page$/i do
  Given 'I click the "Form_EditForm_action_save" button (ajax)'
end

Given /delete the current page$/i do
  Given 'I click the "Delete from the draft site" button'
end


## ASSERTIONS

Given /There (?:are|is) ([0-9]+) root pages? with navigation label "(.*)"/i do |count, nav|
  @browser.elements_by_xpath("//ul[@id='sitetree']/li/ul/li//a[.='#{nav}']").count.should == count.to_i
end

Given /The "(.*)" page does not exist/i do | page|
  @browser.link(:title, title).should empty?
  #|''get url''|@{root_url}PAGE|
  #|''title''|'''is not'''|PAGE|
end


## Current Page

Given /^The (.*) of the current page is "([^\"]*)"$/i do |arg1|
  pending
end

Then /^The current page is editable$/i do
  pending
end

Then /^The current page is read-only$/i do
  pending
end

Then /^The current page is at the top\-level$/i do
  pending
end
