<?php

namespace Drupal\cypress;

/**
 * Cypress execution service.
 */
class Cypress implements CypressInterface {

  /**
   * Environment variables to be used with Cypress.
   */
  const ENVIRONMENT_VARIABLES = [
    // Increase Node.js max header size to prevent issues with Drupal's
    // debug_cacheability_headers option which is enabled by
    // \Drupal\Core\Test\FunctionalTestSetupTrait::initSettings() during the
    // Drupal installation.
    'NODE_OPTIONS' => '--max-http-header-size=80000',
  ];

  /**
   * A process manager to execute cypress commands.
   *
   * @var \Drupal\cypress\ProcessManagerInterface
   */
  protected $processManager;

  /**
   * An npm project manager to install cypress and dependencies.
   *
   * @var \Drupal\cypress\NpmProjectManagerInterface
   */
  protected $npmProjectManager;

  /**
   * The cypress runtime manager to add and execute test suites.
   *
   * @var \Drupal\cypress\CypressRuntimeInterface
   */
  protected $cypressRuntime;

  /**
   * The drupal app root.
   *
   * @var string
   */
  protected $appRoot;

  /**
   * The cypress configuration directory.
   *
   * @var string
   */
  protected $cypressRoot;

  /**
   * All discovered test suites.
   *
   * @var string[]
   */
  protected $testDirectories;

  /**
   * The path to the cypress executable to use.
   *
   * @var string
   */
  protected $cypressExecutable;

  /**
   * The path to the drush executable to use.
   *
   * @var string
   */
  protected $drushExecutable;

  /**
   * Cypress constructor.
   *
   * @param \Drupal\cypress\ProcessManagerInterface $processManager
   *   A process manager to execute cypress commands.
   * @param \Drupal\cypress\NpmProjectManagerInterface $npmProjectManager
   *   An npm project manager to install cypress and dependencies.
   * @param \Drupal\cypress\CypressRuntimeInterface $cypressRuntime
   *   The cypress runtime manager to add and execute test suites.
   * @param $appRoot
   *   The Drupal app root directory.
   * @param $npmRoot
   *   The npm package directory where dependencies are installed.
   * @param $cypressRoot
   *   The cypress configuration directory.
   * @param array $testDirectories
   *   All discovered test suites.
   * @param string $drushExecutable
   *   The path to the drush executable.
   */
  public function __construct(
    ProcessManagerInterface $processManager,
    NpmProjectManagerInterface $npmProjectManager,
    CypressRuntimeInterface $cypressRuntime,
    $appRoot,
    $npmRoot,
    $cypressRoot,
    array $testDirectories,
    $drushExecutable
  ) {
    $this->processManager = $processManager;
    $this->npmProjectManager = $npmProjectManager;
    $this->cypressRuntime = $cypressRuntime;
    $this->appRoot = $appRoot;
    $this->cypressRoot = $cypressRoot;
    $this->testDirectories = $testDirectories;
    $this->cypressExecutable = $npmRoot . '/node_modules/.bin/cypress';
    $this->drushExecutable = $drushExecutable;
  }

  /**
   * Pre-command initialisation.
   *
   * @param array $options
   *   The set of cypress options.
   *
   * @return \Drupal\cypress\CypressOptions
   */
  public function init(array $options = []) {
    $this->npmProjectManager->ensureInitiated();

    $cypressOptions = new CypressOptions($options + [
      'appRoot' => $this->appRoot,
      'drush' => $this->drushExecutable,
    ]);
    $this->cypressRuntime->initiate($cypressOptions);
    foreach ($this->testDirectories as $name => $path) {
      $this->cypressRuntime->addSuite($name, $path);
    }
    return $cypressOptions;
  }

  /**
   * {@inheritDoc}
   */
  public function run(array $options = []) {
    $cypressOptions = $this->init($options);
    $args = $cypressOptions->getCliOptions();
    array_unshift($args, 'run');
    array_unshift($args, $this->cypressExecutable);
    $this->processManager->run($args, $this->cypressRoot, self::ENVIRONMENT_VARIABLES);
  }

  /**
   * {@inheritDoc}
   */
  public function open(array $options = []) {
    $cypressOptions = $this->init($options);
    $args = $cypressOptions->getCliOptions();
    array_unshift($args, 'open');
    array_unshift($args, $this->cypressExecutable);
    $this->processManager->run($args, $this->cypressRoot, self::ENVIRONMENT_VARIABLES);
  }
}
