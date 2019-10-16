<?php

namespace Drupal\Tests\cypress\Unit;

use Drupal\cypress\NpmProjectManager;
use Drupal\cypress\ProcessManagerInterface;
use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use Prophecy\Argument;

class NpmProjectManagerTest extends UnitTestCase {
  protected $processManager;
  protected $npmProjectManager;
  protected $fileSystem;
  protected $packageDirectory;

  protected function setUp() {
    parent::setUp();
    $this->fileSystem = vfsStream::setup();
    $this->processManager = $this->prophesize(ProcessManagerInterface::class);
    $this->packageDirectory = $this->fileSystem->url() . '/drupal';
    $this->npmProjectManager = new NpmProjectManager(
      $this->processManager->reveal(),
      $this->packageDirectory,
      'npm'
    );
  }

  public function testNothingExists() {
    $this->processManager->run(['npm', 'init', '-y'], $this->packageDirectory)->shouldBeCalledOnce();
    $this->processManager->run(['npm', 'install'], $this->packageDirectory)->shouldBeCalledOnce();
    $this->npmProjectManager->ensureInitiated();
    $this->assertDirectoryExists($this->packageDirectory);
  }

  public function testDirectoryExists() {
    vfsStream::create([
      'drupal' => [],
    ], $this->fileSystem);
    $this->processManager->run(['npm', 'init', '-y'], $this->packageDirectory)->shouldBeCalledOnce();
    $this->processManager->run(['npm', 'install'], $this->packageDirectory)->shouldBeCalledOnce();
    $this->npmProjectManager->ensureInitiated();
    $this->assertDirectoryExists($this->packageDirectory);
  }

  public function testPackageJsonExists() {
    vfsStream::create([
      'drupal' => [
        'package.json' => '{}'
      ],
    ], $this->fileSystem);
    $this->processManager->run(['npm', 'init', '-y'], $this->packageDirectory)->shouldNotBeCalled();
    $this->processManager->run(['npm', 'install'], $this->packageDirectory)->shouldBeCalledOnce();
    $this->npmProjectManager->ensureInitiated();
    $this->assertDirectoryExists($this->packageDirectory);
  }

  public function testNodeModulesExists() {
    vfsStream::create([
      'drupal' => [
        'package.json' => '{}',
        'node_modules' => [],
      ],
    ], $this->fileSystem);
    $this->processManager->run(['npm', 'init', '-y'], $this->packageDirectory)->shouldNotBeCalled();
    $this->processManager->run(['npm', 'install'], $this->packageDirectory)->shouldNotBeCalled();
    $this->npmProjectManager->ensureInitiated();
    $this->assertDirectoryExists($this->packageDirectory);
  }

  public function testPackageMissing() {
    vfsStream::create([
      'drupal' => [
        'package.json' => '{}',
        'node_modules' => [],
      ],
    ], $this->fileSystem);
    $this->processManager->run(['npm', 'init', '-y'], $this->packageDirectory)->shouldNotBeCalled();
    $this->processManager->run(['npm', 'install'], $this->packageDirectory)->shouldNotBeCalled();
    $this->processManager->run(['npm', 'install', 'foo@1.0.0'], $this->packageDirectory)->shouldBeCalledOnce();
    $this->npmProjectManager->ensurePackageVersion('foo', '1.0.0');
    $this->assertDirectoryExists($this->packageDirectory);
  }

  public function testPackageMatches() {
    vfsStream::create([
      'drupal' => [
        'package.json' => '{}',
        'node_modules' => [
          'foo' => [
            'package.json' => '{"version": "1.0.0"}',
          ]
        ],
      ],
    ], $this->fileSystem);
    $this->processManager->run(['npm', 'init', '-y'], $this->packageDirectory)->shouldNotBeCalled();
    $this->processManager->run(['npm', 'install'], $this->packageDirectory)->shouldNotBeCalled();
    $this->processManager->run(['npm', 'install', 'foo@1.0.0'], $this->packageDirectory)->shouldNotBeCalled();
    $this->npmProjectManager->ensurePackageVersion('foo', '1.0.0');
    $this->assertDirectoryExists($this->packageDirectory);
  }

  public function testPackageMisses() {
    vfsStream::create([
      'drupal' => [
        'package.json' => '{}',
        'node_modules' => [
          'foo' => [
            'package.json' => '{"version": "1.0.0"}',
          ]
        ],
      ],
    ], $this->fileSystem);
    $this->processManager->run(['npm', 'init', '-y'], $this->packageDirectory)->shouldNotBeCalled();
    $this->processManager->run(['npm', 'install'], $this->packageDirectory)->shouldNotBeCalled();
    $this->processManager->run(['npm', 'install', 'foo@2.0.0'], $this->packageDirectory)->shouldBeCalledOnce();
    $this->npmProjectManager->ensurePackageVersion('foo', '2.0.0');
    $this->assertDirectoryExists($this->packageDirectory);
  }

  public function testPackageFuzzyMatches() {
    vfsStream::create([
      'drupal' => [
        'package.json' => '{}',
        'node_modules' => [
          'foo' => [
            'package.json' => '{"version": "1.2.0"}',
          ]
        ],
      ],
    ], $this->fileSystem);
    $this->processManager->run(['npm', 'init', '-y'], $this->packageDirectory)->shouldNotBeCalled();
    $this->processManager->run(['npm', 'install'], $this->packageDirectory)->shouldNotBeCalled();
    $this->processManager->run(['npm', 'install', 'foo@^1.1.0'], $this->packageDirectory)->shouldNotBeCalled();
    $this->npmProjectManager->ensurePackageVersion('foo', '^1.1.0');
    $this->assertDirectoryExists($this->packageDirectory);
  }

  public function testPackageFuzzyMisses() {
    vfsStream::create([
      'drupal' => [
        'package.json' => '{}',
        'node_modules' => [
          'foo' => [
            'package.json' => '{"version": "1.1.0"}',
          ]
        ],
      ],
    ], $this->fileSystem);
    $this->processManager->run(['npm', 'init', '-y'], $this->packageDirectory)->shouldNotBeCalled();
    $this->processManager->run(['npm', 'install'], $this->packageDirectory)->shouldNotBeCalled();
    $this->processManager->run(['npm', 'install', 'foo@^1.2.0'], $this->packageDirectory)->shouldBeCalledOnce();
    $this->npmProjectManager->ensurePackageVersion('foo', '^1.2.0');
    $this->assertDirectoryExists($this->packageDirectory);
  }

  public function testEmptyMerge() {
    vfsStream::create([
      'drupal' => [
        'package.json' => '{}',
        'node_modules' => [],
        'foo' => [
          'package.json' => '{"version": "1.1.0"}',
        ]
      ],
    ], $this->fileSystem);

    $this->processManager->run(Argument::any(), $this->packageDirectory)->shouldNotBeCalled();
    $this->npmProjectManager->merge($this->packageDirectory . '/foo/package.json');
  }

  public function testNewDependency() {
    vfsStream::create([
      'drupal' => [
        'package.json' => '{}',
        'node_modules' => [],
        'foo' => [
          'package.json' => '{"version": "1.1.0","dependencies":{"foo":"^1.1.0"}}',
        ]
      ],
    ], $this->fileSystem);

    $this->processManager->run(['npm', 'install', 'foo@^1.1.0'], $this->packageDirectory)->shouldBeCalledOnce();
    $this->npmProjectManager->merge($this->packageDirectory . '/foo/package.json');
  }

  public function testMatchingDependency() {
    vfsStream::create([
      'drupal' => [
        'package.json' => '{"dependencies":{"foo":"^1.2.0"}}',
        'node_modules' => [],
        'foo' => [
          'package.json' => '{"version": "1.1.0","dependencies":{"foo":"^1.1.0"}}',
        ]
      ],
    ], $this->fileSystem);

    $this->processManager->run(['npm', 'install', 'foo@^1.1.0'], $this->packageDirectory)->shouldNotBeCalled();
    $this->npmProjectManager->merge($this->packageDirectory . '/foo/package.json');
  }

  public function testDependencyUpdate() {
    vfsStream::create([
      'drupal' => [
        'package.json' => '{"dependencies":{"foo":"^1.1.0"}}',
        'node_modules' => [],
        'foo' => [
          'package.json' => '{"version": "1.1.0","dependencies":{"foo":"^1.2.0"}}',
        ]
      ],
    ], $this->fileSystem);

    $this->processManager->run(['npm', 'install', 'foo@^1.2.0'], $this->packageDirectory)->shouldBeCalledOnce();
    $this->npmProjectManager->merge($this->packageDirectory . '/foo/package.json');
  }

  public function testDependencyConflict() {
    vfsStream::create([
      'drupal' => [
        'package.json' => '{"dependencies":{"foo":"1.1.0"}}',
        'node_modules' => [],
        'foo' => [
          'package.json' => '{"version": "1.1.0","dependencies":{"foo":"1.2.0"}}',
        ]
      ],
    ], $this->fileSystem);

    $this->expectExceptionMessage("Incompatible versions of package 'foo': 1.2.0 / 1.1.0");
    $this->npmProjectManager->merge($this->packageDirectory . '/foo/package.json');
  }

  public function testNewSettings() {
    vfsStream::create([
      'drupal' => [
        'package.json' => '{}',
        'node_modules' => [],
        'foo' => [
          'package.json' => '{"foo":"bar"}',
        ]
      ],
    ], $this->fileSystem);

    $this->npmProjectManager->merge($this->packageDirectory . '/foo/package.json');

    $this->assertJsonStringEqualsJsonFile($this->packageDirectory . '/package.json', json_encode([
      'foo' => 'bar'
    ]));
  }

  public function testSettingsUpdate() {
    vfsStream::create([
      'drupal' => [
        'package.json' => '{"foo":"bar"}',
        'node_modules' => [],
        'foo' => [
          'package.json' => '{"foo":"baz"}',
        ]
      ],
    ], $this->fileSystem);

    $this->npmProjectManager->merge($this->packageDirectory . '/foo/package.json');

    $this->assertJsonStringEqualsJsonFile($this->packageDirectory . '/package.json', json_encode([
      'foo' => 'baz'
    ]));
  }
}
