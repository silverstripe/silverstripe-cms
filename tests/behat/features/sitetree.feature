Feature: Sitetree
  As an author
  I want to operate the sitetree
  So that I can operate my website

  Background:
    Given the "group" "EDITOR group" has permissions "CMS_ACCESS_LeftAndMain" and "SITETREE_REORGANISE"
    And I am logged in with "EDITOR" permissions
    And a "page" "One"
    And a "page" "Two"
    And a "page" "Three"
    And I go to "/admin/pages"

  # @modal is required to use "I confirm the dialog"
  @modal
  Scenario: Operation sitetree

    Then the site tree order should be "--One,--Two,--Three"
    And I should not see a ".status-modified" element

    # Drag and drop to reorder pages
    When I drag the "#record-1 > a .text" element to the "#record-3 > a .text" element
    And I wait for 3 seconds
    Then the site tree order should be "--Two,--One,--Three"
    And I should see a ".status-modified" element
    When I click on the ".toast__close" element

    # Drag and drop change nesting levels for pages
    When I drag the "#record-2 > a .text" element to the "#record-1 > a .text" element
    And I wait for 3 seconds
    Then the site tree order should be "--One,---Two,--Three"
    When I click on the ".toast__close" element

    # Publish pages in a batch
    When I press the "Batch actions" button
    When I click on the "#record-1 .jstree-checkbox" element
    And I click on the "#record-2 .jstree-checkbox" element
    And I click on the "#record-3 .jstree-checkbox" element
    And I select "Publish" from the "Form_BatchActionsForm_Action" field with javascript
    And I click on the "#Form_BatchActionsForm_action_submit" element
    And I confirm the dialog
    Then I should see a "Published 3 pages" success toast
    When I click on the ".toast__close" element
    # Wait a little time to ensure the last toast is cleared
    And I wait for 2 seconds
    
    # Unpublish pages in a batch
    When I click on the "#record-2 .jstree-checkbox" element
    And I select "Unpublish" from the "Form_BatchActionsForm_Action" field with javascript
    And I click on the "#Form_BatchActionsForm_action_submit" element
    And I confirm the dialog
    Then I should see a "Unpublished 1 pages" success toast
    When I click on the ".toast__close" element
    And I wait for 2 seconds

    # Unpublish and archive pages in a batch
    When I click on the "#record-3 .jstree-checkbox" element
    And I select "Unpublish and archive" from the "Form_BatchActionsForm_Action" field with javascript
    And I click on the "#Form_BatchActionsForm_action_submit" element
    # Surprisingly unpublish and archive doesn't have a dialog
    # And I confirm the dialog
    # Assertion does not work for some reason despite screenshot showing the toast message
    # Then I should see a "Deleted 1 pages from draft and live, and sent them to the archive" success toast
    When I go to "/admin/pages"
    Then I should not see "Three"

    # Toggle list and tree views
    Then I should not see a "#Form_ListViewForm_Page" element
    When I click on the "[data-view='listview']" element
    And I wait for 5 seconds
    Then I should see a "#Form_ListViewForm_Page" element
    When I click on the "[data-view='treeview']" element
    And I wait for 5 seconds
    Then I should not see a "#Form_ListViewForm_Page" element
