<?php

namespace Drupal\Tests\cypress\Unit\Commands;

use Consolidation\Log\Logger;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\cypress\Commands\CypressCommands;
use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * Virtual file system.
 */
class VfsFileSystem extends Filesystem {

  /**
   * {@inheritDoc)
   *
   * Use `mirror` instead of symlink since the latter is not supported
   * by vfsStream.
   */
  public function symlink($originDir, $targetDir, $copyOnWindows = FALSE) {
    $this->mirror($originDir, $targetDir);
    return TRUE;
  }
}

class CypressCommandsTest extends UnitTestCase {

  /**
   * @var \Symfony\Component\Process\Process
   */
  protected $npmInstall;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var string
   */
  protected $appRoot;

  /**
   * @var string
   */
  protected $cypressRoot;

  /**
   * {@inheritDoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->npmInstall = $this->prophesize(Process::class);
    $this->moduleHandler = $this->prophesize(ModuleHandlerInterface::class);
  }

  /**
   * Prepare the Commands object.
   *
   * @param array $content
   *   Directory contents to be digested by `vfsStream::create()`.
   *
   * @return \Drupal\cypress\Commands\CypressCommands
   *   The commands object, ready to be executed.
   */
  protected function prepareCommands(array $content) {
    $vfs = vfsStream::setup('app');
    $this->appRoot = vfsStream::create($content, $vfs)->url();
    $this->cypressRoot = $this->appRoot . '/.cypress';

    $npmInstall = $this->npmInstall->reveal();
    $moduleHandler = $this->moduleHandler->reveal();

    assert($npmInstall instanceof Process);
    assert($moduleHandler instanceof ModuleHandlerInterface);

    // A module without a cypress folder.
    vfsStream::create([
      'module_a' => [
        'tests' => [
          'Cypress' => [
            'package.json' => '{}',
            'support' => [
              'index.js' => '// TEST',
            ],
            'plugins' => [
              'index.js' => '// TEST',
            ],
          ],
        ],
      ],
    ], $vfs);
    $moduleAInfo = $this->prophesize(Extension::class);
    $moduleAInfo->getPath()->willReturn('module_a');

    // A module with a cypress integration folder.
    vfsStream::create([
      'module_b' => [
        'tests' => [
          'Cypress' => [
            'integration' => [],
            'steps' => [],
          ],
        ],
      ],
    ], $vfs);
    $moduleBInfo = $this->prophesize(Extension::class);
    $moduleBInfo->getPath()->willReturn('module_b');

    // Make sure the module handler returns something.
    $this->moduleHandler->getModuleList()->willReturn([
      'module_a' => $moduleAInfo->reveal(),
      'module_b' => $moduleBInfo->reveal(),
    ]);

    // An additional folder with test implementations.
    vfsStream::create([
      'extra' => [
        'integration' => [],
      ],
    ], $vfs);

    $command = new CypressCommands(
      $vfs->url(),
      $vfs->url() . '/.cypress',
      $npmInstall,
      $this->prophesize(Process::class)->reveal(),
      $this->prophesize(Process::class)->reveal(),
      $moduleHandler,
      new VfsFileSystem(),
      ['extra' => './extra']
    );
    $logger = new Logger(new NullOutput());
    $command->setLogger($logger);
    return $command;
  }

  /**
   * The init command creates a new `.cypress` directory in the app root.
   */
  public function testCreateCypressDirectory() {
    $this->prepareCommands([])->init();
    $this->assertDirectoryExists($this->cypressRoot);
  }

  /**
   * The init command should not fail if the `.cypress` directory exists.
   */
  public function testCypressDirectoryExists() {
    $this->prepareCommands([
      '.cypress' => [],
    ])->init();
    $this->assertDirectoryExists($this->cypressRoot);
  }

  /**
   * A new `package.json` is created in the `.cypress` directory.
   */
  public function testCreatePackageJson() {
    $this->prepareCommands([])->init();
    $this->assertFileExists($this->cypressRoot . '/package.json');
    $packageJson = json_decode(file_get_contents($this->cypressRoot . '/package.json'));
    $this->assertEquals('cypress-runtime', $packageJson->name);
  }

  /**
   * If a package.json exists, it will be updated.
   */
  public function testUpdatePackageJson() {
    $this->prepareCommands([
      '.cypress' => [
        'package.json' => '{"name":"foobar"}',
      ],
    ])->init();
    $this->assertFileExists($this->cypressRoot . '/package.json');
    $packageJson = json_decode(file_get_contents($this->cypressRoot . '/package.json'));
    $this->assertEquals('cypress-runtime', $packageJson->name);
  }

  /**
   * Whenever package.json is update, package-lock.json is removed.
   */
  public function testRemovePackageLockJson() {
    $this->prepareCommands([
      '.cypress' => [
        'package.json' => '{"name":"foobar"}',
        'package-lock.json' => '{}',
      ],
    ])->init();
    $this->assertFileNotExists($this->cypressRoot . '/package-lock.json');
  }

  /**
   * If package.json is created immediately run npm install.
   */
  public function testNpmInstallOnCreatePackageJson() {
    $commands = $this->prepareCommands([
      '.cypress' => [
      ],
    ]);
    $this->npmInstall->mustRun()->shouldBeCalledOnce();
    $commands->init();
  }

  /**
   * If package.json is updated immediately run npm install.
   */
  public function testNpmInstallOnUpdatePackageJson() {
    $commands = $this->prepareCommands([
      '.cypress' => [
        'package.json' => '{"name":"foobar"}',
      ],
    ]);
    $this->npmInstall->mustRun()->shouldBeCalledOnce();
    $commands->init();
  }

  /**
   * If `node_modules` is missing `npm install` is run.
   */
  public function testNpmInstallIfNodeModulesMissing() {
    $commands = $this->prepareCommands([
      '.cypress' => [
      ],
    ]);
    $this->npmInstall->mustRun()->shouldBeCalledTimes(2);
    $commands->init();
    $commands->init();
  }

  /**
   * If everything is up to date, don't run `npm install`.
   */
  public function testNpmInstallOnlyIfNecessary() {
    $commands = $this->prepareCommands([
      '.cypress' => [
        'node_modules' => [],
      ],
    ]);
    $this->npmInstall->mustRun()->shouldBeCalledTimes(1);
    $commands->init();
    $commands->init();
  }

  /**
   * Integration folders from modules and test folders are linked.
   */
  public function testLinkIntegrationFolders() {
    $this->prepareCommands([
      '.cypress' => [
        'integration' => [
          'foo' => [],
        ],
      ],
    ])->init();

    $this->assertDirectoryNotExists($this->cypressRoot . '/integration/foo');
    $this->assertDirectoryNotExists($this->cypressRoot . '/integration/module_a');
    $this->assertDirectoryExists($this->cypressRoot . '/integration/module_b');
    $this->assertDirectoryExists($this->cypressRoot . '/integration/extra');
  }

  /**
   * Reusable step definitions are linked to '`integration/common`.
   */
  public function testLinkStepDefinitions() {
    $this->prepareCommands([])->init();
    $this->assertDirectoryExists($this->cypressRoot . '/integration/common/module_b');
  }

  /**
   * Support files are linked and imported into the central `support/index.js`.
   */
  public function testLinkSupportFiles() {
    $this->prepareCommands([])->init();
    $this->assertFileExists($this->cypressRoot . '/support.js');
    $this->assertEquals(implode("\n", [
      '// Automatically generated by `drush cypress:init`.',
      'require(\'drupal-cypress-module_a/support/index.js\');',
    ]), file_get_contents($this->cypressRoot . '/support.js'));
  }

  /**
   * Plugin files are linked and imported into the central `plugins/index.js`.
   */
  public function testLinkPluginFiles() {
    $this->prepareCommands([])->init();
    $this->assertFileExists($this->cypressRoot . '/plugins.js');
    $this->assertEquals(implode("\n", [
      '// Automatically generated by `drush cypress:init`.',
      'module.exports = (on, config) => {',
      '  require(\'drupal-cypress-module_a/plugins/index.js\')(on, config);',
      '};'
    ]), file_get_contents($this->cypressRoot . '/plugins.js'));
  }

  /**
   * package.json files are linked and imported into the central `package.json`.
   */
  public function testLinkDependencies() {
    $this->prepareCommands([])->init();
    $this->assertFileExists($this->cypressRoot . '/package.json');
    $packageJson = json_decode(file_get_contents($this->cypressRoot . '/package.json'), TRUE);
    $this->assertArrayEquals([
      'drupal-cypress-module_a' => 'file:' . $this->appRoot . '/module_a/tests/Cypress',
      'cypress' => CypressCommands::$CYPRESS_VERSION,
    ], $packageJson['dependencies']);
  }

}
