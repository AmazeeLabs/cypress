<?php

namespace Drupal\cypress\Commands;

use Consolidation\SiteProcess\Util\Tty;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drush\Commands\DrushCommands;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class CypressCommands extends DrushCommands {

  public static $CYPRESS_VERSION = '3.4.1';

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
   * Symfony process that will run `npm install` in the `.cypress` directory.
   *
   * @var \Symfony\Component\Process\Process
   */
  protected $npmInstall;

  /**
   * Symfony process that will run `cypress open` in the `.cypress` directory.
   *
   * @var \Symfony\Component\Process\Process
   */
  protected $cypressOpen;

  /**
   * Symfony process that will run `cypress run` in the `.cypress` directory.
   *
   * @var \Symfony\Component\Process\Process
   */
  protected $cypressRun;

  /**
   * A list of additional directories that contain test information.
   *
   * @var string[]
   */
  protected $testDirs;

  public function __construct(
    $appRoot,
    $cypressRoot,
    Process $npmInstall,
    Process $cypressOpen,
    Process $cypressRun,
    ModuleHandlerInterface $moduleHandler,
    Filesystem $fileSystem,
    array $testDirs = []
  ) {
    parent::__construct();
    $this->fileSystem = $fileSystem;
    $this->appRoot = $appRoot;
    $this->cypressRoot = $cypressRoot;
    $this->npmInstall = $npmInstall;
    $this->cypressOpen = $cypressOpen;
    $this->cypressRun = $cypressRun;
    $this->moduleHandler = $moduleHandler;
    $this->testDirs = $testDirs;

    $this->npmInstall->setTty(Tty::isTtySupported());
    $this->cypressOpen->setTty(Tty::isTtySupported());
    $this->cypressRun->setTty(Tty::isTtySupported());
  }

  /**
   * @command cypress:open
   */
  public function open() {
    $this->logger()->notice('Opening Cypress user interface.');
    $this->init();
    $this->cypressOpen->setTimeout(NULL);
    $this->cypressOpen->mustRun();
  }

  /**
   * @command cypress:run
   */
  public function run() {
    $this->logger()->notice('Running Cypress headless mode.');
    $this->init();
    $this->cypressOpen->setTimeout(NULL);
    $this->cypressRun->mustRun();
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

    $currentPackageJson = NULL;
    if ($this->fileSystem->exists($this->cypressRoot . '/package.json')) {
      $this->logger()->debug("Reading existing package.json from '{$this->cypressRoot}'.");
      $currentPackageJson = file_get_contents($this->cypressRoot . '/package.json');
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
    $plugins = [];
    $dependencies = [];

    $this->logger()->info('Assembling Cypress tests in modules.');
    foreach ($this->moduleHandler->getModuleList() as $id => $module) {
      $dir = $module->getPath() . '/tests/Cypress';
      $this->importTestDirectory($id, $dir, $support, $plugins, $dependencies);
    }

    $this->logger()->info('Assembling Cypress tests from test directories.');
    foreach ($this->testDirs as $id => $dir) {
      $this->importTestDirectory($id, $dir, $support, $plugins, $dependencies);
    }

    $this->fileSystem->dumpFile(
      $this->cypressRoot . '/support.js',
      $this->generateSupportIndexJs($support)
    );

    $this->logger()->debug('Writing plugins.js.');
    $this->fileSystem->dumpFile(
      $this->cypressRoot . '/plugins.js',
      $this->generatePluginsIndexJs($plugins)
    );

    $packageJson = [
      'name' => 'cypress-runtime',
      'version' => '1.0.0',
      'license' => 'MIT',
      'dependencies' => [],
      'cypress-cucumber-preprocessor' => [
        'nonGlobalStepDefinitions' => TRUE
      ],
    ];

    $packageJson['dependencies']['cypress'] = static::$CYPRESS_VERSION;
    foreach ($dependencies as $id => $path) {
      $packageJson['dependencies']['drupal-cypress-' . $id] = 'file:' . $path;
    }

    $packageJson = json_encode($packageJson, JSON_PRETTY_PRINT);

    $this->logger()->debug("Writing package.json to '{$this->cypressRoot}'.");
    $this->fileSystem->dumpFile(
      $this->cypressRoot . '/package.json',
      $packageJson
    );

    if (
      (json_decode($currentPackageJson, TRUE) !== json_decode($packageJson, TRUE))
      || !$this->fileSystem->exists($this->cypressRoot . '/node_modules')
    ) {
      $this->logger()->notice('Running npm install.');
      if ($this->fileSystem->exists($this->cypressRoot . '/package-lock.json')) {
        $this->fileSystem->remove($this->cypressRoot . '/package-lock.json');
      }
      $this->npmInstall->mustRun();
    }
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
  protected function generateSupportIndexJs(array $ids) {
    $index = array_map(function ($id) {
      return "require('drupal-cypress-$id/support/index.js');";
    }, $ids);
    array_unshift(
      $index,
      '// Automatically generated by `drush cypress:init`.'
    );
    return implode("\n", $index);
  }

  /**
   * Generate a javascript file that imports plugins from given folders.
   *
   * @param array $ids
   *   A list of dependency ids.
   *
   * @return string
   *   The content for a local plugins/index.js.
   */
  protected function generatePluginsIndexJs(array $ids) {
    $index = array_map(function ($id) {
      return "  require('drupal-cypress-$id/plugins/index.js')(on, config);";
    }, $ids);
    array_unshift(
      $index,
      '// Automatically generated by `drush cypress:init`.',
      'module.exports = (on, config) => {'
    );
    $index[] = '};';
    return implode("\n", $index);
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
  protected function importTestDirectory($id, $path, &$support, &$plugins, &$dependencies) {
    $path = realpath($this->appRoot . '/' . $path);
    if (!$this->fileSystem->exists($path)) {
      return;
    }

    if ($this->fileSystem->exists($path . '/package.json')) {
      $this->logger()->debug('package.json found in ' .  $path . '.');
      $dependencies[$id] = $path;
    }

    if ($this->fileSystem->exists($path . '/plugins/index.js')) {
      $this->logger()->debug('plugins/index.js found in ' .  $path . '.');
      $plugins[] = $id;
    }

    if ($this->fileSystem->exists($path . '/support/index.js')) {
      $this->logger()->debug('support/index.js found in ' .  $path . '.');
      $support[] = $id;
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
