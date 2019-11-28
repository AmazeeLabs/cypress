Feature: Script execution
  The Cypress module allows invocation of simple PHP scripts to execute low
  level Drupal operations using the 'cy.drupalScript' command.

  @drush
  Scenario: Execute a Drush command in a test site
    Given the test case has set up a new test site
    When the test case uses 'cy.drush' to execute the 'status' command
    Then the status test shows the test site as site directory

  Scenario: Execute a Drupal script
    Given the test case uses 'cy.drupalSession' to authenticate as "admin"
    And the test case uses 'cy.drupalScript' to create a page with title "Testpage"
    When the test accesses the main content listing
    Then there should be an entry for the page with title "Testpage"
