<?php

namespace Drupal\cypress;

/**
 * Interface for the cypress management service.
 */
interface CypressInterface {

  /**
   * Run one specific or all test suites.
   *
   * @param array $options
   *   A dictionary of cypress options.
   *
   * @see \Drupal\cypress\CypressOptions
   *
   * @return void
   */
  public function run($options = []);

  /**
   * Open the Cypress user interface to run tests interactively.
   * @param array $options
   *   A dictionary of cypress options.
   *
   * @see \Drupal\cypress\CypressOptions
   *
   * @return void
   */
  public function open($options = []);
}
