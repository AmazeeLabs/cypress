<?php

namespace Drupal\cypress;

/**
 * Provide the root of the npm package containing the test runtime.
 *
 * @TODO: Currently this is calculated as the lowest common parent directory
 *        of all test folders. This is due to a problem with the cucumber
 *        preprocessor:
 *        https://github.com/TheBrainFamily/cypress-cucumber-preprocessor/issues/245
 *        After resolution of this issue, the $npmRoot should become equal to
 *        the $cypressRoot, and this class should not be required any more.
 */
class NpmRootFactory {

  /**
   * The application root directory.
   *
   * @var string
   */
  protected $appRoot;

  /**
   * The list of absolute test directories.
   *
   * @var string[]
   */
  protected $directories;

  /**
   * NpmRootFactory constructor.
   *
   * @param $appRoot
   *   The application root directory.
   * @param $directories
   *   A list of absolute path to test directories.
   */
  public function __construct($appRoot, $directories) {
    $this->appRoot = $appRoot;
    $this->directories = $directories;
  }

  /**
   * Retrieve the directory tha should contain the test package.
   *
   * @return string
   *   The absolute path to the npm package.
   */
  public function getDirectory() {
    $current = explode('/', $this->appRoot);
    foreach ($this->directories as $path) {
      $segments = explode('/', $path);
      $common = [];
      for ($i = 0; $i < min(count($current), count($segments)); $i++) {
        if ($current[$i] !== $segments[$i]) {
          break;
        }
        $common[] = $current[$i];
      }
      $current = $common;
    }
    return implode('/', $current);
  }

}
