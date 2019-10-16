<?php

namespace Drupal\cypress;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Generates the list of directories containing Cypress tests information.
 */
class CypressTestDirectoriesFactory {

  /**
   * The path the system looks for Cypress tests.
   */
  const CYPRESS_TEST_DIRECTORY = 'tests/Cypress';

  /**
   * The prefix for environment variables defining a test suite.
   */
  const CYPRESS_SUITE_PREFIX = 'CYPRESS_SUITE_';

  /**
   * The application root. All paths added should be relative to this.
   *
   * @var string
   */
  protected $appRoot;

  /**
   * A module handler instance. Used to scan modules for test directories.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * A filesystem component.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  protected $fileSystem;

  /**
   * CypressTestDirectoriesFactory constructor.
   *
   * @param string $appRoot
   *   The application root.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   A module handler to scan for directories in modules.
   */
  public function __construct($appRoot, ModuleHandlerInterface $moduleHandler) {
    $this->appRoot = $appRoot;
    $this->moduleHandler = $moduleHandler;
    $this->fileSystem = new Filesystem();
  }

  /**
   * Retrieve a list of modules that contain Cypress test information.
   *
   * @return string[]
   *   List of absolute system paths that contain Cypress tests.
   */
  public function getDirectories() {
    $directories = [];
    foreach ($this->moduleHandler->getModuleList() as $id => $module) {
      $directories[$id] = $module->getPath() . '/' . static::CYPRESS_TEST_DIRECTORY;
    }

    foreach ($_ENV as $key => $value) {
      if (substr($key,0, strlen(static::CYPRESS_SUITE_PREFIX)) === static::CYPRESS_SUITE_PREFIX) {
        $directories[strtolower(substr($key,strlen(static::CYPRESS_SUITE_PREFIX)))] = $value;
      }
    }

    return array_filter(array_map(function ($dir) {
      return $this->fileSystem->isAbsolutePath($dir) ? $dir : $this->appRoot . '/' . $dir;
    }, $directories), function ($dir) {
      return $this->fileSystem->exists($dir);
    });
  }

}
