@assets @retry
Feature: Insert links into a page
As a cms author
I want to insert a link into my content
So that I can link to a external website or a page on my site

  Background:
    Given a "page" "Home"
      And a "page" "About Us" has the "Content" "<p>My awesome content</p>"
      And a "page" "Details" has the "Content" "<p>My sub-par content<a name="youranchor"></a></p>"
      And a "file" "file1.jpg"
      And I am logged in with "ADMIN" permissions
      And I go to "/admin/pages"
      And I click on "About Us" in the tree

  Scenario: I can link to an internal page
    When I select "awesome" in the "Content" HTML field
      And I press the "Insert Link" HTML field button
      And I click "Page on this site" in the ".mce-menu" element
    Then I should see an "form#Form_editorInternalLink" element
    When I click "(Choose Page)" in the ".Select-multi-value-wrapper" element
      And I click "About Us" in the ".treedropdownfield__menu" element
      And I fill in "my desc" for "Link description"
      And I press the "Insert" button
    Then the "Content" HTML field should contain "<a title="my desc" href="[sitetree_link,id=2]">awesome</a>"
    # Required to avoid "unsaved changes" browser dialog
    Then I press the "Save draft" button

  Scenario: I can link to an anchor in an internal page
    When I select "awesome" in the "Content" HTML field
      And I press the "Insert Link" HTML field button
      And I click "Anchor on a page" in the ".mce-menu" element
    Then I should see an "form#Form_editorAnchorLink" element
      And I should see "About Us" in the "#Form_editorAnchorLink_PageID_Holder .Select-multi-value-wrapper" element
    When I click "About Us" in the "#Form_editorAnchorLink_PageID_Holder .Select-multi-value-wrapper" element
      And I click "Details" in the "#Form_editorAnchorLink_PageID_Holder .Select-menu-outer" element
      And I click "Select or enter anchor" in the "#Form_editorAnchorLink_Anchor_Holder .Select-multi-value-wrapper" element
      And I click "youranchor" in the "#Form_editorAnchorLink_Anchor_Holder .Select-menu-outer" element
    Then I should see "youranchor" in the "#Form_editorAnchorLink_Anchor_Holder .Select-value" element
    When I fill in "my desc" for "Link description"
      And I press the "Insert" button
    Then the "Content" HTML field should contain "<a title="my desc" href="[sitetree_link,id=3]#youranchor">awesome</a>"
    # Required to avoid "unsaved changes" browser dialog
    Then I press the "Save draft" button

  Scenario: I can edit a link to an internal page
    Given I fill in the "Content" HTML field with "<a title='my desc' href='[sitetree_link,id=2]'>awesome</a>"
      And I select "awesome" in the "Content" HTML field
      And I press the "Insert Link" HTML field button
      And I click "Page on this site" in the ".mce-menu" element
      And I should see an "form#Form_editorInternalLink" element
    Then I should see "About Us" in the ".Select-value" element
      And the "Link description" field should contain "my desc"
    # This doesn't seem to suffer from that issue
    When I click "About Us" in the ".Select-value" element
      And I click "Home" in the ".treedropdownfield__menu" element
      And I fill in "my new desc" for "Link description"
      And I press the "Insert" button
    Then the "Content" HTML field should contain "<a title="my new desc" href="[sitetree_link,id=1]">awesome</a>"
    # Required to avoid "unsaved changes" browser dialog
    Then I press the "Save draft" button

  Scenario: I can link to an external URL
    Given I select "awesome" in the "Content" HTML field
      And I press the "Insert Link" HTML field button
    When I click "Link to external URL" in the ".mce-menu" element
      And I should see an "form#Form_ModalsEditorExternalLink" element
    When I fill in "http://silverstripe.org" for "URL"
      And I check "Open in new window/tab"
      And I press the "Insert" button
    Then the "Content" HTML field should contain "<a rel="noopener" href="http://silverstripe.org" target="_blank">awesome</a>"
    # Required to avoid "unsaved changes" browser dialog
    Then I press the "Save draft" button

  Scenario: I can edit a link
    Given I fill in the "Content" HTML field with "<p>My <a href='http://silverstripe.org'>awesome</a> content"
      And I select "awesome" in the "Content" HTML field
    When I press the "Insert Link" HTML field button
      And I click "Link to external URL" in the ".mce-menu" element
      And I should see an "form#Form_ModalsEditorExternalLink" element
    Then the "URL" field should contain "http://silverstripe.org"
    # This doesn't seem to suffer from that issue
    When I fill in "http://google.com" for "URL"
    And I press the "Insert" button
    Then the "Content" HTML field should contain "<a href="http://google.com">awesome</a>"
    # Required to avoid "unsaved changes" browser dialog
    Then I press the "Save draft" button

  Scenario: I can remove a link
    Given I fill in the "Content" HTML field with "My <a href='http://silverstripe.org'>awesome</a> content"
      And I select "awesome" in the "Content" HTML field
    When I press the "Remove link" button
    Then the "Content" HTML field should contain "My awesome content"
      And the "Content" HTML field should not contain "http://silverstripe.org"
    # Required to avoid "unsaved changes" browser dialog
    Then I press the "Save draft" button
