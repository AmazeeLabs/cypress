<?php

namespace Drupal\cypress\TestSite;

use Drupal\Core\Config\FileStorage;

class CypressTestSetup implements CacheableTestSetupInterface {
  /**
   * {@inheritdoc}
   */
  public function setup() {
    /** @var \Drupal\Core\Extension\ModuleInstallerInterface $moduleInstaller */
    $moduleInstaller = \Drupal::service('module_installer');
    $moduleInstaller->install(['cypress']);

    if ($configDir = getenv('DRUPAL_CONFIG_DIR')) {
      $configStorage = new FileStorage(getenv('DRUPAL_APP_ROOT') . '/' . $configDir);
      /** @var \Drupal\Core\Config\ConfigInstallerInterface $configInstaller */
      $configInstaller = \Drupal::service('config.installer');
      $configInstaller->installOptionalConfig($configStorage);
    }
  }

  /**
   * {@inheritDoc}
   */
  public function getCacheId() {
    return getenv('DRUPAL_CONFIG_DIR');
  }

  /**
   * {@inheritDoc}
   */
  public function postCacheLoad() {
    if ($configDir = getenv('DRUPAL_CONFIG_DIR')) {
      $configStorage = new FileStorage(getenv('DRUPAL_APP_ROOT') . '/' . $configDir);
      /** @var \Drupal\Core\Config\ConfigInstallerInterface $configInstaller */
      $configInstaller = \Drupal::service('config.installer');
      $configInstaller->installOptionalConfig($configStorage);
    }
  }
}
