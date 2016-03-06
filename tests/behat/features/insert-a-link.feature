@assets 
Feature: Insert links into a page
As a cms author
I want to insert a link into my content
So that I can link to a external website or a page on my site

  Background:
    Given a "page" "Home"
    And a "page" "About Us" has the "Content" "My awesome content"
    And a "page" "Details" has the "Content" "My sub-par content<a name="youranchor"></a>"
    And a "file" "assets/file1.jpg"
    And I am logged in with "ADMIN" permissions
    And I go to "/admin/pages"
    And I click on "About Us" in the tree

  Scenario: I can link to an internal page
    Given I select "awesome" in the "Content" HTML field
    And I press the "Insert Link" button
    When I select the "Page on the site" radio button
    And I fill in the "internal" dropdown with "Home"
    And I fill in "my desc" for "Link description"
    And I press the "Insert" button
    # TODO Dynamic DB identifiers
    Then the "Content" HTML field should contain "<a title="my desc" href="[sitetree_link,id=1]">awesome</a>"
    # Required to avoid "unsaved changes" browser dialog
    Then I press the "Save draft" button

  Scenario: I can link to an anchor in an internal page
    Given I select "awesome" in the "Content" HTML field
    And I press the "Insert Link" button
    And I select the "Page on the site" radio button
    And I fill in the "internal" dropdown with "Details"
    And I wait for 1 second
    And I select "youranchor" from "Form_EditorToolbarLinkForm_AnchorSelector"
    And I press the "Insert link" button
    Then the "Content" HTML field should contain "<a href="[sitetree_link,id=3]#youranchor">awesome</a>"
    # Required to avoid "unsaved changes" browser dialog
    Then I press the "Save draft" button

  Scenario: I can link to an external URL
    Given I select "awesome" in the "Content" HTML field
    And I press the "Insert Link" button
    When I select the "Another website" radio button
    And I fill in "http://silverstripe.org" for "URL"
    And I check "Open link in a new window"
    And I press the "Insert" button
    Then the "Content" HTML field should contain "<a href="http://silverstripe.org" target="_blank">awesome</a>"
    # Required to avoid "unsaved changes" browser dialog
    Then I press the "Save draft" button

  Scenario: I can link to a file
    Given I select "awesome" in the "Content" HTML field
    When I press the "Insert Link" button
    When I select the "Download a file" radio button
    And I attach the file "testfile.jpg" to "file[Uploads][]" with HTML5
    And I press the "Insert link" button
    Then the "Content" HTML field should contain "<a href="[file_link,id=3]">awesome</a>"
    # Required to avoid "unsaved changes" browser dialog
    Then I press the "Save draft" button
    # Check that the field is reset when adding another new link
    Given I select "content" in the "Content" HTML field
    When I press the "Insert Link" button
    Then I should not see a ".ss-uploadfield-files .ss-uploadfield-item" element

  Scenario: I can link to an anchor
    Given I fill in the "Content" HTML field with "<p>My awesome content<a name='myanchor'></a></p>"
    And I select "awesome" in the "Content" HTML field
    When I press the "Insert Link" button
    When I select the "Anchor on this page" radio button
    And I select "myanchor" from "Form_EditorToolbarLinkForm_AnchorSelector"
    And I press the "Insert link" button
    Then the "Content" HTML field should contain "<a href="#myanchor">awesome</a>"
    # Required to avoid "unsaved changes" browser dialog
    Then I press the "Save draft" button

  Scenario: I can edit a link
    Given I fill in the "Content" HTML field with "<p>My <a href='http://silverstripe.org'>awesome</a> content"
    And I select "awesome" in the "Content" HTML field
    When I press the "Insert Link" button
    # We need to hard-code the <input> id attribute, if you say 'Then the URL field', it picks up URLSegment instead.
    Then the "Form_EditorToolbarLinkForm_external" field should contain "http://silverstripe.org"
    # This doesn't seem to suffer from that issue
    When I fill in "http://google.com" for "URL"
    And I press the "Insert link" button
    Then the "Content" HTML field should contain "<a href="http://google.com">awesome</a>"
    # Required to avoid "unsaved changes" browser dialog
    Then I press the "Save draft" button

  Scenario: I can remove a link
    Given I fill in the "Content" HTML field with "My <a href='http://silverstripe.org'>awesome</a> content"
    And I select "awesome" in the "Content" HTML field
    When I press the "Unlink" button
    Then the "Content" HTML field should contain "My awesome content"
    And the "Content" HTML field should not contain "http://silverstripe.org"
    # Required to avoid "unsaved changes" browser dialog
    Then I press the "Save draft" button
