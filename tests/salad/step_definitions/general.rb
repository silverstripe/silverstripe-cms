##
## General rules for the SilverStripe CMS as a whole.  They mostly have to do with the LeftAndMain
## interface

Given /I click on the "([^\"]*)" tab/ do |tab|
  Given "I click the \"tab-Root_Content_set_#{tab}\" link"
end

Given /I wait for a status message/ do
  Watir::Waiter::wait_until {
    @browser.p(:id, 'statusMessage').exists? && @browser.p(:id, 'statusMessage').visible?
  }
end

Given /I wait for a success message/ do
  # We have to wait until after messages of the form 'Saving...', to get either a good message or
  # a bad message
  Watir::Waiter::wait_until {
    @browser.p(:id, 'statusMessage').exists? && @browser.p(:id, 'statusMessage').visible? && @browser.p(:id, 'statusMessage').class_name != ""
  }

  @browser.p(:id, 'statusMessage').class_name.should == 'good'
end


