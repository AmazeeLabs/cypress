<?php

namespace Drupal\cypress;

/**
 * Generate the Cypress root path based on the application root directory.
 */
class CypressRootFactory {

  /**
   * The cypress directory path below the application directory.
   */
  const CYPRESS_ROOT_DIRECTORY = 'drupal-cypress-environment';

  /**
   * The applications root directory.
   *
   * @var string
   */
  protected $appRoot;

  /**
   * CypressRootFactory constructor.
   *
   * @param string $appRoot
   *   The application root directory.
   */
  public function __construct($appRoot) {
    $this->appRoot = $appRoot;
  }

  /**
   * Retrieve the Cypress root directory.
   *
   * @return string
   *   The absolute path to the Cypress root directory.
   */
  public function getDirectory() {
    return $this->appRoot . '/' . static::CYPRESS_ROOT_DIRECTORY;
  }

}
