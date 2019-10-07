<?php

namespace Drupal\cypress\TestSite;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\NodeType;

class CypressTestSiteInstallScript extends CypressTestSetup {

  public function setup() {
    parent::setup();
    /** @var \Drupal\Core\Extension\ModuleInstallerInterface $moduleInstaller */
    $moduleInstaller = \Drupal::service('module_installer');
    $moduleInstaller->install(['language', 'toolbar', 'workspaces', 'node']);
    NodeType::create([
      'type' => 'page',
      'name' => 'Page',
    ])->save();
    ConfigurableLanguage::createFromLangcode('de')->save();
  }

}
