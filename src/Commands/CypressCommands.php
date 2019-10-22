<?php

namespace Drupal\cypress\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\Component\Utility\NestedArray;
use Drupal\cypress\CypressInterface;
use Drush\Commands\DrushCommands;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

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
    foreach ($this->testDirectories as $id => $dir) {
      $rows[] = [
        'Suite' => trim($id),
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
   */
  public function init() {
    if ($this->setupTestingServices()) {
      $this->logger()->notice('Configuring Cypress environment.');
      $this->cypress->init([]);
    }
  }

  /**
   * @command cypress:open
   */
  public function open() {
    if ($this->setupTestingServices()) {
      $this->logger()->notice('Opening Cypress user interface.');
      $this->cypress->open([]);
    }
  }

  /**
   * @command cypress:run
   * @param spec
   *   The specs to run. Folders are relative to the Cypress environment.
   * @option tags
   *   The tags to run.
   */
  public function run($spec = NULL, $options = ['tags' => '']) {
    if ($this->setupTestingServices()) {
      $this->logger()->notice('Running Cypress headless mode.');
      if ($spec) {
        $options['spec'] = $spec;
      }
      $this->cypress->run($options);
    }
  }
}

