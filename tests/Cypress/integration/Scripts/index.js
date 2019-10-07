// @ts-check
/// <reference types="Cypress" />
require('../steps');

// And there is a page with title "Test"
Given(/^the test case uses 'cy.drupalScript' to create a page with title "([^"]*)"$/, function (title) {
  cy.drupalScript('cypress:integration/Scripts/testPage.php', {title: title})
});

// When the user accesses the main content listing
When(/^the test accesses the main content listing$/, function () {
  cy.visit('/admin/content');
});

// Then there should be an entry for the page with title "Test"
Then(/^there should be an entry for the page with title "([^"]*)"$/, function (title) {
  cy.contains(title);
});
