// @ts-check
/// <reference types="Cypress" />


// Given there is a configuration sync directory "features/config"
Given(/^there is a configuration sync directory "([^"]*)"$/, function () {
  // Nothing to do.
});

// And "features/config" contains a new content type "Test page type"
Given(/^"([^"]*)" contains a new content type "([^"]*)"$/, function () {
  // Nothing to do.
});

// When the test uses 'cy.drupalInstall' to install from "features/config"
When(/^the test uses 'cy.drupalInstall' to install from "([^"]*)"$/, function (config) {
  cy.drupalInstall('minimal', null, config);
});

// And the test accesses the content type listing
When(/^the test accesses the content type listing$/, function () {
  cy.drupalSession({user: 'admin'});
  cy.visit('/admin/structure/types');
});

// Then there should be a content type called "Test page type"
Then(/^there should be a content type called "([^"]*)"$/, function (type) {
  cy.contains(type);
});
