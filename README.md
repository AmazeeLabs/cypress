# Cypress for Drupal

## Overview

This module integrates the [Drupal] content management system with [Cypress] E2E
testing. It will help you to:

* Use Cypress for testing whole Drupal projects, including configuration.
* Include Cypress tests in custom Drupal modules.
* Ship custom Cypress commands and predefined cucumber step definitions with
  Drupal modules.

It also includes some handy Cypress plugins out of the box:
* The [Cucumber preprocessor] to write specifications in Gherkin.
* [Cypress testing library] to add some good-practice commands.
* An extension to test [Shadow DOM in web components].
* And another one to [upload files] during tests.


## Requirements and Installation

Apart from standard system requirements, you will need [Composer] and [npm]
available on your system first. From there, this is shortest path to get it up
and running:

[Composer]: https://getcomposer.org/
[npm]: https://www.npmjs.com/get-npm

1. Download and extract Drupal
   ```shell script
   curl -O https://ftp.drupal.org/files/projects/drupal-8.8.5.tar.gz
   tar -xvzf drupal-8.8.5.tar.gz
   cd drupal-8.8.5
   ```

2. Install the Cypress Module and upgrade PHPUnit
   ```shell script
   composer require drupal/cypress
   composer require phpunit/phpunit:^7 symfony/phpunit-bridge phpspec/prophecy sebastian/global-state:^2 sebastian/environment:^3.1 phpunit/php-code-coverage:^6
   ```

3. Install the minimal profile and the Cypress module
   ```shell script
   php core/scripts/drupal install minimal
   drush en cypress -y
   ```

4. In case of doubt, loosen the directory permissions so the module can copy
   settings files for you. If you don't, it might ask you to do that yourself.
   ```shell script
   chmod 755 sites/default
   ```

5. Start a test server and run tests
   ```shell script
   vendor/bin/drush serve &
   vendor/bin/drush cypress:run
   ```

The test suite for the Cypress module itself, located in the `tests\Cypress`,
should have been executed. They also serve as examples on how to write Cypress
tests yourself.

> How meta!

You are good to go, now draw the rest of the *drowsy* the owl!

![A detailed instruction on how to draw an owl](owl.jpeg)


[Drupal]: https://www.drupal.org
[Cypress]: https://www.cypress.io
[Cucumber preprocessor]: https://github.com/TheBrainFamily/cypress-cucumber-preprocessor
[Cypress testing library]: https://testing-library.com/docs/cypress-testing-library/intro
[Shadow DOM in web components]: https://github.com/abramenal/cypress-shadow-dom
[upload files]: https://www.npmjs.com/package/cypress-file-upload


## Configuration

Back to serious. What happened now? A new directory called
`drupal-cypress-environment` was created, which you can safely add to
`.gitignore`. All dependencies have been downloaded there and the Drupal
installation was scanned for modules with a `tests/Cypress` directory which
where added as new test suites. Additionally a `testing.services.yml` file
should have been created in your `sites/default` directory. This one can be
added to your git repository, since it allows you to adapt configuration:

* `cypress.enabled`: **This one is important!** It is disabled by default and
  should be enabled ***only*** in `testing.services.yml`. It opens up backdoors
  for Cypress to speed up tests, which are huge security threats for a publicly
  accessible website.
* `cypress.executable.drush`: A path to the `drush` executable that will be used
  by cypress. Relative to the Drupal root directory. It defaults to
  `vendor/bin/drush`, which is where it will be if you simply download Drupal as
  shown above. But if you use a different directory layout (like the
  [composer template]), you might have to adapt this.
* `cypress.test_suites`: The module will
  register a test suite for each module containing a `tests/Cypress` directory
  with the modules machine name. This parameter allows to add other directories
  as well.

The `cypress:run` command will just run all scoped tests, while `cypress:open`
starts the Cypress user interface for interactive debugging.

[composer template](https://github.com/drupal-composer/drupal-project)

## Test suites

A test suite can hook into the test framework in different ways by simply
containing certain files or directories.

1. `package.json`: If a test suite contains a `package.json` file, its
   dependencies and additional settings will be merged into the global
   package that is automatically maintained in `drupal-cypress-environment`.
2. `integration`: The actual tests and implementations. The modules uses the
   [Cucumber preprocessor] for Cypress in `nonGlobalStepDefinitions` mode. This
   means that test specifications are written in `*.feature` files and
   implementations in arbitrary `*.js` files stored in a directory with the same
   name as the `*.feature` file. Please refer to the [Cucumber preprocessor]
   documentation for a lot more details on test organisation.
3. `steps`: This directory will be scanned for `*.js` files that contain global
   step definitions. These are available to all scenarios. Not just within
   the current suites `integration` folder, but also to other test suites. This
   can be used to share common steps defined by a Drupal module.
4. `plugins/index.js`: This file will be automatically added to Cypress plugin
   system and allows to add  additional extensions that are not covered out of
   the box by this module.
5. `support/index.js`: That's the entry point for anything that would go into
   Cypress notion of `support` files. Here, for example, test suites can share
   reusable commands.


## Drush commands

The module comes with list of simple drush commands.

### `drush cypress:list`

Simply lists all discovered test suites. Nothing fancy.

### `drush cypress:run`

Will run all or a specific set of test suites. Also accepts an option to define
[tags] that should be run.
[tags]: https://github.com/TheBrainFamily/cypress-cucumber-preprocessor#running-tagged-tests

*Examples:*
```shell script
# Run all test suites
drush cypress:run

# Run all tests of a specific suite
drush cypress:run mytestsuite

# Run all tests within a folder in a specific suite
drush cypress:run mytestsuite:sub/folder

# Run a specific spec file
drush cypress:run mytestsuite:sub/folder/Fancy.feature

# Run all tests matching a certain tags expression
drush cypress:run --tags "@Completed and @Issue-123"

# Both options can be combined
drush cypress:run mytestsuite:sub/folder --tags "@Completed and @Issue-123"
```

### `drush cypress:open`

Starts the Cypress user interface for running tests interactively.

### `drush cypress:init`

Rebuilds the Cypress environment. This is automatically invoked when running
`drush cypress:run` or `drush cypress:open`, but can be used if you need to run
cypress directly from within `drupal-cypress-environment`.


## Predefined commands

The Cypress module comes with a set of predefined commands that can
be used by other test suites to easily interact with Drupal.

### `cy.drupalInstall()`

Install Drupal at the beginning of your test case. This command reuses the
test setup behind Drupals Browsertests. If the tests are run against a SQLite
database, it also automatically caches the install process and reuses it across
test runs to speed things.
It takes a set of options as arguments:

The `profile` option defines the installation profile to use. It defaults to
`testing`.

```javascript
cy.drupalInstall({profile: 'umami_demo'});
```

The value of `config` can be the path to a configuration sync directory,
relative to the Drupal root. This can be used to run tests against a fully
configured project. *The chosen `profile` has to match the one in configuration
in this case.*

```javascript
cy.drupalInstall({config: '../config/sync'});
```

`setup` allows to define a custom setup script (identical to nightwatch test
setups). The path should be prefixed with the test suites name.

```javascript
cy.drupalInstall({setup: 'cypress:integration/CypressTestSiteInstallScript.php'});
```

The `cache` property allows to define a persistent cache file that can be placed
anywhere relative to the Drupal root directory. It can be used to maintain a
*cached* version of the installation to speed up exection on build servers as
well as testing update procedures properly.
After a site has been recreated from cache, the setup process will automatically
run `drush updb -y` and `drush cim -y`.

```javascript
cy.drupalInstall({cache: '../install-cache.zip'});
```

Using the `strictConfigCheck` flag, Drupal config checking can be disabled. This
only makes sense in combination with a `config` directory.

```javascript
cy.drupalInstall({strictConfigCheck: false});
```

### `cy.drupalUninstall`

This should be called at the end of each test case (preferrably in `afterEach`),
to clean up test site installs created by `cy.drupalInstall`.

```javascript
cy.drupalUninstall();
```

### `cy.drush`

Execute arbitrary drush commands against the current test site.

```javascript
cy.drush('cron');
```

### `cy.drupalScript`

Execute a simple PHP script in the test suite. Can be used for test content
setup or other tasks that don't have to be run through the user interface. The
script is executed in context of a fully booted Drupal environment and has
access to the container much like `drush scr`.

The first argument is the script path, relative to your test suite. The second
an arbitrary context object that will be passed to the script as an `$args`
variable.

```javascript
cy.drupalScript('cypress:integration/Scripts/testPage.php', {title: "Test page"});
```

### `cy.drupalSession`

Control the user session a test case operates in. The settings will persist
across subsequent requests within this test case.

`user` to automatically log in a specific account:

```javascript
cy.drupalSession({user: 'admin'});
```

`language` to select the language the system is accessed in:

```javascript
cy.drupalSession({language: 'de'});
```

`workspace` to make the system automatically switch to a specific workspace:

```javascript
cy.drupalSession({workspace: 'stage'});
```

... and a boolean flag to show or hide the toolbar during the test run. By
default the toolbar is turned of to avoid problems with Cypress not being able
to click elements because they are hidden behind it.

```javascript
cy.drupalSession({toolbar: true});
```

### `cy.drupalVisitEntity`

Search for a Drupal entity by properties and visit one of the registered link
patterns. This allows to easily access entity pages without relying on internal
entity id's. This example would bring you to the edit page of a node with title
`Testpage`.

```javascript
cy.drupalVisitEntity('node', {title: "Testpage"}, 'edit-form');
```
