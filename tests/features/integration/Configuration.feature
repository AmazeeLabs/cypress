Feature: Install from configuration
  The 'cy.drupalInstall' allows to run tests against an existing configuration
  directory.

  Scenario: Install
    Given there is a configuration sync directory "features/config"
    And "features/config" contains a new content type "Test page type"
    When the test uses 'cy.drupalInstall' to install from "features/config"
    And the test accesses the content type listing
    Then there should be a content type called "Test page type"

