<?php

namespace Drupal\cypress;

/**
 * Parameter factory to define the cypress root directory.
 */
class CypressRootFactory {

  /**
   * The application root directory.
   *
   * @var string
   */
  protected $appRoot;

  /**
   * CypressRootFactory constructor.
   *
   * @param string $appRoot
   *   The applications root directory.
   */
  public function __construct($appRoot) {
    $this->appRoot = $appRoot;
  }

  /**
   * Retrieve the absolute path to the `.cypress` directory.
   *
   * @return string
   *   The cypress working directory.
   */
  public function get() {
    return $this->appRoot . '/.cypress';
  }
}
