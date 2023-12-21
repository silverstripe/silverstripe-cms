Feature: Publish a page
  As a site owner
  I want see versioned badge on page with draft and modified versioned child objects

  Background:
    Given a "Test Page Versioned Object" "My Page" with "URLSegment"="my-page"
    And the "Test Page Versioned Object" "My Page" is not published
    And the "Versioned parent object" "My Versioned Parent Object" with "TestPageVersionedObject"="1"
    And the "Non Versioned parent object" "My Non Versioned Parent Object" with "TestPageVersionedObject"="1"
    And the "group" "EDITOR" has permissions "Access to 'Pages' section" and "TEST_DATAOBJECT_EDIT"
    And I am logged in as a member of "EDITOR" group

  Scenario: I should see versioned badge on page with draft and modified versioned child object
    Given I go to "/admin/pages"
      And I should see "My Page" in the tree
      And I click on "My Page" in the tree
    Then I should see "My Page" in the ".breadcrumbs-wrapper" element
      And I should see "Draft" in the ".breadcrumbs-wrapper" element
      And I click on the "#tab-Root_VersionedParentObjects" element
      And I should see "My Versioned Parent Object" in the "#Form_EditForm" element
      And I should see "Draft" in the "#Form_EditForm" element
    Then I click "My Versioned Parent Object" in the "#Form_EditForm_VersionedParentObjects" element
      And I should see "My Versioned Parent Object" in the ".breadcrumbs-wrapper" element
      And I should see "Draft" in the ".breadcrumbs-wrapper" element
      And I click "My Page" in the ".breadcrumbs-wrapper" element
      And I should see "Draft" in the ".breadcrumbs-wrapper" element
    Then I click "My Versioned Parent Object" in the "#Form_EditForm_VersionedParentObjects" element
      And I press the "Publish" button
      And I should not see "Draft" in the ".breadcrumbs-wrapper" element
      And I click "My Page" in the ".breadcrumbs-wrapper" element
      And I should see "Draft" in the ".breadcrumbs-wrapper" element
    Then I click "My Versioned Parent Object" in the "#Form_EditForm_VersionedParentObjects" element
      And I fill in "Name" with "My Versioned Parent Object with changes"
      And I press the "Save" button
    Then I should see "Modified" in the ".breadcrumbs-wrapper" element
      And I click "My Page" in the ".breadcrumbs-wrapper" element
      And I should see "Draft" in the ".breadcrumbs-wrapper" element
    Then I press the "Publish" button
      And I should not see "Modified" in the ".breadcrumbs-wrapper" element
      And I should not see "Modified" in the "#Form_EditForm" element

  Scenario: I should see versioned badge on page with draft and modified versioned grandchild object
    Given I go to "/admin/pages"
      And I should see "My Page" in the tree
      And I click on "My Page" in the tree
    Then I should see "My Page" in the ".breadcrumbs-wrapper" element
      And I should see "Draft" in the ".breadcrumbs-wrapper" element
      And I click on the "#tab-Root_NonVersionedParentObjects" element
    Then I click "My Non Versioned Parent Object" in the "#Form_EditForm_NonVersionedParentObjects" element
      And I should not see "Draft" in the ".breadcrumbs-wrapper" element
    Then I click "Versioned child objects" in the ".ui-tabs-nav" element
      And I click "Add Versioned Child Object" in the "#Form_ItemEditForm_VersionedChildObjects" element
      And I fill in "Name" with "My Versioned child object"
      And I press the "Create" button
      And I wait 2 seconds
      And I should see "Draft" in the ".breadcrumbs-wrapper" element
    Then I click "My Non Versioned Parent Object" in the ".breadcrumbs-wrapper" element
      And I should see "Modified" in the ".breadcrumbs-wrapper" element
    Then I click "My Page" in the ".breadcrumbs-wrapper" element
      And I should see "Draft" in the ".breadcrumbs-wrapper" element
      And I should see "My Non Versioned Parent Object" in the "#Form_EditForm" element
      And I should see "Modified" in the "#Form_EditForm" element
      And I press the "Publish" button
    Then I click "My Non Versioned Parent Object" in the "#Form_EditForm" element
    Then I click "Versioned child objects" in the ".ui-tabs-nav" element
      And I click "My Versioned child object" in the "#Form_ItemEditForm_VersionedChildObjects" element
      And I fill in "Name" with "My Versioned child object with changes"
    Then I press the "Save" button
      And I should see "Modified" in the ".breadcrumbs-wrapper" element
    Then I click "My Non Versioned Parent Object" in the ".breadcrumbs-wrapper" element
      And I should see "Modified" in the ".breadcrumbs-wrapper" element
      And I click "Versioned child objects" in the ".ui-tabs-nav" element
      And I should see "Versioned child object with changes" in the "#Form_ItemEditForm_VersionedChildObjects" element
      And I should see "Modified" in the "#Form_ItemEditForm_VersionedChildObjects" element
    Then I click "My Page" in the ".breadcrumbs-wrapper" element
      And I should see "Modified" in the ".breadcrumbs-wrapper" element
      And I should see "My Non Versioned Parent Object" in the "#Form_EditForm" element
      And I should see "Modified" in the "#Form_EditForm" element
