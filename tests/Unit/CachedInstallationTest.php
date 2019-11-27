<?php

namespace Drupal\Tests\cypress\Unit;

use Drupal\cypress\CachedInstallation;
use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use Symfony\Component\Filesystem\Filesystem;

interface Procedure {
  public function __invoke();
}

class CachedInstallationTest extends UnitTestCase {

  /**
   * @var callable
   */
  protected $installer;

  /**
   * @var \Prophecy\Prophecy\MethodProphecy
   */
  protected $installProcedure;

  /**
   * @var callable
   */
  protected $updater;

  /**
   * @var \Prophecy\Prophecy\MethodProphecy
   */
  protected $updateProcedure;

  /**
   * The Drupal root directory.
   *
   * @var string
   */
  protected $appRoot;

  /**
   * @var \Drupal\cypress\CachedInstallation
   */
  protected $cachedInstall;

  protected function setUp() {
    $vfs = vfsStream::setup('app');
    $this->appRoot = vfsStream::create([
      'config' => [
        'a' => [
          'foo.yml' => 'foo',
        ],
        'b' => [
          'bar.yml' => 'bar',
        ],
      ],
      'drupal' => [
        'core' => [],
        'themes' => [
          'y' => [
            'y.theme' => 'foo',
            'y.libraries.yml' => 'foo',
          ],
        ],
        'modules' => [
          'x' => [
            'x.module' => 'foo',
            'x.install' => 'foo',
            'x.post_update.php' => 'foo',
            'x.routing.yml' => 'foo',
          ],
        ],
        'sites' => [
        ],
      ],
    ], $vfs)->url();

    $appRoot = $this->appRoot;

    $installer = $this->prophesize(Procedure::class);
    $this->installProcedure = $installer->__invoke()->will(function () use ($appRoot) {
      $fs = new Filesystem();
      $fs->mkdir($appRoot . '/drupal/sites/simpletest/123/files');
      $fs->mkdir($appRoot . '/drupal/sites/default/files');

      file_put_contents(
        $appRoot . '/drupal/sites/simpletest/123/settings.php',
        '123 ' . $appRoot . '/drupal'
      );

      file_put_contents(
        $appRoot . '/drupal/sites/default/files/.sqlite-test123',
        'foo'
      );
    });
    $this->installer = $installer->reveal();

    $updater = $this->prophesize(Procedure::class);
    $this->updateProcedure = $updater->__invoke();
    $this->updater = $updater->reveal();

    $this->cachedInstall = new class(
      $this->appRoot . '/drupal',
      'sites/simpletest/123',
      '123',
      'sqlite://localhost/sites/default/files/.sqlite',
      'test123'
    ) extends CachedInstallation {

      protected function zippyCompress($files, $destination) {
        file_put_contents(
          $this->appRoot . '/' . $this->installCache,
          'bar'
        );
      }

      protected function zippyExtract($archive, $destination) {
        file_put_contents(
          $destination . '/settings.php',
          'LOCK_ID APP_ROOT'
        );

        (new Filesystem())->mkdir($destination  . '/files');

        file_put_contents(
          $destination . '/files/.sqlite',
          'foo'
        );
      }

    };
    $this->cachedInstall->setCacheDir($this->appRoot . '/cache');
    $this->cachedInstall->setConfigDir($this->appRoot . '/config/a');
    parent::setUp();
  }

  protected function assertSiteInstalled() {
    $this->assertStringEqualsFile(
      $this->appRoot . '/drupal/sites/default/files/.sqlite-test123',
      'foo'
    );

    $this->assertStringEqualsFile(
      $this->appRoot . '/drupal/sites/simpletest/123/settings.php',
      '123 ' . $this->appRoot . '/drupal'
    );
  }

  protected function assertSiteCached($cacheId) {
    $cacheDir = $this->appRoot . '/cache/' . $cacheId;
    $this->assertStringEqualsFile(
      $cacheDir . '/files/.sqlite',
      'foo'
    );

    $this->assertStringEqualsFile(
      $cacheDir . '/settings.php',
      'LOCK_ID APP_ROOT'
    );
  }

  protected function assertSiteNotCached($cacheId) {
    $this->assertDirectoryNotExists(
      $this->appRoot . '/cache/' . $cacheId
    );
  }

  /**
   * Installation should not fail if no cache directory is set.
   */
  public function testNoCacheDir() {
    $this->installProcedure->shouldBeCalledOnce();
    $this->updateProcedure->shouldNotBeCalled();
    $this->cachedInstall
      ->setCacheDir(NULL)
      ->install($this->installer, $this->updater);

    $this->assertSiteInstalled();
    $this->assertSiteNotCached($this->cachedInstall->getInstallCacheId());
    $this->assertSiteNotCached($this->cachedInstall->getUpdateCacheId());
  }

  /**
   * Test uncacheable install.
   *
   * If the setup is not cacheable the whole procedure is execute and no
   * cache directory is created.
   */
  public function testUncacheableInstall() {
    $this->installProcedure->shouldBeCalledOnce();
    $this->updateProcedure->shouldNotBeCalled();

    $this->cachedInstall = new CachedInstallation(
      $this->appRoot . '/drupal',
      'sites/default',
      'LOCK',
      'mysql://localhost:3306#drupal',
      'PREFIX'
    );
    $this->cachedInstall->setCacheDir($this->appRoot . '/cache');
    $this->cachedInstall->setConfigDir($this->appRoot . '/config');

    $this->cachedInstall->install($this->installer, $this->updater);

    $this->assertSiteInstalled();
    $this->assertSiteNotCached($this->cachedInstall->getInstallCacheId());
    $this->assertSiteNotCached($this->cachedInstall->getUpdateCacheId());
  }

  /**
   * Test install without configuration.
   *
   * If there is no config directory, only execute the install procedure and
   * cache it accordingly.
   */
  public function testCacheableInstallWithoutConfig() {
    $this->installProcedure->shouldBeCalledOnce();
    $this->updateProcedure->shouldNotBeCalled();

    $this->cachedInstall
      ->setConfigDir(NULL)
      ->install($this->installer, $this->updater);
    $this->cachedInstall->install($this->installer, $this->updater);

    $this->assertSiteInstalled();
    $this->assertSiteCached($this->cachedInstall->getInstallCacheId());
    $this->assertSiteNotCached($this->cachedInstall->getUpdateCacheId());
  }

  /**
   * Test install with configuration.
   *
   * Should effectively create two cached directories. One for the installation
   * and one for the update.
   *
   */
  public function testCacheableInstallWithConfig() {
    $this->installProcedure->shouldBeCalledOnce();
    $this->updateProcedure->shouldNotBeCalled();

    $this->cachedInstall->install($this->installer, $this->updater);
    $this->cachedInstall->install($this->installer, $this->updater);

    $this->assertSiteInstalled();
    $this->assertSiteCached($this->cachedInstall->getInstallCacheId());
    $this->assertSiteCached($this->cachedInstall->getUpdateCacheId());
  }

  /**
   * Test writing of the persistent cache.
   */
  public function testWriteToPersistentCache() {
    $this->installProcedure->shouldBeCalledOnce();
    $this->updateProcedure->shouldNotBeCalled();

    $this->cachedInstall
      ->setInstallCache('../install-cache.zip')
      ->install($this->installer, $this->updater);

    $this->assertSiteInstalled();
    $this->assertSiteCached($this->cachedInstall->getInstallCacheId());
    $this->assertSiteCached($this->cachedInstall->getUpdateCacheId());
    $this->assertFileExists($this->appRoot . '/install-cache.zip');
  }

  /**
   * Test loading from the persistent cache.
   */
  public function testRestoreFromPersistentCache() {
    $this->installProcedure->shouldNotBeCalled();
    $this->updateProcedure->shouldBeCalledOnce();

    file_put_contents($this->appRoot . '/install-cache.zip', 'foo');
    $this->cachedInstall
      ->setInstallCache('../install-cache.zip')
      ->install($this->installer, $this->updater);

    $this->assertSiteInstalled();
    $this->assertSiteCached($this->cachedInstall->getInstallCacheId());
    $this->assertSiteCached($this->cachedInstall->getUpdateCacheId());
  }

  /**
   * Test profile change.
   *
   * If the install profile is different, a entirely new site install should
   * be invoked.
   */
  public function testProfileChange() {
    $this->installProcedure->shouldBeCalledTimes(2);
    $this->updateProcedure->shouldNotBeCalled();

    $this->cachedInstall->install($this->installer, $this->updater);
    $this->cachedInstall->setProfile('standard');
    $this->cachedInstall->install($this->installer, $this->updater);

    $this->assertSiteInstalled();
    $this->assertSiteCached($this->cachedInstall->getInstallCacheId());
    $this->assertSiteCached($this->cachedInstall->getUpdateCacheId());
  }

  /**
   * Test config directory change.
   *
   * If the config directory is different, a entirely new site install should
   * be invoked.
   */
  public function testConfigDirChange() {
    $this->installProcedure->shouldBeCalledTimes(2);
    $this->updateProcedure->shouldNotBeCalled();

    $this->cachedInstall->install($this->installer, $this->updater);
    $this->cachedInstall->setConfigDir($this->appRoot . '/config/b');
    $this->cachedInstall->install($this->installer, $this->updater);

    $this->assertSiteInstalled();
    $this->assertSiteCached($this->cachedInstall->getInstallCacheId());
    $this->assertSiteCached($this->cachedInstall->getUpdateCacheId());
  }

  /**
   * Test language change.
   *
   * If the install language is different, a entirely new site install should
   * be invoked.
   */
  public function testLangCodeChange() {
    $this->installProcedure->shouldBeCalledTimes(2);
    $this->updateProcedure->shouldNotBeCalled();

    $this->cachedInstall->install($this->installer, $this->updater);
    $this->cachedInstall->setLangCode('de');
    $this->cachedInstall->install($this->installer, $this->updater);

    $this->assertSiteInstalled();
    $this->assertSiteCached($this->cachedInstall->getInstallCacheId());
    $this->assertSiteCached($this->cachedInstall->getUpdateCacheId());
  }

  /**
   * Test setup class change.
   *
   * If the setup class is different, a entirely new site install should
   * be invoked.
   */
  public function testSetupClassChange() {
    $this->installProcedure->shouldBeCalledTimes(2);
    $this->updateProcedure->shouldNotBeCalled();

    $this->cachedInstall->install($this->installer, $this->updater);
    $this->cachedInstall->setSetupClass('bar');
    $this->cachedInstall->install($this->installer, $this->updater);

    $this->assertSiteInstalled();
    $this->assertSiteCached($this->cachedInstall->getInstallCacheId());
    $this->assertSiteCached($this->cachedInstall->getUpdateCacheId());
  }

  /**
   * Test config change.
   *
   * If the contents of the configuration directory change, an update should be
   * invoked.
   */
  public function testConfigChange() {
    $this->installProcedure->shouldBeCalledOnce();
    $this->updateProcedure->shouldBeCalledOnce();

    $this->cachedInstall->install($this->installer, $this->updater);
    file_put_contents($this->appRoot . '/config/a/foo.yml', 'bar');
    $this->cachedInstall->install($this->installer, $this->updater);

    $this->assertSiteInstalled();
    $this->assertSiteCached($this->cachedInstall->getInstallCacheId());
    $this->assertSiteCached($this->cachedInstall->getUpdateCacheId());
  }

  /**
   * Test update hook changes.
   *
   * If the contents of a *.install, *.post_update.php or *.yml files change,
   * an update should be invoked.
   */
  public function testInstallFileChanges() {
    $this->installProcedure->shouldBeCalledOnce();
    $this->updateProcedure->shouldBeCalledOnce();

    $this->cachedInstall->install($this->installer, $this->updater);
    file_put_contents($this->appRoot . '/drupal/modules/x/x.install', 'bar');
    $this->cachedInstall->install($this->installer, $this->updater);

    $this->assertSiteInstalled();
    $this->assertSiteCached($this->cachedInstall->getInstallCacheId());
    $this->assertSiteCached($this->cachedInstall->getUpdateCacheId());
  }

  /**
   * Test update hook changes.
   *
   * If the contents of a *.install, *.post_update.php or *.yml files change,
   * an update should be invoked.
   */
  public function testUpdateFileChanges() {
    $this->installProcedure->shouldBeCalledOnce();
    $this->updateProcedure->shouldBeCalledOnce();

    $this->cachedInstall->install($this->installer, $this->updater);
    file_put_contents($this->appRoot . '/drupal/modules/x/x.post_update.php', 'bar');
    $this->cachedInstall->install($this->installer, $this->updater);

    $this->assertSiteInstalled();
    $this->assertSiteCached($this->cachedInstall->getInstallCacheId());
    $this->assertSiteCached($this->cachedInstall->getUpdateCacheId());
  }

  /**
   * Test *.yml changes.
   *
   * If the contents of a *.install, *.post_update.php or *.yml files change,
   * an update should be invoked.
   */
  public function testYmlFileChanges() {
    $this->installProcedure->shouldBeCalledOnce();
    $this->updateProcedure->shouldBeCalledOnce();

    $this->cachedInstall->install($this->installer, $this->updater);
    file_put_contents($this->appRoot . '/drupal/modules/x/x.routing.yml', 'bar');
    $this->cachedInstall->install($this->installer, $this->updater);

    $this->assertSiteInstalled();
    $this->assertSiteCached($this->cachedInstall->getInstallCacheId());
    $this->assertSiteCached($this->cachedInstall->getUpdateCacheId());
  }

  /**
   * Test code changes changes.
   *
   * If any other
   */
  public function testCodeChanges() {
    $this->installProcedure->shouldBeCalledOnce();
    $this->updateProcedure->shouldBeCalledOnce();

    $this->cachedInstall->install($this->installer, $this->updater);
    file_put_contents($this->appRoot . '/config/a/foo.yml', 'bar');
    $this->cachedInstall->install($this->installer, $this->updater);

    $this->assertSiteInstalled();
    $this->assertSiteCached($this->cachedInstall->getInstallCacheId());
    $this->assertSiteCached($this->cachedInstall->getUpdateCacheId());
  }

}
