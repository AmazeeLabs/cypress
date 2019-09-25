// @ts-check
/// <reference types="Cypress" />

import {Given} from "cypress-cucumber-preprocessor/steps";

// Given the user "admin" is logged in
Given(/^the user "([^"]*)" is logged in$/, function () {
  cy.drupalSession({user: 'admin'});
});

