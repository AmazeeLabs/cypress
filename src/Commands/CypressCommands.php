<?php

namespace Drupal\cypress\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\Component\Utility\NestedArray;
use Drupal\cypress\CypressInterface;
use Drupal\cypress\CypressRootFactory;
use Drush\Commands\DrushCommands;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

/**
 * Cypress drush commands.
 */
class CypressCommands extends DrushCommands {

  protected $cypress;
  protected $testDirectories;
  protected $fileSystem;
  protected $appRoot;

  public function __construct(CypressInterface $cypress, array $testDirectories, $appRoot) {
    parent::__construct();
    $this->testDirectories = $testDirectories;
    $this->cypress = $cypress;
    $this->fileSystem = new Filesystem();
    $this->appRoot = $appRoot;
  }

  /**
   * @command cypress:list
   */
  public function list() {
    $rows = [];
    $length = max(array_map('strlen', array_keys($this->testDirectories)));
    foreach ($this->testDirectories as $id => $dir) {
      $rows[] = [
        'Suite' => str_pad(trim($id), $length) . ' :',
        'Directory' => $this->fileSystem->makePathRelative($dir, $this->appRoot),
      ];
    }
    return new RowsOfFields($rows);
  }

  protected function setupTestingServices() {
    $sitePath = \Drupal::service('site.path');
    $modulePath = drupal_get_path('module', 'cypress');
    if (!$this->fileSystem->exists($sitePath . '/testing.services.yml')) {
      if (!$this->confirm("No 'testing.services.yml' found in '$sitePath'. Create one?", TRUE)) {
        $this->logger->warning("Aborted.");
        return FALSE;
      }
      try {
        $this->fileSystem->copy(
          $modulePath . '/example.testing.services.yml',
          $sitePath . '/testing.services.yml'
        );
      } catch (\Exception $exc) {
        $this->logger->warning("Could not create '$sitePath/testing.services.yml'.");
        $this->logger->warning("Please copy '$modulePath/example.testing.services.yml' to '$sitePath/testing.services.yml'.");
        return FALSE;
      }
    }
    $services = Yaml::parseFile($sitePath . '/testing.services.yml');
    if (!NestedArray::getValue($services, ['parameters', 'cypress.enabled'])) {
      $this->logger->warning("Cypress is not enabled in '$sitePath/testing.services.yml'.");
      $this->logger->warning("Please set the 'cypress.enabled' parameter to true.");
      return FALSE;
    }
    return TRUE;
  }

  /**
   * @command cypress:init
   * @description Initiate the cypress environment.
   */
  public function init() {
    if ($this->setupTestingServices()) {
      $this->logger()->notice('Configuring Cypress environment.');
      $this->cypress->init([]);
    }
  }

  /**
   * @command cypress:open
   * @description Open the cypress interface.
   */
  public function open() {
    if ($this->setupTestingServices()) {
      $this->logger()->notice('Opening Cypress user interface.');
      $this->cypress->open([]);
    }
  }

  /**
   * @command cypress:run
   * @description Run cypress tests in a headless browser.
   * @param spec
   *   The specs to run. Prefixed with the test suite. `drush cypress:run cypress:integration/Session.feature`
   * @option tags
   *   The tags to run.
   */
  public function run($spec = NULL, $options = ['tags' => '']) {
    if ($this->setupTestingServices()) {
      $this->logger()->notice('Running Cypress headless mode.');
      if ($spec) {
        if (strpos($spec, ':') !== FALSE) {
          list($suite, $spec) = explode(':', $spec);
          $spec = $suite . '/' . $spec;
        }
        $spec = 'integration/' . $spec;
        if (pathinfo($spec, PATHINFO_EXTENSION) !== 'feature') {
          $spec .= '/**/*.*';
        }
        $options['spec'] = $spec;
      }
      $this->cypress->run($options);
    }
  }

  /**
   * @command cypress:clear
   * @description Clear cypress and simpletest caches.
   */
  public function clear() {
    $this->fileSystem->remove(implode(
      '/',
      [
        $this->appRoot,
        CypressRootFactory::CYPRESS_ROOT_DIRECTORY,
        'cache',
      ]
    ));
    $this->logger->notice('Cypress caches cleared.');
    $this->fileSystem->remove($this->appRoot . '/sites/simpletest');
    $this->logger->notice('Simpletest sites removed.');
  }
}

