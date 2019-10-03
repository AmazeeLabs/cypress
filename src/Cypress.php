<?php

namespace Drupal\cypress;

/**
 * Cypress execution service.
 */
class Cypress implements CypressInterface {

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
   * The cypress version constraint to use.
   *
   * @var string
   */
  protected $cypressVersion;

  /**
   * The cypress cucumber version constraint.
   *
   * @var string
   */
  protected $cypressCucumberVersion;

  /**
   * The path to the cypress executable to use.
   *
   * @var string
   */
  protected $cypressExecutable;

  /**
   * Cypress constructor.
   *
   * @param \Drupal\cypress\ProcessManagerInterface $processManager
   *   A process manager to execute cypress commands.
   * @param \Drupal\cypress\NpmProjectManagerInterface $npmProjectManager
   *   An npm project manager to install cypress and dependencies.
   * @param \Drupal\cypress\CypressRuntimeInterface $cypressRuntime
   *   The cypress runtime manager to add and execute test suites.
   * @param $npmRoot
   *   The npm package directory where dependencies are installed.
   * @param $cypressRoot
   *   The cypress configuration directory.
   * @param array $testDirectories
   *   All discovered test suites.
   * @param $cypressVersion
   *   The cypress version constraint to use.
   * @param $cypressCucumberVersion
   *   The cypress cucumber version constraint.
   */
  public function __construct(
    ProcessManagerInterface $processManager,
    NpmProjectManagerInterface $npmProjectManager,
    CypressRuntimeInterface $cypressRuntime,
    $npmRoot,
    $cypressRoot,
    array $testDirectories,
    $cypressVersion,
    $cypressCucumberVersion
  ) {
    $this->processManager = $processManager;
    $this->npmProjectManager = $npmProjectManager;
    $this->cypressRuntime = $cypressRuntime;
    $this->cypressRoot = $cypressRoot;
    $this->testDirectories = $testDirectories;
    $this->cypressVersion = $cypressVersion;
    $this->cypressCucumberVersion = $cypressCucumberVersion;
    $this->cypressExecutable = $npmRoot . '/node_modules/.bin/cypress';
  }

  /**
   * Pre-command initialisation.
   *
   * @param array $options
   *   The set of cypress options.
   *
   * @return \Drupal\cypress\CypressOptions
   */
  protected function initialise(array $options) {
    $this->npmProjectManager->ensureInitiated();
    $this->npmProjectManager->ensurePackageVersion(
      'cypress',
      $this->cypressVersion
    );
    $this->npmProjectManager->ensurePackageVersion(
      'cypress-cucumber-preprocessor',
      $this->cypressCucumberVersion
    );

    $cypressOptions = new CypressOptions($options);
    $this->cypressRuntime->initiate($cypressOptions);
    foreach ($this->testDirectories as $name => $path) {
      $this->cypressRuntime->addSuite($name, $path);
    }
    return $cypressOptions;
  }

  /**
   * {@inheritDoc}
   */
  public function run($options = []) {
    $cypressOptions = $this->initialise($options);
    $args = $cypressOptions->getCliOptions();
    array_unshift($args, $this->cypressExecutable);
    $args[] = 'run';
    $this->processManager->run($args, $this->cypressRoot, $cypressOptions->getEnvironment());
  }

  /**
   * {@inheritDoc}
   */
  public function open($options = []) {
    $cypressOptions = $this->initialise($options);
    $args = $cypressOptions->getCliOptions();
    array_unshift($args, $this->cypressExecutable);
    $args[] = 'open';
    $this->processManager->run($args, $this->cypressRoot, $cypressOptions->getEnvironment());
  }
}
