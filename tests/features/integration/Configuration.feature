@Configuration
Feature: Install from configuration
  The 'cy.drupalInstall' allows to run tests against an existing configuration
  directory.

  Scenario: Install directly
    Given there is a configuration sync directory "features/config"
    And "features/config" contains a new content type "Test page type"
    When the test uses 'cy.drupalInstall' to install from "features/config"
    And the test accesses the content type listing
    Then there should be a content type called "Test page type"

  Scenario: Install from install cache
    Given there is a configuration sync directory "features/config"
    And "features/config" contains a new content type "Test page type"
    When the test uses 'cy.drupalInstall' to install from "features/config" from a install cache file "features/install-cache.zip"
    And the test accesses the content type listing
    Then there should be a content type called "Test page type"

  Scenario: Disable strict config checking
    Given there is a configuration sync directory "features/config-invalid"
    And the config file "system.site.yml" contains a unknown property "foo" with value "bar"
    When the test uses 'cy.drupalInstall' to install from "features/config-invalid" with 'strictConfigCheck' disabled
    Then the install procedure should not fail
