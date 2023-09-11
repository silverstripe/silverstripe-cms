@retry
Feature: Insert links into a page
As a cms author
I want to insert a link into my content
So that I can link to a external website or a page on my site

  Background:
    Given I add an extension "SilverStripe\CMS\Tests\Behaviour\AdditionalAnchorPageExtension" to the "Page" class
      And a "page" "Home"
      And a "page" "About Us" has the "Content" "<p>My awesome content</p>"
      And a "page" "Details" has the "Content" "<p>My sub-par content<a name="youranchor"></a></p>"
      And the "group" "EDITOR" has permissions "Access to 'Pages' section"
      And I am logged in as a member of "EDITOR" group
      And I go to "/admin/pages"
      And I click on "About Us" in the tree

  Scenario: I can link to an anchor in an internal page
    When I select "awesome" in the "Content" HTML field
      And I press the "Insert link" HTML field button
      And I click "Anchor on a page" in the ".tox-collection__group" element
    Then I should see an "form#Form_editorAnchorLink" element
      And I should see "About Us" in the "#Form_editorAnchorLink_PageID_Holder .treedropdownfield__value-container" element
    When I select "Details" in the "#Form_editorAnchorLink_PageID_Holder" tree dropdown
      And I select "youranchor" in the "#Form_editorAnchorLink_Anchor_Holder" anchor dropdown
    Then I should see "youranchor" in the "#Form_editorAnchorLink_Anchor_Holder .anchorselectorfield__value-container" element
    When I fill in "my desc" for "Link description"
      And I press the "Insert link" button
    Then the "Content" HTML field should contain "<a title="my desc" href="[sitetree_link,id=3]#youranchor">awesome</a>"
    # Required to avoid "unsaved changes" browser dialog
    Then I press the "Save" button

  Scenario: I can wrap an image in a link to an anchor in an internal page
    Given I fill in the "Content" HTML field with "<p><img src='file1.jpg'></p>"
    When I select the image "file1.jpg" in the "Content" HTML field
      And I press the "Insert link" HTML field button
      And I click "Anchor on a page" in the ".tox-collection__group" element
    Then I should see an "form#Form_editorAnchorLink" element
      And I should not see "Link text"
      And I should see "About Us" in the "#Form_editorAnchorLink_PageID_Holder .treedropdownfield__value-container" element
    When I select "Details" in the "#Form_editorAnchorLink_PageID_Holder" tree dropdown
      And I select "youranchor" in the "#Form_editorAnchorLink_Anchor_Holder" anchor dropdown
      And I press the "Insert link" button
    Then the "Content" HTML field should contain "<a href="[sitetree_link,id=3]#youranchor"><img src="file1.jpg"></a>"
      # Required to avoid "unsaved changed" browser dialog
      And I press the "Save" button

  Scenario: I can link to an anchor from a dataobject on the current page
    When I select "awesome" in the "Content" HTML field
      And I press the "Insert link" HTML field button
      And I click "Anchor on a page" in the ".tox-collection__group" element
    Then I should see an "form#Form_editorAnchorLink" element
      And I should see "About Us" in the "#Form_editorAnchorLink_PageID_Holder .treedropdownfield__value-container" element
    When I select "dataobject-anchor" in the "#Form_editorAnchorLink_Anchor_Holder" anchor dropdown
    Then I should see "dataobject-anchor" in the "#Form_editorAnchorLink_Anchor_Holder .anchorselectorfield__value-container" element
    When I fill in "my desc" for "Link description"
      And I press the "Insert link" button
    Then the "Content" HTML field should contain "<a title="my desc" href="[sitetree_link,id=2]#dataobject-anchor">awesome</a>"
    # Required to avoid "unsaved changes" browser dialog
    Then I press the "Save" button

  Scenario: I can link to an unsaved anchor in the current page
    Given I fill in the "Content" HTML field with "<p>My awesome content</p><p><a id='unsaved-anchor'></a>unsaved content</p>"
    When I select "awesome" in the "Content" HTML field
      And I press the "Insert link" HTML field button
      And I click "Anchor on a page" in the ".tox-collection__group" element
    Then I should see an "form#Form_editorAnchorLink" element
      And I should see "About Us" in the "#Form_editorAnchorLink_PageID_Holder .treedropdownfield__value-container" element
    When I click on the ".anchorselectorfield__dropdown-indicator" element
      Then I should see "dataobject-anchor" in the ".anchorselectorfield__menu-list" element
    When I select "unsaved-anchor" in the "#Form_editorAnchorLink_Anchor_Holder" anchor dropdown
    Then I should see "unsaved-anchor" in the "#Form_editorAnchorLink_Anchor_Holder .anchorselectorfield__value-container" element
    When I fill in "my desc" for "Link description"
      And I press the "Insert link" button
    Then the "Content" HTML field should contain "<a title="my desc" href="[sitetree_link,id=2]#unsaved-anchor">awesome</a>"
    # Required to avoid "unsaved changes" browser dialog
    Then I press the "Save" button
