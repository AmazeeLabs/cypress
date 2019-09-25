declare namespace Cypress {
  enum  EnabledState {
    ON = "on",
    OFF = "off",
  }

  interface SessionOptions {
    user?: string
    language?: string
    workspace?: string
    toolbar?: EnabledState
  }

  interface SearchProperties {
    [key: string]: string
  }

  interface Chainable<Subject> {
    /**
     * Install Drupal using a specific test setup file.
     *
     *
     * Automatically aliases module paths. Given there is a module "my_module"
     *  with a setup file in:
     *
     * `tests/Cypress/fixtures/TestSetup.php`
     *
     * ... then `drupalSetup` can be called like this:
     *
     * `cy.drupalSetup('my_module/TestSetup.php')`
     *
     * @param setup
     *   The setup file.
     */
    drupalInstall(setup?: String): Chainable<Subject>

    /**
     * Clean up a test site that has been installed with `drupalInstall`.
     */
    drupalUninstall(): Chainable<Subject>

    /**
     * Execute a drush command in the current site.
     *
     * @param command
     *   The drush command without `drush`.
     */
    drush(command?: String): Chainable<Subject>

    /**
     * Initiate a Drupal session.
     *
     * @param options
     */
    drupalSession(options: SessionOptions): Chainable<Subject>

    /**
     * Visit an entity link.
     *
     * @param type
     * @param properties
     * @param link
     */
    visitDrupalEntity(type: string, properties: SearchProperties, link?: string): Chainable<Subject>
  }
}
