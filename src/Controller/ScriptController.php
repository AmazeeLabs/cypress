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

    $suite = FALSE;
    $path = $content->script;
    if (strpos($path, ':') !== FALSE) {
      list($suite, $path) = explode(':', $content->script);
    }

    if ($suite) {
      $suites = \Drupal::getContainer()->get('cypress.test_directories');
      if (!array_key_exists($suite, $suites)) {
        return new Response('Unknown test suite "' . $suite . '".', 404);
      }
      $path = $suites[$suite] . '/' . $path;

    }
    if (!file_exists($path)) {
      return new Response('File "' . $path . '" not found in suite "' . $suite . ' (' . $suites[$suite] . ')".', 404);
    }

    $args = $content->args ?? [];

    ob_start();
    try {
      include $path;
    }
    catch (\Throwable $e) {
      return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
    }
    $response = ob_get_clean();

    return new Response($response);
  }

}
