<?php

namespace Drupal\cypress\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Test entity redirect controller
 *
 * Searches for a given entity by properties, generates the requested url and
 * redirects to it.
 *
 * For simple E2E testing with content fixtures.
 *
 * @package Drupal\cypress\Controller
 */
class EntityController extends ControllerBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * EntityUrlController constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager to search for entities.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * @param string $entity_type
   *   The entity type id.
   * @param string $link_type
   *   The link type.
   *
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function entityRedirect($entity_type, $link_type) {
    if (!cypress_enabled()) {
      throw new NotFoundHttpException();
    }
    $request = \Drupal::request();

    if ($request->query->count() > 0) {
      /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
      $storage = $this->entityTypeManager->getStorage($entity_type);
      $entities = $storage->loadByProperties($request->query->all());
      if (!$entities) {
        throw new NotFoundHttpException();
      }
      $entity = array_pop($entities);
      return new RedirectResponse($entity->toUrl($link_type)->toString());
    }
  }
}
