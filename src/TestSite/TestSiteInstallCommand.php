<?php

namespace Drupal\cypress\TestSite;

use Drupal\cypress\CachedInstallation;
use Drupal\cypress\CypressRootFactory;
use Drupal\TestSite\Commands\TestSiteInstallCommand as CoreTestSiteInstallCommand;
use Symfony\Component\Process\Process;

/**
 * Cypress derivative of the TestSiteInstallCommand.
 */
class TestSiteInstallCommand extends CoreTestSiteInstallCommand {

  /**
   * The Drupal root directory.
   *
   * @var string
   */
  protected $appRoot;

  /**
   * {@inheritDoc}
   */
  public function __construct($name = NULL) {
    parent::__construct($name);
    $this->appRoot = getenv('DRUPAL_APP_ROOT');
  }

  /**
   * {@inheritDoc}
   *
   * Also sets parameters to install from configuration if
   * DRUPAL_CONFIG_DIR is set.
   */
  public function installParameters() {
    $parameters = parent::installParameters();
    if ($configDirectory = getenv('DRUPAL_CONFIG_DIR')) {
      $parameters['parameters']['existing_config'] = TRUE;
      $parameters['config_install_path'] = $configDirectory;
    }
    return $parameters;
  }

  /**
   * {@inheritDoc}
   *
   * Allows to reference setup classes relative to test suites.
   *
   * ```
   * cypress:integration/CypressTestInstallScript.php
   * ```
   */
  protected function getSetupClass($file) {
    if (strpos($file, ':') !== FALSE) {
      list($suite, $path) = explode(':', $file);
      return parent::getSetupClass(
        "drupal-cypress-environment/suites/{$suite}/{$path}"
      );
    }
    return parent::getSetupClass($file);
  }

  /**
   * {@inheritDoc}
   *
   * Uses `CachedInstallation` to add setup caching.
   *
   * @see \Drupal\cypress\CachedInstallation
   */
  public function setup($profile = 'testing', $setup_class = NULL, $langcode = 'en') {
    // Optionally turn of strict config checking.
    $this->strictConfigSchema = getenv('DRUPAL_CONFIG_CHECK') !== 'false';

    $this->profile = $profile;
    $this->langcode = $langcode;
    $this->setupBaseUrl();
    $this->prepareEnvironment();


    $setup_class = $setup_class ?? '\Drupal\cypress\TestSite\CypressTestSetup';
    $lockId = substr($this->databasePrefix, 4);

    $cachedInstallation = new CachedInstallation(
      getenv('DRUPAL_APP_ROOT'),
      $this->siteDirectory,
      $lockId,
      getenv('SIMPLETEST_DB'),
      $this->databasePrefix
    );

    $cacheDir = implode(
      '/',
      [
        getenv('DRUPAL_APP_ROOT'),
        CypressRootFactory::CYPRESS_ROOT_DIRECTORY,
        'cache',
      ]
    );

    $cachedInstallation
      ->setProfile($profile)
      ->setLangCode($langcode)
      ->setCacheDir($cacheDir)
      ->setInstallCache(getenv('DRUPAL_INSTALL_CACHE'))
      ->setConfigDir(getenv('DRUPAL_CONFIG_DIR'))
      ->setSetupClass($setup_class);

    /** @var \Drupal\TestSite\TestSetupInterface $setupScript */
    $setupScript = new $setup_class();

    $cachedInstallation->install(
      function () use ($setupScript) {
        $this->installDrupal();
        $setupScript->setup();
      },
      function () {
        $user_agent = drupal_generate_test_ua($this->databasePrefix);
        foreach ([
                   ['vendor/bin/drush', 'cr'],
                   ['vendor/bin/drush', 'updb', '-y'],
                   ['vendor/bin/drush', 'cim', '-y'],
                   ['vendor/bin/drush', 'cr', '-y'],
                 ] as $command) {
          (new Process(
            $command, getenv('DRUPAL_APP_ROOT'), [
              'HTTP_USER_AGENT' => $user_agent
            ] + $_SERVER
          ))->mustRun();
        }
      }
    );
  }

}
