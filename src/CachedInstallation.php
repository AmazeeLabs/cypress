<?php

namespace Drupal\cypress;

use Alchemy\Zippy\Zippy;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Helper class for managing cached test site installs.
 *
 * Tries to optimize installing and updating test sites as much as possible by
 * caching the whole sites directory. Currently this only works for SQLite based
 * installs.
 */
class CachedInstallation {

  /**
   * The Drupal root directory.
   *
   * @var string
   */
  protected $appRoot;

  /**
   * The site directory to install to.
   *
   * @var string
   */
  protected $siteDir;

  /**
   * The current simpletest lock id.
   *
   * @var string
   */
  protected $lockId;

  /**
   * The simpletest database prefix.
   *
   * @var string
   */
  protected $dbPrefix;

  /**
   * The installation profile to use.
   *
   * @var string
   */
  protected $profile = 'minimal';

  /**
   * An optional setup class.
   *
   * Identical to the ones used by nightwatch tests.
   *
   * @var string|null
   */
  protected $setupClass = NULL;

  /**
   * The language to install.
   *
   * @var string
   */
  protected $langCode = 'en';

  /**
   * An optional configuration directory to install from.
   *
   * @var string|null
   */
  protected $configDir = NULL;

  /**
   * The simpletest database url.
   *
   * @var string[]
   */
  protected $dbUrl;

  /**
   * Path to a zip archive with a persistent install cache.
   *
   * @var string|null
   */
  protected $installCache = NULL;

  /**
   * A directory to write cached site installs to.
   *
   * @var string|null
   */
  protected $cacheDir = NULL;


  /**
   * A filesystem instance.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  protected $fs;

  /**
   * CachedInstallation constructor.
   *
   * @param string $appRoot
   *   The path to the Drupal root directory.
   * @param $siteDir
   *   The site directory to use.
   * @param $lockId
   *   The lock id used for this test site install.
   * @param $dbUrl.
   *   The database url
   * @param $dbPrefix
   *   The database prefix to use.
   */
  public function __construct($appRoot, $siteDir, $lockId, $dbUrl, $dbPrefix) {
    $this->fs = new FileSystem();
    $this->appRoot = $appRoot;
    $this->siteDir = $siteDir;
    $this->lockId = $lockId;
    $this->dbUrl = parse_url($dbUrl);
    $this->dbPrefix = $dbPrefix;
  }

  /**
   * Set the installation profile.
   *
   * @param string $profile
   *   The installation profile to use.
   *
   * @return \Drupal\cypress\CachedInstallation
   */
  public function setProfile($profile) {
    $this->profile = $profile;
    return $this;
  }

  /**
   * Set the setup class.
   *
   * @param string $setupClass
   *   The setup class to use.
   *
   * @return \Drupal\cypress\CachedInstallation
   */
  public function setSetupClass($setupClass) {
    $this->setupClass = $setupClass;
    return $this;
  }

  /**
   * Set the installation language.
   *
   * @param string $langCode
   *   The installation language to use.
   *
   * @return \Drupal\cypress\CachedInstallation
   */
  public function setLangCode($langCode) {
    $this->langCode = $langCode;
    return $this;
  }

  /**
   * Set the configuration directory.
   *
   * @param string $configDir
   *   The configuration directory to use.
   *
   * @return \Drupal\cypress\CachedInstallation
   */
  public function setConfigDir($configDir) {
    $this->configDir = $configDir;
    return $this;
  }

  /**
   * Set the install cache url.
   *
   * @param string $installCache
   *   The path to a persistent install cache.
   *
   * @return \Drupal\cypress\CachedInstallation
   */
  public function setInstallCache($installCache) {
    $this->installCache = $installCache;
    return $this;
  }

  /**
   * Set the cache directory.
   *
   * @param string $cacheDir
   *   The path to the cache directory.
   *
   * @return \Drupal\cypress\CachedInstallation
   */
  public function setCacheDir($cacheDir) {
    $this->cacheDir = $cacheDir;
    return $this;
  }

  /**
   * Extract a zip archive to a directory.
   *
   * @param string $archive
   *   The path to the zip archive.
   * @param string $destination
   *   The destination directory.
   */
  protected function zippyExtract($archive, $destination) {
    Zippy::load()->open($archive)->extract($destination);
  }

  /**
   * Compress a list of files into a zip archive.
   *
   * @param string[] $files
   *   The list of files.
   * @param string $archive
   *   The path of the destination archive
   */
  protected function zippyCompress($files, $archive) {
    Zippy::load()->create($archive, $files, TRUE);
  }

  /**
   * Generate a cache id for the install cache.
   *
   * @return string
   */
  public function getInstallCacheId() {
    return md5(serialize([
      $this->profile,
      $this->setupClass,
      $this->langCode,
      $this->configDir,
      $this->installCache && $this->fs->exists($this->appRoot . '/' . $this->installCache)
        ? file_get_contents($this->appRoot . '/' . $this->installCache)
        : ''
    ]));
  }

  /**
   * Generate an update cache id.
   *
   * Will generate a new cache id whenever configuration, update hooks or files
   * that require a cache clear change.
   *
   * @return string
   */
  public function getUpdateCacheId() {
    $cacheId = [];

    if ($this->configDir) {
      // Add all config directory contents to the cache id.
      $finder = new Finder();
      $finder->files()->in($this->configDir);
      foreach ($finder as $file) {
        $cacheId[] = md5(file_get_contents($file->getPath() . '/' . $file->getFilename()));
      }
    }

    // Add all update- or cache-relevant code files to the cache id.
    $finder = new Finder();
    $finder
      ->name('*.theme')
      ->name('*.module')
      ->name('*.install')
      ->name('*.post_update.php')
      ->name('*.yml');

    $finder->files()->in([
      $this->appRoot . '/core',
      $this->appRoot . '/themes',
      $this->appRoot . '/modules',
    ]);

    foreach ($finder as $file) {
      $cacheId[] = md5(file_get_contents($file->getPath() . '/' . $file->getFilename()));
    }

    // Add the install cache, since update caches have to invalidate whenever
    // install caches are refreshed.
    $cacheId[] = $this->getInstallCacheId();
    return md5(serialize($cacheId));
  }

  /**
   * Execute the installation process.
   *
   * Requires two separate callable objects. The first one will be executed for
   * uncached installs. The second one for updating a site install that is
   * loaded from cache.
   *
   * @param callable $install
   *   The initial install procedure.
   * @param callable $update
   *   The update procedure.
   */
  public function install(callable $install, callable $update) {
    // Abort if there is no cache directory or the setup is not cacheable.
    if (!$this->isCacheable() || !$this->cacheDir) {
      $install();
      return;
    }

    $installCacheDir = $this->cacheDir . '/' . $this->getInstallCacheId();
    $updateCacheDir = $this->cacheDir . '/' . $this->getUpdateCacheId();

    // If the update cache exists, just restore from there.
    if ($this->configDir && $this->fs->exists($updateCacheDir)) {
      $this->restoreCache($updateCacheDir);
      return;
    }

    // If the current install is not cached but there is a persistent cache,
    // restore the persistent cache to the current install cache directory.
    if (!$this->fs->exists($installCacheDir) && $this->installCache) {
      $this->restorePersistentCache($installCacheDir);
    }

    // If the current install is cached, restore it and if the install is using
    // a config directory run upgrade commands. Then write the result to the
    // update cache.
    if ($this->fs->exists($installCacheDir)) {
      $this->restoreCache($installCacheDir);
      if ($this->configDir) {
        $update();
        $this->writeCache($updateCacheDir);
      }
      return;
    }

    // No caches available at this point. Run the full install and cache it.
    $install();
    $this->writeCache($installCacheDir);

    // If the install cache archive is configured but doesn't exist, generate
    // it now.
    if ($this->installCache && !$this->fs->exists($this->appRoot . '/' . $this->installCache)) {
      $this->writePersistentCache($installCacheDir);
      // Update the cache directories since existence of a persistent install
      // cache changes the cache ids.
      $installCacheDir = $this->cacheDir . '/' . $this->getInstallCacheId();
      $updateCacheDir = $this->cacheDir . '/' . $this->getUpdateCacheId();
      $this->writeCache($installCacheDir);
    }

    // After a fresh install we never need to run updates so we can populat
    // the update cache directly.
    if ($this->configDir) {
      $this->writeCache($updateCacheDir);
    }
  }

  /**
   * Generate the absolute path to the test site database file.
   *
   * @return string
   */
  protected function dbFile() {
    return $this->appRoot . '/' . $this->dbUrl['path'] . '-' . $this->dbPrefix;
  }

  /**
   * Generate the absolute site directory path.
   *
   * @return string
   */
  protected function sitePath() {
    return $this->appRoot . '/' . $this->siteDir;
  }

  /**
   * Cache the current site directory to a given cache directory.
   *
   * @param string $cacheDir
   *   The cache directory to write to.
   */
  protected function writeCache($cacheDir) {
    $this->copyDir($this->sitePath(), $cacheDir);
    $this->fs->copy($this->dbFile(), $cacheDir . '/files/' . basename($this->dbUrl['path']));
    $settingsFile = file_get_contents($cacheDir . '/settings.php');
    $settingsFile = str_replace($this->lockId, 'LOCK_ID', $settingsFile);
    $settingsFile = str_replace($this->appRoot, 'APP_ROOT', $settingsFile);
    $this->fs->dumpFile($cacheDir . '/settings.php', $settingsFile);
  }

  /**
   * Restore the current site directory from a given cache directory.
   *
   * @param string $cacheDir
   *   The cache directory to restore from.
   */
  protected function restoreCache($cacheDir) {
    $this->copyDir($cacheDir, $this->sitePath());
    $this->fs->copy($cacheDir . '/files/' . basename($this->dbUrl['path']), $this->dbFile());
    $settingsFile = file_get_contents($cacheDir . '/settings.php');
    $settingsFile = str_replace('LOCK_ID', $this->lockId, $settingsFile);
    $settingsFile = str_replace('APP_ROOT', $this->appRoot, $settingsFile);
    $this->fs->dumpFile($this->sitePath() . '/settings.php', $settingsFile);
  }

  /**
   * Write a specific cache directory to the persistent cache archive.
   *
   * @param string $cacheDir
   *   The cache directory to persist.
   */
  protected function writePersistentCache($cacheDir) {
    $files = [];
    $finder = new Finder();
    $finder->files()->in($cacheDir);
    $finder->ignoreDotFiles(FALSE);
    foreach ($finder as $file) {
      $files[$file->getRelativePath() . '/' . $file->getBasename()] = $file->getRealPath();
    }

    $this->zippyCompress($files, $this->appRoot . '/' . $this->installCache);
  }

  /**
   * Restore the persistent cache to a specific cache directory.
   *
   * @param string $cacheDir
   *   The cache directory to restore to.
   */
  protected function restorePersistentCache($cacheDir) {
    if ($this->fs->exists($this->appRoot . '/' . $this->installCache)) {
      $this->fs->mkdir($cacheDir);
      $this->zippyExtract($this->appRoot . '/' . $this->installCache, $cacheDir);
    }
  }

  /**
   * Determines if the setup is cacheable.
   *
   * Right now only SQLite installs can be cached.
   *
   * @return bool
   */
  protected function isCacheable() {
    return $this->dbUrl['scheme'] === 'sqlite';
  }

  /**
   * Copy an entire directory.
   *
   * @param string $source
   *   The source directory.
   * @param $destination
   *   The destination directory.
   */
  protected function copyDir($source, $destination) {
    $finder = new Finder();
    $finder->files()->in($source);
    if (!$this->fs->exists($destination)) {
      $this->fs->mkdir($destination);
    }
    $finder->ignoreDotFiles(FALSE);
    foreach ($finder as $file) {
      $this->fs->copy(
        rtrim($source, '/') . '/' . $file->getRelativePath() . '/' . $file->getFilename(),
        rtrim($destination) . '/' . $file->getRelativePath() . '/' . $file->getFilename()
      );
    }
  }
}
