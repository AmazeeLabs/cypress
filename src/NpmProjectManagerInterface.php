<?php

namespace Drupal\cypress;

/**
 * Local node project management.
 *
 * Ensure initialisation and dependency versions of a local package.
 */
interface NpmProjectManagerInterface {

  /**
   * Ensure that the package has been initialised.
   */
  public function ensureInitiated();

  /**
   * Ensure a package is installed in a given version.
   *
   * @param $package
   *   The npm package name.
   * @param $version
   *   The version string. Only strict versions supported.
   */
  public function ensurePackageVersion($package, $version);
}
