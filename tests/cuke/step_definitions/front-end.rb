##
## Step definitions for testing the front-end site
##


Given /I go to the draft site/ do
  pending
  Given 'I click the "viewStageSite" link'
#  |''element''|//a[@id="viewStageSite"]|''exists''|
#  |''checking timeout''|@{fast_checking_timeout}|
#  |''optionally''|''element''|//a[@id="viewStageSite"][@style=""]|''exists''|
# |''checking timeout''|@{checking_timeout}|
#  |''click''|viewStageSite|
end

Given /I close window and go back to clean state/ do
#  |''close''|
#  |''select initial window''|
#  |default frame|
#  |''go to root node''|
end  
