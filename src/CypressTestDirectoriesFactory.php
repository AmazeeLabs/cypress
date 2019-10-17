<?php

namespace Drupal\cypress;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

/**
 * Generates the list of directories containing Cypress tests information.
 */
class CypressTestDirectoriesFactory {

  /**
   * The path the system looks for Cypress tests.
   */
  const CYPRESS_TEST_DIRECTORY = 'tests/Cypress';

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
   * Test directories provided by an external source (e.g. configured manually).
   *
   * @var string[]
   */
  protected $directories;

  /**
   * A filesystem component.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  protected $fileSystem;

  /**
   * The site path to search for a testing.services.yml
   *
   * @var string
   */
  protected $sitePath;

  /**
   * CypressTestDirectoriesFactory constructor.
   *
   * @param string $appRoot
   *   The application root.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   A module handler to scan for directories in modules.
   * @param string[] $directories
   *   Externally provided paths.
   * @param string $sitePath
   *   The site path to search for a testing.services.yml
   */
  public function __construct($appRoot,ModuleHandlerInterface $moduleHandler, $directories, $sitePath) {
    $this->appRoot = $appRoot;
    $this->moduleHandler = $moduleHandler;
    $this->directories = $directories;
    $this->fileSystem = new Filesystem();
    $this->sitePath = $sitePath;
  }

  /**
   * Retrieve a list of modules that contain Cypress test information.
   *
   * @return string[]
   *   List of absolute system paths that contain Cypress tests.
   */
  public function getDirectories() {
    $directories = $this->directories;
    foreach ($this->moduleHandler->getModuleList() as $id => $module) {
      $directories[$id] = $module->getPath() . '/' . static::CYPRESS_TEST_DIRECTORY;
    }

    if ($this->fileSystem->exists($this->sitePath . '/testing.services.yml')) {
      $yml = Yaml::parseFile($this->sitePath . '/testing.services.yml');
      $directories += NestedArray::getValue($yml, ['parameters', 'cypress.test_suites']) ?? [];
    }

    return array_filter(array_map(function ($dir) {
      return $this->fileSystem->isAbsolutePath($dir) ? $dir : $this->appRoot . '/' . $dir;
    }, $directories), function ($dir) {
      return $this->fileSystem->exists($dir);
    });
  }

}
