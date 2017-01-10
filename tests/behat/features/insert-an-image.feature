@assets
Feature: Insert an image into a page
  As a cms author
  I want to insert an image into a page
  So that I can insert them into my content efficiently

  Background:
    Given a "page" "About Us"
    And a "image" "folder1/file1.jpg"
    And a "image" "folder1/file2.jpg"
    And I am logged in with "ADMIN" permissions
    And I go to "/admin/pages"
    And I click on "About Us" in the tree

  Scenario: I can insert an image from a URL
    Given I press the "Insert Media" HTML field button

    When I press the "Insert from URL" button
    And I fill in "RemoteURL" with "http://www.silverstripe.org/themes/ssv3/img/ss_logo.png"
    And I press the "Add url" button
    Then I should see "ss_logo.png" in the ".ss-assetuploadfield span.name" element

    When I press the "Insert" button
    Then the "Content" HTML field should contain "ss_logo.png"
    # Required to avoid "unsaved changed" browser dialog
    Then I press the "Save draft" button

  @assets
  Scenario: I can insert an image uploaded from my own computer
    Given I press the "Insert Media" HTML field button
    And I attach the file "testfile.jpg" to "AssetUploadField" with HTML5
    # TODO Delay previous step until upload succeeded
    And I wait for 2 seconds
    Then there should be a filename "Uploads/testfile.jpg" with hash "59de0c841f0da39f1d21ab12cd4fa85b8a91457c"
    When I press the "Insert" button
    Then the "Content" HTML field should contain "testfile__Resampled.jpg"
    # Required to avoid "unsaved changed" browser dialog
    Then I press the "Save draft" button

  @assets
  Scenario: I can upload an image from my own computer that matches the name of an existing file
    Given a "image" "Uploads/file1.jpg"
    When I press the "Insert Media" HTML field button
    And I attach the file "file1.jpg" to "AssetUploadField" with HTML5
    # TODO Delay previous step until upload succeeded
    And I wait for 2 seconds
    # Note change in default behaviour from 3.1, respect default Upload.replaceFile=false
    Then there should be a filename "Uploads/file1.jpg" with hash "3d0ef6ec372233e08e87f6e1f02ace9c93ce11fe"
    And there should be a filename "Uploads/file1-v2.jpg" with hash "3d0ef6ec372233e08e87f6e1f02ace9c93ce11fe"
    When I press the "Insert" button
    Then the "Content" HTML field should contain "file1-v2__Resampled.jpg"
    # Required to avoid "unsaved changed" browser dialog
    Then I press the "Save draft" button

  Scenario: I can insert an image from the CMS file store
    Given I press the "Insert Media" HTML field button
    And I fill in the "ParentID" dropdown with "folder1"
    And I click on "file1" in the "Files" table
    When I press the "Insert" button
    Then the "Content" HTML field should contain "file1__Resampled.jpg"
    # Required to avoid "unsaved changed" browser dialog
    Then I press the "Save draft" button

  Scenario: I can edit properties of an image before inserting it
    Given I press the "Insert Media" HTML field button
    And I fill in the "ParentID" dropdown with "folder1"
    And I click on "file1" in the "Files" table
    And I press the "Edit this file" button
    When I fill in "Alternative text (alt)" with "My alt"
    And I press the "Insert" button
    Then the "Content" HTML field should contain "file1__Resampled.jpg"
    And the "Content" HTML field should contain "My alt"
    # Required to avoid "unsaved changed" browser dialog
    Then I press the "Save draft" button
