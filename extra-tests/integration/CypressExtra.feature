Feature: External tests and drush scripts

  Scenario: Automatic login
    Given the user "admin" is logged in
    And there is a page with title "Test"
    When the user accesses the main content listing
    Then there should be an entry for the page with title "Test"
