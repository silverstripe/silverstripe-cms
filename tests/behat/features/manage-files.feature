@javascript @assets
Feature: Manage files
  As a cms author
  I want to upload and manage files within the CMS
  So that I can insert them into my content efficiently

  Background:
    Given a "image" "assets/folder1/file1.jpg" was created "2012-01-01 12:00:00"
    And a "image" "assets/folder1/folder1.1/file2.jpg" was created "2010-01-01 12:00:00"
    And a "folder" "assets/folder2"
    And I am logged in with "ADMIN" permissions
    And I go to "/admin/assets"

  @modal
  Scenario: I can add a new folder
    Given I press the "Add folder" button
    And I type "newfolder" into the dialog
    And I confirm the dialog
    Then the "Files" table should contain "newfolder"

  Scenario: I can list files in a folder
    Given I click on "folder1" in the "Files" table
    Then the "folder1" table should contain "file1"
    And the "folder1" table should not contain "file1.1"

  Scenario: I can upload a file to a folder
    Given I click on "folder1" in the "Files" table
    And I press the "Upload" button
    And I attach the file "testfile.jpg" to "AssetUploadField" with HTML5
    And I wait for 5 seconds
    And I press the "Back to folder" button
    Then the "folder1" table should contain "testfile"

  Scenario: I can edit a file
    Given I click on "folder1" in the "Files" table
    And I click on "file1" in the "folder1" table
    And I fill in "renamedfile" for "Title"
    And I press the "Save" button
    And I follow "folder1"
    Then the "folder1" table should not contain "testfile"
    And the "folder1" table should contain "renamedfile"

  Scenario: I can delete a file
    Given I click on "folder1" in the "Files" table
    And I click on "file1" in the "folder1" table
    And I press the "Delete" button, confirming the dialog
    Then the "folder1" table should not contain "file1"

  Scenario: I can change the folder of a file
    Given I click on "folder1" in the "Files" table
    And I click on "file1" in the "folder1" table
    And I fill in "folder2" for the "Folder" dropdown
    And I press the "Save" button
    # /show/0 is to ensure that we are on top level folder
    And I go to "/admin/assets/show/0"
    And I click on "folder2" in the "Files" table
    And the "folder2" table should contain "file1"

  Scenario: I can see allowed extensions help
    When I go to "/admin/assets/add"
    And I follow "Show allowed extensions"
    Then I should see "png,"

  Scenario: I can filter the files list view using name
    Given I expand the content filters
    And I fill in "Name" with "file1"
    And I press the "Search" button
    Then the "Files" table should contain "file1"
    And the "Files" table should not contain "file2"

  Scenario: I can filter the files list view using filetype
    Given a "file" "assets/document.pdf"
    And I expand the content filters
    And I select "Image" from "File type" with javascript
    And I press the "Search" button
    Then the "Files" table should contain "file1"
    And the "Files" table should not contain "document"

  Scenario: I can filter out files that don't match the date range
    Given I expand the content filters
    And I fill in "From" with "2003-01-01"
    And I fill in "To" with "2011-01-01"
    And I press the "Search" button
    And the "Files" table should contain "file2"
    And the "Files" table should not contain "file1"
