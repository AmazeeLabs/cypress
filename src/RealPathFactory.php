<?php

namespace Drupal\cypress;

/**
 * Apply `realpath` to a list of directories.
 */
class RealPathFactory {

  /**
   * The list of paths to transform.
   *
   * @var string[]
   */
  protected $paths;

  /**
   * RealPathFactory constructor.
   *
   * @param array $paths
   *   The list of paths to transform.
   */
  public function __construct(array $paths) {
    $this->paths = $paths;
  }

  /**
   * Retrieve the list of real paths.
   *
   * Results where `realpath` failed (e.g. directory doesn't exist) are already
   * filtered.
   *
   * @return string[]
   *   The list of absolute real system paths.
   */
  public function getPaths() {
    return array_filter(array_map('realpath', $this->paths));
  }
}
