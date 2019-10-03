<?php

namespace Drupal\cypress;

/**
 * Management interface for a cypress runtime.
 */
interface CypressRuntimeInterface {

  /**
   * Initiate the runtime with a given set of options.
   *
   * @param \Drupal\cypress\CypressOptions $options
   *   A cypress options object.
   */
  public function initiate(CypressOptions $options);

  /**
   * Add a test suite to the current runtime.
   *
   * @param $name
   *   The test suite machine name.
   * @param $path
   *   The absolute path to the test suite directory.
   *
   * @return void
   */
  public function addSuite($name, $path);
}
