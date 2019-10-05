<?php

namespace Drupal\cypress;

/**
 * Interface for a service executing shell commands.
 */
interface ProcessManagerInterface {

  /**
   * Execute a command in a given environment.
   *
   * Output is as a side effect and is not part of this interface.
   *
   * @param array $commandLine
   *   The command line as list of words.
   * @param $workingDirectory
   *   The directory to execute the command in.
   * @param array $environment
   *   A set of environment overrides.
   *
   * @return void
   */
  public function run(array $commandLine, $workingDirectory, $environment = []);
}
