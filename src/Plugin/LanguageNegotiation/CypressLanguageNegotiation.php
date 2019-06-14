<?php

namespace Drupal\cypress\Plugin\LanguageNegotiation;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\language\LanguageNegotiationMethodBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

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
class CypressLanguageNegotiation extends LanguageNegotiationMethodBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * CypressLanguageNegotiation constructor.
   *
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   */
  public function __construct(SessionInterface $session) {
    $this->session = $session;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($container->get('session'));
  }

  /**
   * {@inheritDoc}
   */
  public function getLangcode(Request $request = NULL) {
    return (cypress_enabled() && $request) ? ($this->session->get('CYPRESS_LANGUAGE', FALSE) ?: $request->headers->get('X-CYPRESS-LANGUAGE')) : FALSE;
  }

}
