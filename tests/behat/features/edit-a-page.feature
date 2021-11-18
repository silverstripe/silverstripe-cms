Feature: Edit a page
  As an author
  I want to edit a page in the CMS
  So that I correct errors and provide new information

  Background:
    Given a "page" "About Us" has the "Content" "<p>My content</p>"
    And a "image" "assets/file1.jpg"
    #And a file "assets/file1.jpg" with changes "image"="assets/folder1/file2.jpg" and "page"="About Us"
    And I am logged in with "ADMIN" permissions
    And I go to "/admin/pages"
    Then I should see "About Us" in the tree

  @javascript
  Scenario: I can open a page for editing from the pages tree
    When I click on "About Us" in the tree
    Then I should see an edit page form

  @javascript
  Scenario: I can edit title and content and see the changes on draft
    When I click on "About Us" in the tree
    Then I should see an edit page form

    When I fill in "Title" with "About Us!"
    And I fill in the "Content" HTML field with "<p>my new content</p>"
    And I press the "Save" button
    Then I should see the "Saved" button

    When I click on "About Us" in the tree
    Then the "Title" field should contain "About Us!"
    And the "Content" HTML field should contain "my new content"

  @javascript
  Scenario: I can toggle between the main tabs on a page
    When I click on "About Us" in the tree
    Then I should see an edit page form
    And I should see a "Settings" tab in the CMS content header tabs
    And the "Content" header tab should be active

    When I click on "Settings" in the header tabs
    Then the "Settings" header tab should be active
    And the "Content" header tab should not be active

  Scenario: Frontend changes
    When I click on "About Us" in the tree

    # Change URL segment
    And I press the "Edit" button
    Then the rendered HTML should contain "/about-us"
    And I fill in "Form_EditForm_URLSegment" with "about-modified-us"
    And I press the "OK" button
    Then the rendered HTML should contain "/about-modified-us"

    # Add metadata
    When I click on the "#ui-accordion-Form_EditForm_Metadata-header-0" element
    And I wait for 1 second
    And I fill in "Meta Description" with "MyMetaDesc"

    # Modified content is not displayed on the live site
    When I press the "Save" button
    And I go to "/about-modified-us?stage=Stage"
    Then I should see "About Us"
    And I go to "/about-modified-us"
    Then I should not see "About Us"
    
    # Assert URL segment + metadata on frontend
    When I go to "/admin/pages"
    And I click on "About Us" in the tree
    And I press the "Publish" button
    And I go to "/about-modified-us"
    Then I should see "About Us"
    And the rendered HTML should contain "<meta name=\"description\" content=\"MyMetaDesc\""

    # Link to an email address
    When I go to "/admin/pages"
    And I click on "About Us" in the tree

  Scenario: TinyMCE asset linking
    When I click on "About Us" in the tree

    # Embed files from the "Files" section of the admin area
    And I click on the "div[aria-label='Insert from Files'] button" element
    And I click on the ".gallery__files .gallery-item__thumbnail" element
    And I press the "Insert file" button

    # Link to a file in the "Files" section of the admin area
    And I click on the "div[aria-label='Insert link [Ctrl+K]'] button" element
    And I select "Link to a file" from the TinyMCE menu with javascript
    And I click on the ".gallery__files .gallery-item__thumbnail" element
    And I fill in "Form_fileInsertForm_Text" with "MyImage"
    And I press the "Link to file" button

    # Embed media from a URL
    And I click on the "div[aria-label='Insert media via URL'] button" element
    And I fill in "Form_remoteCreateForm_Url" with "https://www.youtube.com/watch?v=ScMzIvxBSi4"
    And I press "Add media"
    And I wait for 15 seconds
    And I press "Insert media"

    # Assert on frontend
    And I press the "Publish" button
    And I go to "/about-us"
    # insert from files
    Then the rendered HTML should contain "<img src=\"/assets/file1.jpg\""
    # link to a file
    Then the rendered HTML should contain "<a href=\"/assets/file1.jpg\">"
    # media embed
    Then the rendered HTML should contain "src=\"https://www.youtube.com/embed/ScMzIvxBSi4?feature=oembed\""

  Scenario: Change page type
    When I click on "About Us" in the tree
    And I click the "Settings" CMS tab
    And I select "Virtual Page" from the "#Form_EditForm_ClassName" field with javascript
    And I press the "Save" button
    Then I should see "Please choose a linked page in the main content fields in order to publish"

  Scenario: Change permission levels for who can view and edit the page, at an individual page level
    When I click on "About Us" in the tree
    And I click the "Settings" CMS tab
    And I select the "Form_EditForm_CanViewType_LoggedInUsers" radio button
    And I press the "Publish" button

    # Logout and assert frontend not visible to not-logged-in users
    And I go to "/Security/login"
    And I press the "Log in as someone else" button
    And I go to "/about-us"
    Then I should not see "About us"
