##
## General rules for the SilverStripe CMS as a whole.  They mostly have to do with the LeftAndMain
## interface

# Match general CMS tabs, ModelAdmin needs another system
Given /I click on the "([^\"]*)" tab/ do |tab|
	found = nil
	links = @salad.browser.links()
	links.each {|link|
		if /^tab-.+/.match(link.id) then
			if link.innerText == tab or /^tab-.*#{tab}(_set)?/.match(link.id) then
				found = link
				break
			end
		end
	}
	if found then
		Given "I click the \"#{found.id}\" link"
	else
		fail("Could not find the \"#{tab}\" tab")
	end
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


