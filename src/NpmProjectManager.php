<?php

namespace Drupal\cypress;

use Composer\Semver\VersionParser;
use Symfony\Component\Filesystem\Filesystem;

/**
 * A shell based project manager implementation.
 */
class NpmProjectManager implements NpmProjectManagerInterface {

  /**
   * The project root directory.
   *
   * @var string
   */
  protected $packageDirectory;

  /**
   * A process manager to invoke commands.
   *
   * @var \Drupal\cypress\ProcessManagerInterface
   */
  protected $processManager;

  /**
   * A filesystem component to access file information.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  protected $fileSystem;

  /**
   * The path to the npm executable.
   *
   * @var string
   */
  protected $npmExecutable;

  /**
   * NpmProjectManager constructor.
   *
   * @param \Drupal\cypress\ProcessManagerInterface $processManager
   *   A process manager to execute npm commands.
   * @param $packageDirectory
   *   The directory to put the package in.
   * @param $npmExecutable
   *   The npm executable to use when executing commands.
   */
  public function __construct(ProcessManagerInterface $processManager, $packageDirectory, $npmExecutable) {
    $this->processManager = $processManager;
    $this->packageDirectory = $packageDirectory;
    $this->npmExecutable = $npmExecutable;
    $this->fileSystem = new Filesystem();
  }

  /**
   * {@inheritDoc}
   */
  public function ensureInitiated() {
    if (!$this->fileSystem->exists($this->packageDirectory)) {
      $this->fileSystem->mkdir($this->packageDirectory);
    }

    if (!$this->fileSystem->exists($this->packageDirectory . '/package.json')) {
      $this->processManager->run([$this->npmExecutable, 'init', '-y'], $this->packageDirectory);
    }

    if (!$this->fileSystem->exists($this->packageDirectory . '/node_modules')) {
      $this->processManager->run([$this->npmExecutable, 'install'], $this->packageDirectory);
    }

    $packageJson = json_decode($this->packageDirectory . '/package.json', TRUE);
    $packageJson['cypress-cucumber-preprocessor']['nonGlobalStepDefinitions'] = TRUE;
    $this->fileSystem->dumpFile($this->packageDirectory . '/package.json', json_encode($packageJson, JSON_PRETTY_PRINT));
  }

  /**
   * {@inheritDoc}
   */
  public function ensurePackageVersion($package, $version) {
    if (!$this->fileSystem->exists($this->packageDirectory . '/package.json')) {
      $this->ensureInitiated();
    }

    $packageJson = $this->packageDirectory . '/node_modules/' . $package . '/package.json';

    if ($this->fileSystem->exists($packageJson)) {
      $constraint = (new VersionParser())->parseConstraints($version);
      $installedVersion = json_decode(file_get_contents($packageJson))->version;
      if ($constraint->matches((new VersionParser())->parseConstraints($installedVersion))) {
        return;
      }
    }

    $this->processManager->run([
      $this->npmExecutable, 'install', $package . '@' . $version
    ], $this->packageDirectory);
  }
}
