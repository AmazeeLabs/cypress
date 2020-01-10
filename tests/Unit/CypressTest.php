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
      '/app',
      '/app/drupal-cypress-environment',
      [
        'a' => '/app/tests/a',
        'b' => '/app/tests/b',
      ],
      '1.0',
      '1.0',
      'drush'
    );

    $this->options = [
      'tags' => 'foo',
      'spec' => 'bar',
      'appRoot' => '/app',
      'drush' => 'drush',
    ];

    $cypressOptions = new CypressOptions($this->options);

    $npmProjectManager->ensureInitiated()->shouldBeCalledOnce();

    $cypressRuntime->initiate($cypressOptions)->shouldBeCalledOnce();
    $cypressRuntime->addSuite('a', '/app/tests/a')->shouldBeCalledOnce();
    $cypressRuntime->addSuite('b', '/app/tests/b')->shouldBeCalledOnce();
  }

  public function testCypressRun() {
    $this->processManager->run(
      ['/app/node_modules/.bin/cypress', 'run', '--spec', 'bar'],
      '/app/drupal-cypress-environment',
      Cypress::ENVIRONMENT_VARIABLES
    )->shouldBeCalledOnce();
    $this->cypress->run($this->options);
  }

  public function testCypressOpen() {
    $this->processManager->run(
      ['/app/node_modules/.bin/cypress', 'open', '--spec', 'bar'],
      '/app/drupal-cypress-environment',
      Cypress::ENVIRONMENT_VARIABLES
    )->shouldBeCalledOnce();
    $this->cypress->open($this->options);
  }
}
