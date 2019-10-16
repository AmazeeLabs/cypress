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
   * Merge in another package.json file.
   *
   * Dependencies will be combined and checked for conflicts. All other content
   * will be simply deeply merged.
   *
   * @param string $file
   *   The full path to the file.
   *
   * @return void
   */
  public function merge($file);

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
