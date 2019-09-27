<?php

namespace Drupal\cypress\Commands;

use Consolidation\SiteProcess\Util\Tty;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drush\Commands\DrushCommands;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class CypressCommands extends DrushCommands {

  public static $CYPRESS_VERSION = '3.4.1';
  public static $CYPRESS_CUCUMBER_VERSION = '1.16.0';

  /**
   * A filesystem instance.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  protected $fileSystem;

  /**
   * The applications root directory.
   *
   * @var string
   */
  protected $appRoot;

  /**
   * The path to the cypress auxiliary directory.
   *
   * @var string
   */
  protected $cypressRoot;

  /**
   * A module handler service.
   *
   * Used to scan extensions for cypress directories.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * A list of additional directories that contain test information.
   *
   * @var string[]
   */
  protected $testDirs;

  protected $npmExecutable;

  protected $nodeExecutable;

  public function __construct(
    $appRoot,
    $cypressRoot,
    ModuleHandlerInterface $moduleHandler,
    Filesystem $fileSystem,
    array $testDirs,
    $npmExecutable,
    $nodeExecutable
  ) {
    parent::__construct();
    $this->fileSystem = $fileSystem;
    $this->appRoot = $appRoot;
    $this->cypressRoot = $cypressRoot;
    $this->moduleHandler = $moduleHandler;
    $this->testDirs = $testDirs;
    $this->npmExecutable = $npmExecutable;
    $this->nodeExecutable = $nodeExecutable;
  }

  function runProcess(array $command, $cwd, $tty = TRUE) {
    $process = new Process($command, $cwd);
    if ($tty) {
      $process->setTty(Tty::isTtySupported());
    }
    $process->mustRun();
    return $process->getOutput();
  }

  /**
   * @command cypress:open
   */
  public function open() {
    $this->logger()->notice('Opening Cypress user interface.');
    $this->init();
    $this->cypress(['open']);
  }

  /**
   * @command cypress:run
   */
  public function run() {
    $this->logger()->notice('Running Cypress headless mode.');
    $this->init();
    $this->cypress(['run']);
  }

  protected function npm(array $args, $tty = TRUE) {
    array_unshift($args, $this->npmExecutable);
    return $this->runProcess($args, $this->appRoot, $tty);
  }

  protected function cypress(array $args) {
    array_unshift($args, $this->nodeExecutable, '../node_modules/.bin/cypress');
    return $this->runProcess($args, $this->cypressRoot);
  }

  /**
   * @command cypress:init
   */
  public function init() {
    $this->logger()->notice('Preparing Cypress setup.');
    if (!$this->fileSystem->exists($this->cypressRoot)) {
      $this->logger()->debug("Creating cypress base directory at '{$this->cypressRoot}'.");
      $this->fileSystem->mkdir($this->cypressRoot);
    }

    if (!$this->fileSystem->exists($this->appRoot . '/package.json')) {
      $this->logger()->notice("No package.json found in '{$this->appRoot}'.");
      $this->logger()->notice("Running 'npm init -y'.");
      $this->npm(['init', '-y']);
    }

    $this->logger()->notice("Adding cypress-cucumber-preprocessor configuration to {$this->appRoot}/package.json.");
    $packageJson = json_decode(file_get_contents($this->appRoot . '/package.json'), TRUE);
    $packageJson['cypress-cucumber-preprocessor']['nonGlobalStepDefinitions'] = TRUE;
    $this->fileSystem->dumpFile($this->appRoot . '/package.json', json_encode($packageJson, JSON_PRETTY_PRINT));

    $foundCypress = FALSE;
    $result = NULL;
    try {
      $result = $this->npm(['view', 'cypress', 'version'], FALSE);
      $foundCypress = trim($result) === static::$CYPRESS_VERSION;
    } catch (ProcessFailedException $exc) {}
    if (!$foundCypress) {
      $this->logger()->debug('Installing cypress.');
      $this->runProcess([$this->npmExecutable, 'install', 'cypress@'.static::$CYPRESS_VERSION], $this->appRoot);
    }
    else {
      $this->logger()->debug('Found cypress version: ' . trim($result));
    }

    $foundCypressCucumber = FALSE;
    try {
      $result = $this->npm(['view', 'cypress-cucumber-preprocessor', 'version'], FALSE);
      $foundCypressCucumber = trim($result) === static::$CYPRESS_CUCUMBER_VERSION;
    } catch (ProcessFailedException $exc) {}
    if (!$foundCypressCucumber) {
      $this->logger()->debug('Installing cypress-cucumber-preprocessor.');
      $this->runProcess([$this->npmExecutable, 'install', 'cypress-cucumber-preprocessor@'.static::$CYPRESS_CUCUMBER_VERSION], $this->appRoot);
    }
    else {
      $this->logger()->debug('Found cypress-cucumber-preprocess version: ' . trim($result));
    }

    $this->logger()->debug("Writing cypress.json to '{$this->cypressRoot}'.");
    $this->fileSystem->dumpFile(
      $this->cypressRoot . '/cypress.json',
      json_encode([
        'ignoreTestFiles' => ['*.js', '*.php'],
        'integrationFolder' => 'integration',
        'pluginsFile' => 'plugins.js',
        'supportFile' => 'support.js',
        'video' => FALSE,
        // TODO: Make configurable.
        'baseUrl' =>  'http://localhost:8888',
      ], JSON_PRETTY_PRINT)
    );

    $this->logger()->debug("Clearing test directories in '{$this->cypressRoot}'.");
    $this->removeDirectory($this->cypressRoot . '/integration');
    $this->removeDirectory($this->cypressRoot . '/TestSite');
    $this->fileSystem->mkdir($this->cypressRoot . '/integration');
    $this->fileSystem->mkdir($this->cypressRoot . '/integration/common');
    $this->fileSystem->mkdir($this->cypressRoot . '/TestSite');

    $support = [];

    $this->logger()->info('Assembling Cypress tests in modules.');
    foreach ($this->moduleHandler->getModuleList() as $id => $module) {
      $dir = $module->getPath() . '/tests/Cypress';
      $this->importTestDirectory($id, $dir, $support);
    }

    $this->logger()->info('Assembling Cypress tests from test directories.');
    foreach ($this->testDirs as $id => $dir) {
      $this->importTestDirectory($id, $dir, $support);
    }

    $this->fileSystem->dumpFile(
      $this->cypressRoot . '/support.js',
      $this->generateSupportIndexJs($support)
    );

    $this->logger()->debug('Writing plugins.js.');
    $this->fileSystem->dumpFile(
      $this->cypressRoot . '/plugins.js',
      $this->generatePluginsIndexJs()
    );
  }

  /**
   * Generate a javascript file that imports `index` from given folders.
   *
   * @param array $ids
   *   A list of dependency ids.
   *
   * @return string
   *   The content for a local index.js.
   */
  protected function generateSupportIndexJs(array $paths) {
    $index = array_map(function ($path) {
      return "require('$path/support/index.js');";
    }, $paths);
    array_unshift(
      $index,
      '// Automatically generated by `drush cypress:init`.'
    );
    return implode("\n", $index);
  }

  /**
   * Generate a javascript file that imports plugins.
   *
   * @return string
   *   The content for a local plugins/index.js.
   */
  protected function generatePluginsIndexJs() {
    $plugins =  <<<JS
const cucumber = require('cypress-cucumber-preprocessor').default;

module.exports = (on, config) => {
  on('file:preprocessor', cucumber());
};
JS;
    return $plugins;
  }

  /**
   * Import tests from a given extension directory.
   *
   * @param $id
   * @param $path
   * @param $support
   * @param $plugins
   * @param $dependencies
   */
  protected function importTestDirectory($id, $path, &$support) {
    $path = $this->appRoot . '/' . $path;
    if (!$this->fileSystem->exists($path)) {
      return;
    }

    if ($this->fileSystem->exists($path . '/support/index.js')) {
      $this->logger()->debug('support/index.js found in ' .  $path . '.');
      $support[$id] = $path;
    }

    if ($this->fileSystem->exists($path . '/integration')) {
      $this->logger()->debug('integration folder found in ' .  $path . '.');
      $this->fileSystem->symlink(
        $path . '/integration',
        $this->cypressRoot . '/integration/' . $id
      );
    }

    if ($this->fileSystem->exists($path . '/steps')) {
      $this->logger()->debug('steps folder found in ' .  $path . '.');
      $this->fileSystem->symlink(
        $path . '/steps',
        $this->cypressRoot . '/integration/common/' . $id
      );
    }

    if ($this->fileSystem->exists($path . '/fixtures')) {
      $this->logger()->debug('fixtures folder found in ' .  $path . '.');
      $this->fileSystem->symlink(
        $path . '/fixtures',
        $this->cypressRoot . '/fixtures/' . $id
      );
    }
  }

  /**
   * @param string $directory
   */
  protected function removeDirectory($directory) {
    if ($this->fileSystem->exists($directory)) {
      $this->fileSystem->remove($directory);
    }
  }

}
