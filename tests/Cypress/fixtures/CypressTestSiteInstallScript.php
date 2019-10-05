<?php

namespace Drupal\cypress\TestSite;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\Cypress\CypressTest;
use Drupal\TestSite\TestSetupInterface;

class CypressTestSiteInstallScript extends CypressTestSite {

  public function setup() {
    parent::setup();
    /** @var \Drupal\Core\Extension\ModuleInstallerInterface $moduleInstaller */
    $moduleInstaller = \Drupal::service('module_installer');
    $moduleInstaller->install(['language', 'toolbar', 'workspaces']);
    ConfigurableLanguage::createFromLangcode('de')->save();
  }

}
