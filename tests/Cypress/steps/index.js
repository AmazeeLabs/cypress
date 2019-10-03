// @ts-check
/// <reference types="Cypress" />

// Given the user "admin" is logged in
Given(/^the user "([^"]*)" is logged in$/, function () {
  cy.drupalSession({user: 'admin'});
});


