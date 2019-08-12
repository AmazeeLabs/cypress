<?php

namespace Drupal\cypress\Authentication\Provider;

use Drupal\Core\Authentication\AuthenticationProviderFilterInterface;
use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CypressAuthenticationProvider implements AuthenticationProviderInterface, AuthenticationProviderFilterInterface {

  /**
   * The user storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $userStorage;

  /**
   * @var
   */
  protected $session;

  /**
   * LocaleWorkspaceNegotiator constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, SessionInterface $session) {
    $this->session = $session;
    $this->userStorage = $entity_type_manager->getStorage('user');
  }

  /**
   * {@inheritDoc}
   */
  public function applies(Request $request) {
    return cypress_enabled() && ($this->session->has('CYPRESS_USER') || $request->headers->has('X-CYPRESS-USER'));
  }

  protected function getUserFromRequest($request) {
    return $request->headers->get('X-CYPRESS-USER') ?: $this->session->get('CYPRESS_USER');
  }

  /**
   * {@inheritDoc}
   */
  public function authenticate(Request $request) {
    $matches = $this->userStorage->loadByProperties([
      'name' => $this->getUserFromRequest($request),
    ]);
    return $matches ? array_pop($matches) : NULL;
  }

  /**
   * {@inheritDoc}
   */
  public function appliesToRoutedRequest(Request $request, $authenticated) {
    return cypress_enabled() && ($this->session->has('CYPRESS_USER') || $request->headers->has('X-CYPRESS-USER'));
  }

}
