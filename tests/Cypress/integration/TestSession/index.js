// @ts-check
/// <reference types="Cypress" />

beforeEach(() => {
  cy.drupalInstall('cypress/CypressTestSiteInstallScript.php');
});

afterEach(() => {
  cy.drupalUninstall();
});

// Given a test case uses 'cy.drupalSession' to authenticate in as "admin"
Given(/^the test case uses 'cy.drupalSession' to authenticate in as "([^"]*)"$/, function (account) {
  cy.drupalSession({user: account});
});

// When the test case visits the homepage and clicks the link to the "admin" account
When(/^the test case visits the homepage and clicks the link to the "([^"]*)" account$/, function (account) {
  cy.visit('/');
  cy.contains(account).click();
});

// Then then the "admin" account page should be displayed
Then(/^then the "([^"]*)" account page should be displayed$/, function (account) {
  cy.get('h1').contains(account);
});

// Given the 'toolbar' module is installed
Given(/^the "([^"]*)" module is installed$/, function () {
  // Modules already installed in test site setup.
});

// And the test case uses 'cy.drupalSession' to display the toolbar
And(/^the test case uses 'cy.drupalSession' to display the toolbar$/, function () {
  cy.drupalSession({toolbar: true});
});

// Then the toolbar should be visible
Then(/^the toolbar should be visible$/, function () {
  cy.get('#toolbar-bar');
});

// And the test case uses 'cy.drupalSession' to switch to workspace "stage"
And(/^the test case uses 'cy.drupalSession' to switch to workspace "([^"]*)"$/, function (workspace) {
  cy.drupalSession({workspace: workspace});
});

// Then the "Stage" workspace should active
Then(/^the "([^"]*)" workspace should active$/, function (workspace) {
  cy.get('.workspaces-toolbar-tab').contains(workspace);
});

// And the language "German" is enabled
And(/^the language "([^"]*)" is enabled$/, function () {
  // Already enabled in test site install.
});

const langCodes = {
  'German': 'de'
};

// And the test case uses 'cy.drupalSession' to display switch to "German"
And(/^the test case uses 'cy.drupalSession' to display switch to "([^"]*)"$/, function (language) {
  cy.drupalSession({language: langCodes[language]});
});

// Then the page is displayed in "German"
Then(/^the page is displayed in "([^"]*)"$/, function (language) {
  cy.get('html[lang="' + langCodes[language] + '"]');
});
