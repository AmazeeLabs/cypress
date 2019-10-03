<?php

namespace Drupal\Tests\cypress\Unit;

use Drupal\cypress\CypressOptions;
use Drupal\cypress\CypressRootFactory;
use Drupal\cypress\CypressRuntime;
use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use Symfony\Component\Filesystem\Filesystem;

class CypressRuntimeTest extends UnitTestCase {

  /**
   * @var \org\bovigo\vfs\vfsStreamDirectory
   */
  protected $fileSystem;

  /**
   * @var string
   */
  protected $cypressRoot;

  /**
   * @var \Drupal\cypress\CypressRuntime
   */
  protected $cypressRuntime;

  /**
   * @var \Drupal\cypress\CypressOptions
   */
  protected $cypressOptions;

  protected function setUp() {
    parent::setUp();
    $this->fileSystem = vfsStream::setup();
    $this->cypressRoot = $this->fileSystem->url() . '/' . CypressRootFactory::CYPRESS_ROOT_DIRECTORY;
    $this->cypressOptions = new CypressOptions();
    $this->cypressRuntime = new class ($this->cypressRoot) extends CypressRuntime {
      protected function getFileSystem() {
        return new class extends Filesystem {
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
        };
      }
    };
  }

  public function testCreateCypressDirectory() {
    $this->cypressRuntime->initiate($this->cypressOptions);
    $this->assertDirectoryExists($this->cypressRoot);
  }

  public function testClearDirectories() {
    vfsStream::create([
      CypressRootFactory::CYPRESS_ROOT_DIRECTORY => [
        'integration' => [ 'foo' => 'bar' ],
        'fixtures' => [ 'foo' => 'bar' ],
      ]
    ]);
    $this->cypressRuntime->initiate($this->cypressOptions);
    $this->assertFileNotExists($this->cypressRoot . '/integration/foo');
    $this->assertFileNotExists($this->cypressRoot . '/fixtures/foo');
  }

  public function testGenerateCypressJson() {
    $this->cypressRuntime->initiate($this->cypressOptions);
    $this->assertStringEqualsFile($this->cypressRoot . '/cypress.json', $this->cypressOptions->getCypressJson());
  }

  public function testGeneratePluginsJs() {
    $this->cypressRuntime->initiate($this->cypressOptions);
    $this->assertFileExists($this->cypressRoot . '/plugins.js');
  }

  public function testGenerateSupportJs() {
    $this->cypressRuntime->initiate($this->cypressOptions);
    $this->assertFileExists($this->cypressRoot . '/support.js');
  }

  public function testAddTestSuite() {
    vfsStream::create([
      'a' => [
        'integration' => [
          'foo' => 'bar',
        ],
        'steps' => [
          'foo' => 'bar',
        ],
        'fixtures' => [
          'foo' => 'bar',
        ],
        'support' => [
          'index.js' => 'bar',
        ],
      ],
    ], $this->fileSystem);
    $this->cypressRuntime->initiate($this->cypressOptions);
    $this->cypressRuntime->addSuite('a', $this->fileSystem->url() . '/a');

    $this->assertStringEqualsFile($this->cypressRoot . '/integration/a/foo', 'bar');
    $this->assertStringEqualsFile($this->cypressRoot . '/fixtures/a/foo', 'bar');
    $this->assertStringEqualsFile($this->cypressRoot . '/integration/common/a/foo', 'bar');
    $this->assertStringEqualsFile(
      $this->cypressRoot . '/support.js',
      "// Automatically generated by the Cypress module for Drupal.
require('vfs://root/a/support/index.js');"
    );
  }

}
