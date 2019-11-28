// @ts-check
/// <reference types="Cypress" />

const langCodes = {
  'German': 'de'
};

beforeEach(() => {
  cy.drupalInstall({
    setup: 'cypress:integration/CypressTestSiteInstallScript.php',
  });
});

afterEach(() => {
  cy.drupalUninstall();
});

// Given a test case uses 'cy.drupalSession' to authenticate in as "admin"
Given(/^the test case uses 'cy.drupalSession' to authenticate as "([^"]*)"$/, function (account) {
  cy.drupalSession({user: account});
});

// And the test case uses 'cy.drupalSession' to display switch to "German"
And(/^the test case uses 'cy.drupalSession' to display switch to "([^"]*)"$/, function (language) {
  cy.drupalSession({language: langCodes[language]});
});

// And the test case uses 'cy.drupalSession' to switch to workspace "stage"
And(/^the test case uses 'cy.drupalSession' to switch to workspace "([^"]*)"$/, function (workspace) {
  cy.drupalSession({workspace: workspace});
});

// And the test case uses 'cy.drupalSession' to display the toolbar
Given(/^the test case uses 'cy.drupalSession' to display the toolbar$/, function () {
  cy.drupalSession({toolbar: true});
});

export {langCodes};
