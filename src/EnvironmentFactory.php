<?php

namespace Drupal\cypress;

/**
 * Factory for configuration data in the environment.
 */
class EnvironmentFactory {

  /**
   * Retrieve the whole environment array.
   *
   * @return array
   */
  public function getEnvironment() {
    return $_SERVER;
  }
}
