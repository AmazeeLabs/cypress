declare namespace Cypress {

  interface SessionOptions {
    user?: string
    language?: string
    workspace?: string
    toolbar?: boolean
  }

  interface SearchProperties {
    [key: string]: string
  }

  interface EntityProperties {
    [key: string]: string | EntityProperties
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
     * @param profile
     *   The install profile. Defaults to testing.
     * @param setup
     *   The setup file. Defaults to CypressTestSetup.
     * @param config
     *   Path to a config sync directory. Relative to the Drupal root directory.
     * @param cache
     *   Path to a zip file that contains a cached site install.
     */
    drupalInstall(profile?: string, setup?: string, config?: string, cache?: string): Chainable<Subject>

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
    drush(command?: string): Chainable<Subject>

    /**
     * Execute a php script with arguments.
     *
     * Automatically aliases module paths. Given there is a module "my_module"
     *  with a script file in:
     *
     * `tests/Cypress/integration/Cypress/testPage.php`
     *
     * ... then `drupalScript` can be called like this:
     *
     * ```
     * cy.drupalScript('my_module/testPage.php', {'title': 'Test title'})`
     * ```
     *
     * Scripts in the global 'steps' folder are moved to 'common':
     *
     * `cy.drupalScript('common/my_module/testPage.php', {'title': 'Test title'})`
     *
     * @param script
     * @param arguments
     */
    drupalScript(script: string, arguments?: any): Chainable<Subject>

    /**
     * Initiate a Drupal session.
     *
     * @param options
     */
    drupalSession(options: SessionOptions): Chainable<Subject>

    /**
     *
     * @param type
     * @param properties
     * @param session
     */
    // drupalCreateEntity(type: string, properties: EntityProperties, session: SessionOptions): Chainable<Subject>

    /**
     *
     * @param type
     * @param search
     * @param properties
     * @param session
     */
    // drupalEditEntity(type: string, search: SearchProperties, properties: EntityProperties, session: SessionOptions): Chainable<Subject>

    /**
     *
     * @param type
     * @param search
     * @param session
     */
    // drupalDeleteEntity(type: string, search: SearchProperties, session: SessionOptions): Chainable<Subject>

    /**
     * Visit an entity link.
     *
     * @param type
     * @param search
     * @param link
     */
    drupalVisitEntity(type: string, search: SearchProperties, link?: string): Chainable<Subject>
  }
}
