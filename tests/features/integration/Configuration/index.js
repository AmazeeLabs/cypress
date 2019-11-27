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
  cy.drupalInstall({profile: 'minimal', config: config});
});

// When the test uses 'cy.drupalInstall' to install from "features/config" with 'strictConfigCheck' disabled
When(/^the test uses 'cy.drupalInstall' to install from "([^"]*)" with 'strictConfigCheck' disabled$/, function (config) {
  cy.drupalInstall({profile: 'minimal', config: config, strictConfigCheck: false});
});

// Then the install procedure should not fail
Then(/^the install procedure should not fail$/, function () {
});

// And the config file "system.site.yml" contains a unknown property "foo" with value "bar"
When(/^the config file "system.site.yml" contains a unknown property "foo" with value "bar"$/, function () {
});

// When the test uses 'cy.drupalInstall' to install from "features/config" from a install cache file "features/install-cache.zip"
When(/^the test uses 'cy.drupalInstall' to install from "([^"]*)" from a install cache file "([^"]*)"$/, function (config, installCache) {
  cy.drupalInstall({profile: 'minimal', config: config, cache: installCache});
});

// And there is no "features/install-cache.test.zip"
Given(/^there is no "([^"]*)"$/, function (file) {
  cy.exec('rm ../' + file , {
    failOnNonZeroExit: false
  });
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

// And the the "name" property of "system.site.yml" has been changed to "Drupal loves Cypress"
When(/^the the "name" property of "system.site.yml" has been changed to "Drupal loves Cypress"$/, function () {
  cy.drupalInstall({profile: 'minimal', config: "features/config", cache: "features/install-cache.zip"});
  cy.drupalScript('features:integration/Configuration/change-site-name.php');
  cy.drupalUninstall();
});

// Then the site name is "Drupal loves Cypress"
Then(/^the site name is "([^"]*)"$/, function (name) {
  cy.drupalScript('features:integration/Configuration/revert-site-name.php');
  cy.visit('/');
  cy.get('#block-stark-branding').contains(name);
});
