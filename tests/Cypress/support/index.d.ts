declare namespace Cypress {

  /**
   * Set of options that can be passed to cy.drupalInstall.
   */
  interface InstallationOptions {

    /**
     * The Drupal profile to install. Defaults to 'minimal'.
     */
    profile?: string

    /**
     * A configuration directory to install from, relative to DRUPAL_ROOT.
     */
    config?: string

    /**
     * Enable or disable strict config checking.
     */
    strictConfigCheck?: boolean

    /**
     * A Drupal test setup script.
     *
     * The path should be prefixed with the test suite's name as scheme.
     */
    setup?: string

    /**
     * A path to a zip file with a cached version of this install.
     *
     * If the file does not exist, it will be created after successful
     * installation.
     */
    cache?: string
  }

  /**
   * Possible options for cy.drupalSession.
   */
  interface SessionOptions {
    /**
     * The user name to authenticate with.
     */
    user?: string

    /**
     * The system language code to initiate.
     */
    language?: string

    /**
     * The workspace machine name to initiate.
     */
    workspace?: string

    /**
     * Flag to indicate if the toolbar should be displayed.
     */
    toolbar?: boolean
  }

  /**
   * Dictionary of arbitrary property values for filtering entities.
   */
  interface SearchProperties {
    [key: string]: string
  }

  /**
   * A nested dictionary of arguments that can be passed to a script.
   */
  interface ScriptArguments {
    [key: string]: string | ScriptArguments
  }

  interface Chainable<Subject> {
    /**
     * Install Drupal with a set of options:
     *
     *   The install profile. Defaults to testing.
     *   The setup file. Defaults to CypressTestSetup.
     *   Path to a config sync directory. Relative to the Drupal root directory.
     *   Path to a zip file that contains a cached site install.
     *   
     * @param options
     */
    drupalInstall(options?: InstallationOptions): Chainable<Subject>

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
     * The script path uses the test suite's name as scheme:
     *
     * ```
     * cy.drupalScript("cypress:integration/Scripts/testPage.php", {title: "Test"});
     * ```
     *
     * @param script
     * @param args
     * @param arguments
     */
    drupalScript(script: string, args: ScriptArguments): Chainable<Subject>

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
     * @param search
     * @param link
     */
    drupalVisitEntity(type: string, search: SearchProperties, link?: string): Chainable<Subject>
  }
}
