<?php

namespace Drupal\cypress\Plugin\LanguageNegotiation;

use Drupal\language\LanguageNegotiationMethodBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Language negotiator for easy E2E testing.
 *
 * @LanguageNegotiation(
 *   id = "language-cypress",
 *   weight = -9999,
 *   name = @Translation("Cypress headers"),
 *   description = @Translation("Use the language defined by the X-CYPRESS-LANGUAGE header.")
 * )
 */
class CypressLanguageNegotiation extends LanguageNegotiationMethodBase {

  /**
   * {@inheritDoc}
   */
  public function getLangcode(Request $request = NULL) {
    return (cypress_enabled() && $request) ? $request->headers->get('X-CYPRESS-LANGUAGE', FALSE) : FALSE;
  }

}
