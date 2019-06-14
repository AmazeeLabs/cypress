<?php

namespace Drupal\cypress\Negotiator;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\workspaces\Negotiator\WorkspaceNegotiatorInterface;
use Drupal\workspaces\WorkspaceInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Development workspace negotiator.
 *
 * Negotiate the workspace by listening to a Cypress request header.
 *
 * @package Drupal\cypress\Negotiator
 */
class CypressWorkspaceNegotiator implements WorkspaceNegotiatorInterface {

  /**
   * The workspace storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $workspaceStorage;

  /**
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
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
    $this->workspaceStorage = $entity_type_manager->getStorage('workspace');
  }

  /**
   * {@inheritDoc}
   */
  public function applies(Request $request) {
    return cypress_enabled() && ($this->session->has('CYPRESS_WORKSPACE') || $request->headers->has('X-CYPRESS-WORKSPACE'));
  }

  /**
   * {@inheritDoc}
   */
  public function getActiveWorkspace(Request $request) {
    return $this->workspaceStorage->load($this->session->get('CYPRESS_WORKSPACE') ?: $request->headers->get('X-CYPRESS-WORKSPACE'));
  }

  /**
   * {@inheritDoc}
   */
  public function setActiveWorkspace(WorkspaceInterface $workspace) {}

}
