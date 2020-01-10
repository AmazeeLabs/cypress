<?php

namespace Drupal\cypress;

use Consolidation\SiteProcess\Util\Tty;
use Symfony\Component\Process\Process;

/**
 * Process manager that attempts to stream output to TTY.
 */
class TtyProcessManager implements ProcessManagerInterface {

  /**
   * {@inheritDoc}
   */
  public function run(array $commandLine, $workingDirectory, $environment = []) {
    $process = new Process($commandLine, $workingDirectory);
    $process->inheritEnvironmentVariables(TRUE);
    $process->setTty(Tty::isTtySupported());
    $process->setTimeout(0.0);
    $process->setIdleTimeout(0.0);
    $process->mustRun();
  }
}
