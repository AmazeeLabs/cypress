<?php

namespace Drupal\cypress\Authentication\Provider;

use Drupal\Core\Authentication\AuthenticationProviderFilterInterface;
use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class CypressAuthenticationProvider implements AuthenticationProviderInterface, AuthenticationProviderFilterInterface {

  /**
   * The user storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $userStorage;

  /**
   * LocaleWorkspaceNegotiator constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->userStorage = $entity_type_manager->getStorage('user');
  }

  /**
   * {@inheritDoc}
   */
  public function applies(Request $request) {
    return cypress_enabled() && $request->headers->has('X-CYPRESS-USER');
  }

  /**
   * {@inheritDoc}
   */
  public function authenticate(Request $request) {
    $matches = $this->userStorage->loadByProperties([
      'name' => $request->headers->get('X-CYPRESS-USER'),
    ]);
    return $matches ? array_pop($matches) : NULL;
  }

  /**
   * {@inheritDoc}
   */
  public function appliesToRoutedRequest(Request $request, $authenticated) {
    return cypress_enabled() && $request->headers->has('X-CYPRESS-USER');
  }

}
