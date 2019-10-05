Feature: External tests and scripts

  Scenario: Create a test page
    Given the user "admin" is logged in
    And there is a page with title "Foo bar"
    When the user accesses the main content listing
    Then there should be an entry for the page with title "Foo bar"
