<?php

namespace Drupal\cypress\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Consolidation\SiteProcess\Util\Tty;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\cypress\CypressInterface;
use Drush\Commands\DrushCommands;
use Symfony\Component\Filesystem\Filesystem;

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

  /**
   * @command cypress:init
   */
  public function init() {
    $this->logger()->notice('Configuring Cypress environment.');
    $this->cypress->init([]);
  }

  /**
   * @command cypress:open
   */
  public function open() {
    $this->logger()->notice('Opening Cypress user interface.');
    $this->cypress->open([]);
  }

  /**
   * @command cypress:run
   * @param spec
   *   The specs to run. Folders are relative to the Cypress environment.
   * @option tags
   *   The tags to run.
   */
  public function run($spec = NULL, $options = ['tags' => '']) {
    $this->logger()->notice('Running Cypress headless mode.');
    if ($spec) {
      $options['spec'] = $spec;
    }
    $this->cypress->run($options);
  }
}

