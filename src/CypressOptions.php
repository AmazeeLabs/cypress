<?php

namespace Drupal\cypress;

/**
 * Bag for Cypress runtime options.
 */
class CypressOptions {

  /**
   * Fixed configuration options that will always take precedence.
   */
  const FIXED = [
    'testFiles' => '**/**/*.feature',
    'ignoreTestFiles' => ['*.js', '*.php'],
    'integrationFolder' => 'integration',
    'pluginsFile' => 'plugins.js',
    'supportFile' => 'support.js',
  ];

  /**
   * Default options that can be overridden by the user.
   */
  const DEFAULT = [
    'baseUrl' => 'http://localhost:8888',
    'video' => FALSE,
    'watchForFileChanges' => FALSE,
  ];

  const ENVIRONMENT = [
    'tags' => 'TAGS',
    'modulePath' => 'CYPRESS_MODULE_PATH',
    'appRoot' => 'DRUPAL_APP_ROOT',
    'drush' => 'DRUPAL_DRUSH',
  ];

  const CLI = [
    'spec' => 'spec',
  ];

  /**
   * The actual runtime options.
   *
   * @var array
   */
  protected $options;

  /**
   * CypressOptions constructor.
   *
   * @param array $options
   *   A list of options.
   */
  public function __construct(array $options = []) {
    $this->options = array_merge(
      static::DEFAULT,
      $options,
      ['modulePath' => realpath(__DIR__ . '/..')],
      self::FIXED
    );
  }

  /**
   * Retrieve the full set of options.
   *
   * @return array
   *   The calculated set of options.
   */
  public function getOptions() {
    return $this->options;
  }

  /**
   * Generate content for a `cypress.json` configuration file.
   *
   * @return string
   *   The content for a `cypress.json` file reflecting these options.
   */
  public function getCypressJson() {
    return json_encode(array_reduce(array_map(function ($key) {
      return [$key => $this->options[$key]];
    }, array_filter(array_keys($this->options), function ($key) {
      return !(
        array_key_exists($key, static::CLI)
        || array_key_exists($key, static::ENVIRONMENT)
      );
    })), 'array_merge', [
      'env' => $this->getEnvironment(),
    ]), JSON_PRETTY_PRINT);
  }

  /**
   * Retrieve the cli options.
   *
   * @return string[]
   *    List of cli options to be used with `Process`.
   */
  public function getCliOptions() {
    return array_reduce(array_map(function ($key) {
      return array_key_exists($key, $this->options)
        ? ['--' . static::CLI[$key], $this->options[$key]]
        : [];
    }, array_keys(static::CLI)), 'array_merge', []);
  }

  /**
   * Get an array of environment variables to set.
   */
  public function getEnvironment() {
    return array_reduce(array_map(function ($key) {
      return array_key_exists($key, $this->options)
        ? [static::ENVIRONMENT[$key] => $this->options[$key]]
        : [];
    }, array_keys(static::ENVIRONMENT)), 'array_merge', []);
  }

}
