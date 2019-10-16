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
  cy.findByText(title);
});

// Given the test case has set up a new test site
Given(/^the test case has set up a new test site$/, function () {
  // Already done in beforeEach
});

// When the test case uses 'cy.drush' to execute the 'status' command
When(/^the test case uses 'cy.drush' to execute the 'status' command$/, function () {
  cy.drush('status --field=site').then(result => {
    cy.state('current_site', result.stdout);
  });
});

// Then the status test shows the test site as site directory
Then(/^the status test shows the test site as site directory$/, function () {
  expect(cy.state('current_site')).to.equal(Cypress.env('DRUPAL_SITE_PATH'));
});
