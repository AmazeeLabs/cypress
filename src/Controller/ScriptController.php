<?php

namespace Drupal\cypress\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Execute arbitrary PHP scripts from cypress.
 */
class ScriptController extends ControllerBase {

  /**
   * Execute arbitrary PHP scripts from cypress.
   */
  public function execute() {
    if (!cypress_enabled()) {
      throw new NotFoundHttpException();
    }
    $content = json_decode(\Drupal::request()->getContent());
    if (!$content || !$content->script) {
       new Response('Request body has to be JSON and has to contain at least the "script" key.', 400);
    }

    $url = parse_url($content->script);
    $suite = $url['scheme'];
    $path = $url['path'];

    $suites = \Drupal::getContainer()->get('cypress.realpath.test_directories');
    $args = $content->args ?? [];

    if (!array_key_exists($suite, $suites)) {
      return new Response('Unknown test suite "' . $suite . '".', 404);
    }
    if (!file_exists($suites[$suite] . '/' . $path)) {
      return new Response('File "' . $path . '" not found in suite "' . $suite . ' (' . $suites[$suite] . ')".', 404);
    }

    ob_start();
    include $suites[$suite] . '/' . $path;
    $response = ob_get_clean();

    return new Response($response);
  }

}
