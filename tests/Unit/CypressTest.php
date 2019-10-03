<?php

namespace Drupal\Tests\Cypress;

use Drupal\cypress\Cypress;
use Drupal\cypress\CypressOptions;
use Drupal\cypress\CypressRuntimeInterface;
use Drupal\cypress\NpmProjectManagerInterface;
use Drupal\cypress\ProcessManagerInterface;
use Drupal\Tests\UnitTestCase;

class CypressTest extends UnitTestCase {
  protected $cypress;
  protected $options;
  protected $processManager;

  protected function setUp() {
    parent::setUp();
    $this->processManager = $this->prophesize(ProcessManagerInterface::class);
    $npmProjectManager = $this->prophesize(NpmProjectManagerInterface::class);
    $cypressRuntime = $this->prophesize(CypressRuntimeInterface::class);

    $this->cypress = new Cypress(
      $this->processManager->reveal(),
      $npmProjectManager->reveal(),
      $cypressRuntime->reveal(),
      '/app',
      '/app/.cypress',
      [
        'a' => '/app/tests/a',
        'b' => '/app/tests/b',
      ],
      '1.0',
      '1.0'
    );

    $this->options = [
      'tags' => 'foo',
      'spec' => 'bar',
    ];

    $cypressOptions = new CypressOptions($this->options);

    $npmProjectManager->ensureInitiated()->shouldBeCalledOnce();
    $npmProjectManager->ensurePackageVersion('cypress', '1.0')->shouldBeCalledOnce();
    $npmProjectManager->ensurePackageVersion('cypress-cucumber-preprocessor', '1.0')->shouldBeCalledOnce();

    $cypressRuntime->initiate($cypressOptions)->shouldBeCalledOnce();
    $cypressRuntime->addSuite('a', '/app/tests/a')->shouldBeCalledOnce();
    $cypressRuntime->addSuite('b', '/app/tests/b')->shouldBeCalledOnce();
  }

  public function testCypressRun() {
    $this->processManager->run(
      ['/app/node_modules/.bin/cypress', '--spec', 'bar','run'],
      '/app/.cypress',
      ['CYPRESS_TAGS' => 'foo']
    )->shouldBeCalledOnce();
    $this->cypress->run($this->options);
  }

  public function testCypressOpen() {
    $this->processManager->run(
      ['/app/node_modules/.bin/cypress', '--spec', 'bar','open'],
      '/app/.cypress',
      ['CYPRESS_TAGS' => 'foo']
    )->shouldBeCalledOnce();
    $this->cypress->open($this->options);
  }
}
