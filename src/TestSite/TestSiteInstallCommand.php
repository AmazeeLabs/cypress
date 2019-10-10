<?php

namespace Drupal\cypress\TestSite;

use Alchemy\Zippy\Zippy;
use Drupal\cypress\CypressRootFactory;
use Drupal\TestSite\Commands\TestSiteInstallCommand as CoreTestSiteInstallCommand;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Cypress derivative of the TestSiteInstallCommand.
 */
class TestSiteInstallCommand extends CoreTestSiteInstallCommand {

  /**
   * A instance of the Filesystem component.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  protected $fileSystem;

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
    $this->fileSystem = new Filesystem();
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
   * Adds setup caching.
   */
  public function setup($profile = 'testing', $setup_class = NULL, $langcode = 'en') {
    $dbUrl = parse_url(getenv('SIMPLETEST_DB'));
    // Currently cached tests setups are only supported with sqlite.
    if ($dbUrl['scheme'] !== 'sqlite') {
      return parent::setup($profile, $setup_class, $langcode);
    }

    if (!$setup_class) {
      $setup_class = '\Drupal\cypress\TestSite\CypressTestSetup';
    }

    $installCache = getenv('DRUPAL_INSTALL_CACHE')
      ? $this->appRoot . '/' . getenv('DRUPAL_INSTALL_CACHE')
      : FALSE;

    $this->profile = $profile;
    $this->langcode = $langcode;
    $this->setupBaseUrl();
    $this->prepareEnvironment();

    $lockId = substr($this->databasePrefix, 4);

    /** @var \Drupal\TestSite\TestSetupInterface $setupScript */
    $setupScript = new $setup_class();

    $cid = md5(serialize([
      $profile,
      $setup_class,
      $langcode,
      getenv('DRUPAL_CONFIG_DIR') ?? '',
      $installCache,
    ]));
    $cacheDir = implode('/', [
      getenv('DRUPAL_APP_ROOT'),
      CypressRootFactory::CYPRESS_ROOT_DIRECTORY,
      'cache',
      $cid,
    ]);

    $zippy = Zippy::load();

    if (
      $installCache
      && !$this->fileSystem->exists($cacheDir)
      && $this->fileSystem->exists($installCache)
    ) {
      $this->fileSystem->mkdir($cacheDir);
      $cache = $zippy->open($installCache);
      $cache->extract($cacheDir);
    }

    $siteDir = $this->appRoot . '/' . $this->siteDirectory;

    $dbFile = $this->appRoot . $dbUrl['path'] . '-' . $this->databasePrefix;

    if (!$this->fileSystem->exists($cacheDir)) {
      $siteDir = $this->appRoot . '/' . $this->siteDirectory;
      $this->installDrupal();
      if ($setup_class) {
        $setupScript->setup();
      }
      $this->copyDir($siteDir, $cacheDir);
      $this->fileSystem->copy($dbFile, $cacheDir . '/files/' . basename($dbUrl['path']));

      $settingsFile = file_get_contents($cacheDir . '/settings.php');
      // When writing to cache, replace the database prefix with a pattern that
      // we can find on cache restore.
      $settingsFile = str_replace($lockId, 'LOCK_ID', $settingsFile);
      // Replace any absolute paths with a token.
      $settingsFile = str_replace($this->appRoot, 'APP_ROOT', $settingsFile);

      $this->fileSystem->dumpFile($cacheDir . '/settings.php', $settingsFile);

      if ($installCache) {
        $files = [];
        $finder = new Finder();
        $finder->files()->in($cacheDir);
        $finder->ignoreDotFiles(FALSE);
        foreach ($finder as $file) {
          $files[$file->getRelativePath() . '/' . $file->getBasename()] = $file->getRealPath();
        }

        $zippy->create($installCache, $files, TRUE);
      }
    }
    else {
      $this->copyDir($cacheDir, $siteDir);
      if ($this->fileSystem->exists($dbFile)) {
        $this->fileSystem->remove($dbFile);
      }
      $this->fileSystem->copy($cacheDir . '/files/' . basename($dbUrl['path']), $dbFile);

      $settingsFile = file_get_contents($cacheDir . '/settings.php');
      $settingsFile = str_replace('LOCK_ID', $lockId, $settingsFile);
      $settingsFile = str_replace('APP_ROOT', $this->appRoot, $settingsFile);
      $this->fileSystem->dumpFile($siteDir . '/settings.php', $settingsFile);
    }
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
    $finder->ignoreDotFiles(FALSE);
    foreach ($finder as $file) {
      $this->fileSystem->copy(
        rtrim($source, '/') . '/' . $file->getRelativePath() . '/' . $file->getFilename(),
        rtrim($destination) . '/' . $file->getRelativePath() . '/' . $file->getFilename()
      );
    }
  }
}
