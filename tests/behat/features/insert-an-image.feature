@assets
Feature: Insert an image into a page
  As a cms author
  I want to insert an image into a page
  So that I can insert them into my content efficiently

  Background:
    Given a "page" "About Us"
    And a "image" "assets/folder1/file1.jpg"
    And a "image" "assets/folder1/file2.jpg"
    And I am logged in with "ADMIN" permissions
    And I go to "/admin/pages"
    And I click on "About Us" in the tree

  Scenario: I can insert an image from a URL
    Given I press the "Insert Media" button
    Then I should see "Choose files to upload..."

    When I press the "From the web" button
    And I fill in "RemoteURL" with "http://www.silverstripe.org/themes/ssv3/img/ss_logo.png"
    And I press the "Add url" button
    Then I should see "ss_logo.png (www.silverstripe.org)" in the ".ss-assetuploadfield span.name" element

    When I press the "Insert" button  
    Then the "Content" HTML field should contain "ss_logo.png"
    # Required to avoid "unsaved changed" browser dialog
    Then I press the "Save draft" button

  @assets
  Scenario: I can insert an image uploaded from my own computer
    Given I press the "Insert Media" button
    And I press the "From your computer" button
    And I attach the file "testfile.jpg" to "AssetUploadField" with HTML5
    # TODO Delay previous step until upload succeeded
    And I wait for 2 seconds
    Then there should be a file "assets/Uploads/testfile.jpg"
    When I press the "Insert" button
    Then the "Content" HTML field should contain "testfile.jpg"
    # Required to avoid "unsaved changed" browser dialog
    Then I press the "Save draft" button

  @assets
  Scenario: I can upload an image from my own computer that matches the name of an existing file
    Given a "image" "assets/Uploads/file1.jpg"
    When I press the "Insert Media" button
    And I press the "From your computer" button
    And I attach the file "file1.jpg" to "AssetUploadField" with HTML5
    # TODO Delay previous step until upload succeeded
    And I wait for 2 seconds
    # Note change in default behaviour from 3.1, respect default Upload.replaceFile=false
    Then there should be a file "assets/Uploads/file1.jpg"
    And there should be a file "assets/Uploads/file2.jpg"
    When I press the "Insert" button
    Then the "Content" HTML field should contain "file2.jpg"
    # Required to avoid "unsaved changed" browser dialog
    Then I press the "Save draft" button

  Scenario: I can insert an image from the CMS file store
    Given I press the "Insert Media" button
    And I press the "From the CMS" button
    And I fill in the "ParentID" dropdown with "folder1"
    And I click on "file1" in the "Files" table
    When I press the "Insert" button
    Then the "Content" HTML field should contain "file1.jpg"
    # Required to avoid "unsaved changed" browser dialog
    Then I press the "Save draft" button

  Scenario: I can edit properties of an image before inserting it
    Given I press the "Insert Media" button
    And I press the "From the CMS" button
    And I fill in the "ParentID" dropdown with "folder1"
    And I click on "file1" in the "Files" table
    And I press the "Edit" button
    When I fill in "Alternative text (alt)" with "My alt"
    And I press the "Insert" button
    Then the "Content" HTML field should contain "file1.jpg"
    And the "Content" HTML field should contain "My alt"
    # Required to avoid "unsaved changed" browser dialog
    Then I press the "Save draft" button

  # TODO This needs to support using drag handles, as we no longer have 'Width' or 'Height' input fields
  @todo
  Scenario: I can edit dimensions of an existing image
    Given the "page" "About us" contains "<img src=assets/folder1/file1.jpg>"
    And I reload the current page
    When I highlight "<img src=assets/folder1/file1.jpg>" in the "Content" HTML field
    And I press the "Insert Media" button
    Then I should see "file1.jpg"
    When I fill in "Width" with "10"
    When I fill in "Height" with "20"
    And I press the "Insert" button
    Then the "Content" HTML field should contain "<img src=assets/folder1/file1.jpg width=10 height=20>"
    # Required to avoid "unsaved changed" browser dialog
    Then I press the "Save draft" button