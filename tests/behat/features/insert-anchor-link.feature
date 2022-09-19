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
      And I am logged in with "ADMIN" permissions
      And I go to "/admin/pages"
      And I click on "About Us" in the tree

  Scenario: I can link to an anchor in an internal page
    When I select "awesome" in the "Content" HTML field
      And I press the "Insert link" HTML field button
      And I click "Anchor on a page" in the ".tox-collection__group" element
    Then I should see an "form#Form_editorAnchorLink" element
      And I should see "About Us" in the "#Form_editorAnchorLink_PageID_Holder .Select-multi-value-wrapper" element
    When I click "About Us" in the "#Form_editorAnchorLink_PageID_Holder .Select-multi-value-wrapper" element
      And I click "Details" in the "#Form_editorAnchorLink_PageID_Holder .Select-menu-outer" element
      And I click "Select or enter anchor" in the "#Form_editorAnchorLink_Anchor_Holder .Select-multi-value-wrapper" element
      And I click "youranchor" in the "#Form_editorAnchorLink_Anchor_Holder .Select-menu-outer" element
    Then I should see "youranchor" in the "#Form_editorAnchorLink_Anchor_Holder .Select-value" element
    When I fill in "my desc" for "Link description"
      And I press the "Insert link" button
    Then the "Content" HTML field should contain "<a title="my desc" href="[sitetree_link,id=3]#youranchor">awesome</a>"
    # Required to avoid "unsaved changes" browser dialog
    Then I press the "Save" button

  Scenario: I can link to an anchor from a dataobject on the current page
    When I select "awesome" in the "Content" HTML field
      And I press the "Insert link" HTML field button
      And I click "Anchor on a page" in the ".tox-collection__group" element
    Then I should see an "form#Form_editorAnchorLink" element
      And I should see "About Us" in the "#Form_editorAnchorLink_PageID_Holder .Select-multi-value-wrapper" element
    When I click "Select or enter anchor" in the "#Form_editorAnchorLink_Anchor_Holder .Select-multi-value-wrapper" element
    Then I should see "dataobject-anchor" in the "#Form_editorAnchorLink_Anchor_Holder .Select-menu-outer" element
    When I click "dataobject-anchor" in the "#Form_editorAnchorLink_Anchor_Holder .Select-menu-outer" element
    Then I should see "dataobject-anchor" in the "#Form_editorAnchorLink_Anchor_Holder .Select-value" element
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
      And I should see "About Us" in the "#Form_editorAnchorLink_PageID_Holder .Select-multi-value-wrapper" element
    When I click "Select or enter anchor" in the "#Form_editorAnchorLink_Anchor_Holder .Select-multi-value-wrapper" element
    Then I should see "unsaved-anchor" in the "#Form_editorAnchorLink_Anchor_Holder .Select-menu-outer" element
      And I should see "dataobject-anchor" in the "#Form_editorAnchorLink_Anchor_Holder .Select-menu-outer" element
    When I click "unsaved-anchor" in the "#Form_editorAnchorLink_Anchor_Holder .Select-menu-outer" element
    Then I should see "unsaved-anchor" in the "#Form_editorAnchorLink_Anchor_Holder .Select-value" element
    When I fill in "my desc" for "Link description"
      And I press the "Insert link" button
    Then the "Content" HTML field should contain "<a title="my desc" href="[sitetree_link,id=2]#unsaved-anchor">awesome</a>"
    # Required to avoid "unsaved changes" browser dialog
    Then I press the "Save" button
