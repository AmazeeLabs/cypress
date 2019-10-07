Feature: Script execution
  The Cypress module allows invocation of simple PHP scripts to execute low
  level Drupal operations using the 'cy.drupalScript' command.

  Scenario: Execute a Drupal script
    Given the test case uses 'cy.drupalSession' to authenticate in as "admin"
    And the test case uses 'cy.drupalScript' to create a page with title "Foo bar"
    When the test accesses the main content listing
    Then there should be an entry for the page with title "Foo bar"
