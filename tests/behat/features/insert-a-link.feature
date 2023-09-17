@assets @retry
Feature: Insert links into a page
As a cms author
I want to insert a link into my content
So that I can link to a external website or a page on my site

  Background:
    Given a "page" "Home"
      And a "page" "About Us" has the "Content" "<p>My awesome content</p>"
      And a "file" "file1.jpg"
      # And the "group" "EDITOR" has permissions "Access to 'Pages' section"
      And the "group" "EDITOR" has permissions "Access to 'Files' section" and "Access to 'Pages' section" and "FILE_EDIT_ALL"
      And I am logged in as a member of "EDITOR" group
      And I go to "/admin/pages"
      And I click on "About Us" in the tree

  Scenario: I can link to an internal page
    When I select "awesome" in the "Content" HTML field
      And I press the "Insert link" HTML field button
      And I click "Page on this site" in the ".tox-collection__group" element
    Then I should see an "form#Form_editorInternalLink" element
    When I select "About Us" in the "#Form_editorInternalLink_PageID_Holder" tree dropdown
      And I fill in "my desc" for "Link description"
      And I press the "Insert link" button
    Then the "Content" HTML field should contain "<a title="my desc" href="[sitetree_link,id=2]">awesome</a>"
    # Required to avoid "unsaved changes" browser dialog
    Then I press the "Save" button

  Scenario: I can wrap an image in a link to an internal page
    Given I fill in the "Content" HTML field with "<p><img src='file1.jpg'></p>"
    When I select the image "file1.jpg" in the "Content" HTML field
      And I press the "Insert link" HTML field button
      And I click "Page on this site" in the ".tox-collection__group" element
    Then I should see an "form#Form_editorInternalLink" element
      And I should not see "Link text"
    When I select "About Us" in the "#Form_editorInternalLink_PageID_Holder" tree dropdown
      And I press the "Insert link" button
    Then the "Content" HTML field should contain "<a href="[sitetree_link,id=2]"><img src="file1.jpg"></a>"
      # Required to avoid "unsaved changed" browser dialog
      And I press the "Save" button

  Scenario: I can edit a link to an internal page
    Given I fill in the "Content" HTML field with "<a title='my desc' href='[sitetree_link,id=2]'>awesome</a>"
      And I select "awesome" in the "Content" HTML field
      And I press the "Insert link" HTML field button
      And I click "Page on this site" in the ".tox-collection__group" element
      And I should see an "form#Form_editorInternalLink" element
    Then I should see "About Us" in the "#Form_editorInternalLink_PageID_Holder .treedropdownfield__value-container" element
      And the "Link description" field should contain "my desc"
    # This doesn't seem to suffer from that issue
    When I select "Home" in the "#Form_editorInternalLink_PageID_Holder" tree dropdown
      And I fill in "my new desc" for "Link description"
      And I press the "Insert link" button
    Then the "Content" HTML field should contain "<a title="my new desc" href="[sitetree_link,id=1]">awesome</a>"
    # Required to avoid "unsaved changes" browser dialog
    Then I press the "Save" button

  Scenario: I can link to an external URL
    Given I select "awesome" in the "Content" HTML field
      And I press the "Insert link" HTML field button
    When I click "Link to external URL" in the ".tox-collection__group" element
      And I should see an "form#Form_ModalsEditorExternalLink" element
    When I fill in "http://silverstripe.org" for "URL"
      And I check "Open in new window/tab"
      And I press the "Insert link" button
    Then the "Content" HTML field should contain "<a rel="noopener" href="http://silverstripe.org" target="_blank">awesome</a>"
    # Required to avoid "unsaved changes" browser dialog
    Then I press the "Save" button

  Scenario: I can wrap an image in a link to an external URL
    Given I fill in the "Content" HTML field with "<p><img src='file1.jpg'></p>"
    When I select the image "file1.jpg" in the "Content" HTML field
      And I press the "Insert link" HTML field button
    When I click "Link to external URL" in the ".tox-collection__group" element
      And I should see an "form#Form_ModalsEditorExternalLink" element
      And I should not see "Link text"
    When I fill in "http://silverstripe.org" for "URL"
      And I press the "Insert link" button
    Then the "Content" HTML field should contain "<a href="http://silverstripe.org"><img src="file1.jpg"></a>"
      # Required to avoid "unsaved changed" browser dialog
      And I press the "Save" button

  Scenario: I can edit an external link
    Given I fill in the "Content" HTML field with "<p>My <a href='http://silverstripe.org'>awesome</a> content"
      And I select "awesome" in the "Content" HTML field
    When I press the "Insert link" HTML field button
      And I click "Link to external URL" in the ".tox-collection__group" element
      And I should see an "form#Form_ModalsEditorExternalLink" element
    Then the "URL" field should contain "http://silverstripe.org"
    # This doesn't seem to suffer from that issue
    When I fill in "http://google.com" for "URL"
    And I press the "Insert link" button
    Then the "Content" HTML field should contain "<a href="http://google.com">awesome</a>"
    # Required to avoid "unsaved changes" browser dialog
    Then I press the "Save" button

  Scenario: I can remove an external link
    Given I fill in the "Content" HTML field with "My <a href='http://silverstripe.org'>awesome</a> content"
      And I select "awesome" in the "Content" HTML field
    When I press the "Remove link" button
    Then the "Content" HTML field should contain "My awesome content"
      And the "Content" HTML field should not contain "http://silverstripe.org"
    # Required to avoid "unsaved changes" browser dialog
    Then I press the "Save" button
