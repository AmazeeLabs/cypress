<?php

namespace Drupal\Tests\cypress\Unit;

use Drupal\cypress\CypressOptions;
use Drupal\Tests\UnitTestCase;

class CypressOptionsTest extends UnitTestCase {

  public function testDefaultOption() {
    $this->assertEquals(CypressOptions::DEFAULT['baseUrl'], (new CypressOptions())->getOptions()['baseUrl']);
  }

  public function testOptionOverride() {
    $this->assertEquals('http://localhost:8889', (new CypressOptions([
      'baseUrl' => 'http://localhost:8889',
    ]))->getOptions()['baseUrl']);
  }

  public function testFixedOption() {
    $this->assertEquals(CypressOptions::FIXED['integrationFolder'], (new CypressOptions([
      'integrationFolder' => 'foo',
    ]))->getOptions()['integrationFolder']);
  }

  public function testCypressJson() {
    $overrides = [
      'baseUrl' => 'http://localhost:8889',
      'trashAssetsBeforeRuns' => TRUE,
      // CLI or ENVIRONMENT options should not be added to cypress.json
      'spec' => 'foo',
      'tags' => '@bar',
    ];
    $this->assertJsonStringEqualsJsonString(json_encode(array_merge(
      CypressOptions::DEFAULT,
      [
        'baseUrl' => 'http://localhost:8889',
        'trashAssetsBeforeRuns' => TRUE,
      ],
      CypressOptions::FIXED
    ), JSON_PRETTY_PRINT), (new CypressOptions($overrides))->getCypressJson());
  }

  public function testCliOptions() {
    $this->assertEquals(['--spec', 'foo'],  (new CypressOptions([
      'spec' => 'foo'
    ]))->getCliOptions());
  }

  public function testEnvironment() {
    $this->assertEquals(['CYPRESS_TAGS' => '@bar'],  (new CypressOptions([
      'tags' => '@bar'
    ]))->getEnvironment());
  }
}
