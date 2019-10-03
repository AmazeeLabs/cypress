<?php

namespace Drupal\cypress;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\language\LanguageNegotiationMethodManager;

/**
 * Enforce the Cypress language negotiation always to be on top.
 */
class ConfigOverrides implements ConfigFactoryOverrideInterface {

  /**
   * The config storage service.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $baseStorage;

  /**
   * The negotiator manager service.
   *
   * @var \Drupal\language\LanguageNegotiationMethodManager|null
   */
  protected $negotiatorManager;

  /**
   * ConfigOverrides constructor.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The config storage service.
   * @param \Drupal\language\LanguageNegotiationMethodManager|null $negotiatorManager
   *   The language negotiation manager service.
   */
  public function __construct(StorageInterface $storage, LanguageNegotiationMethodManager $negotiatorManager = NULL) {
    $this->baseStorage = $storage;
    $this->negotiatorManager = $negotiatorManager;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    if (
      $this->negotiatorManager &&
      in_array('language.types', $names)
      && $this->negotiatorManager->hasDefinition('language-cypress')
      && $config = $this->baseStorage->read('language.types')
    ) {
      foreach (array_keys($config['negotiation']) as $type) {
        $config['negotiation'][$type]['enabled']['language-cypress'] = -999;
        asort($config['negotiation'][$type]['enabled']);
      }

      return ['language.types' => $config];
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'cypress';
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

}
