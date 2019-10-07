<?php

namespace Drupal\cypress\TestSite;

use Drupal\TestSite\TestSetupInterface;

/**
 * Interface for cacheable test setup scripts.
 *
 * To speed up test runs, the site directories including databases can cached
 * and reused between runs.
 */
interface CacheableTestSetupInterface extends TestSetupInterface {

  /**
   * Generates an id that is unique for this cache setup.
   *
   * Can be used to automatically invalidate caches in case of setup changes.
   *
   * @return string
   */
  public function getCacheId();

  /**
   * Post cache load setup step.
   *
   * Executed after the site state has been loaded from cache.
   *
   * @return void
   */
  public function postCacheLoad();

}
