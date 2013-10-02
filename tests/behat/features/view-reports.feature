Feature: View Reports
  As an author
  I want to review aggregate reports on pages
  So that I can keep an overview on the health of my website data

  Background:
    Given a "page" "Empty Page"
    And a "page" "Filled Page" with "Content"="Some Content"
    And I am logged in with "ADMIN" permissions
    And I go to "/admin/reports"

  Scenario: I can view the "Pages with no content" report
    When I follow "Pages with no content"
    Then I should see "Empty Page"
    But I should not see "Filled Page"
    When I follow "Empty Page"
    Then I should see an edit page form
    And the "Page name" field should contain "Empty Page"