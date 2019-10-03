// @ts-check
/// <reference types="Cypress" />

beforeEach(() => {
  cy.drupalInstall('cypress/CypressTestSiteInstallScript.php');
});

afterEach(() => {
  cy.drupalUninstall();
});

// Then the the profile of user "admin" is access
Then(/^the the profile of user "([^"]*)" is accessible$/, function (name) {
  cy.drupalVisitEntity('user', {name: name});
});

