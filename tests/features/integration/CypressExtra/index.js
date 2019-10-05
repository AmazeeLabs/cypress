// @ts-check
/// <reference types="Cypress" />

// And there is a page with title "Test"
Given(/^there is a page with title "([^"]*)"$/, function (title) {
  cy.drupalScript('features:integration/CypressExtra/testPage.php', {title: title})
});

// When the user accesses the main content listing
When(/^the user accesses the main content listing$/, function () {
  cy.visit('/admin/content');
});

// Then there should be an entry for the page with title "Test"
Then(/^there should be an entry for the page with title "([^"]*)"$/, function (title) {
  cy.contains(title);
});
