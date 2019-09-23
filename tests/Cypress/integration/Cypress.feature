Feature: Cypress test helpers

  Scenario: Automatic login
    Given the user "admin" is logged in
    Then the the profile of user "admin" is accessible
