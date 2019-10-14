<?php

namespace Drupal\cypress;

/**
 * Interface for the cypress management service.
 */
interface CypressInterface {
  /**
   * Initialise the Cypress environment.
   *
   * @param array $options
   *   A dictionary of cypress options.
   *
   * @see \Drupal\cypress\CypressOptions
   *
   * @return void
   */
  public function init(array $options = []);

  /**
   * Run one specific or all test suites.
   *
   * Implicitly runs `init`.
   *
   * @param array $options
   *   A dictionary of cypress options.
   *
   * @see \Drupal\cypress\CypressOptions
   *
   * @return void
   */
  public function run(array $options = []);

  /**
   * Open the Cypress user interface to run tests interactively.
   *
   * Implicitly runs `init`.
   *
   * @param array $options
   *   A dictionary of cypress options.
   *
   * @see \Drupal\cypress\CypressOptions
   *
   * @return void
   */
  public function open(array $options = []);
}
