<?php

namespace Drupal\cypress;

use Composer\Semver\VersionParser;
use Drupal\Component\Utility\NestedArray;
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
  public function merge($file) {
    $packageJson = json_decode(file_get_contents($this->packageDirectory . '/package.json'), TRUE);
    $dependencies = $packageJson['dependencies'] ?? [];

    $merge = json_decode(file_get_contents($file), TRUE);
    if (array_key_exists('dependencies', $merge)) {
      foreach ($merge['dependencies'] as $package => $version) {
        if (isset($dependencies[$package])) {
          $currentConstraint = (new VersionParser())->parseConstraints($dependencies[$package]);
          $newConstraint = (new VersionParser())->parseConstraints($version);
          // The new constraint is already covered by the current constraint.
          // Don't adjust versions.
          if ($newConstraint->matches($currentConstraint) && $newConstraint < $currentConstraint) {
            continue;
          }
          if (!($newConstraint->matches($currentConstraint) || $currentConstraint->matches($newConstraint))) {
            throw new \Exception("Incompatible versions of package '$package': $version / {$dependencies[$package]}");
          }
        }
        $this->ensurePackageVersion($package, $version);
      }
    }

    unset($merge['dependencies']);
    // Re-read package.json since it could be modified by ensurePackageVersion.
    $packageJson = json_decode(file_get_contents($this->packageDirectory . '/package.json'), TRUE);
    $packageJson = NestedArray::mergeDeep($packageJson, $merge);

    $this->fileSystem->dumpFile(
      $this->packageDirectory . '/package.json',
      json_encode($packageJson, JSON_PRETTY_PRINT)
    );
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
