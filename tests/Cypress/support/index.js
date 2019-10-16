// @ts-check
/// <reference types="Cypress" />

import '@testing-library/cypress/add-commands';
import './commands.js';

/**
 * Override visit command to inject our custom headers.
 */
Cypress.Commands.overwrite('visit', (originalFn, url, options) => {
  const headers = Object.assign((options && options.headers) || {}, cy.state('drupalHeaders'));
  return originalFn(url, Object.assign(options || {}, {
    'headers': headers,
  }))
});
