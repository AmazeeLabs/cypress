<?php

namespace Drupal\Tests\cypress\Unit;

use Drupal\cypress\CypressRootFactory;
use Drupal\Tests\UnitTestCase;

class CypressRootFactoryTest extends UnitTestCase {
  function testCypressRootDirectory() {
    $this->assertEquals('/app/.cypress', (new CypressRootFactory('/app'))->getDirectory());
  }
}
