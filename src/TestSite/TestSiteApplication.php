<?php

namespace Drupal\cypress\TestSite;

use Drupal\TestSite\Commands\TestSiteReleaseLocksCommand;
use Drupal\TestSite\Commands\TestSiteTearDownCommand;
use Drupal\TestSite\Commands\TestSiteUserLoginCommand;
use Drupal\TestSite\TestSiteApplication as CoreTestSiteApplication;

/**
 * Cypress variant of the TestSiteApplication.
 *
 * Uses a custom install command to enable setup caching.
 */
class TestSiteApplication extends CoreTestSiteApplication {
  /**
   * {@inheritdoc}
   */
  protected function getDefaultCommands() {
    $default_commands = parent::getDefaultCommands();
    $default_commands[] = new TestSiteInstallCommand();
    $default_commands[] = new TestSiteTearDownCommand();
    $default_commands[] = new TestSiteReleaseLocksCommand();
    $default_commands[] = new TestSiteUserLoginCommand();
    return $default_commands;
  }
}
