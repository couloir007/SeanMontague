<?php

namespace Drupal\entity_embed\Controller;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Redirects from an embedded entity to its edit form.
 */
class EditEmbeddedEntity extends ControllerBase {

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityRepository = $container->get('entity.repository');
    return $instance;
  }

  /**
   * Redirects to an entity edit form based on its type and uuid.
   *
   * @param string $type
   *   The entity type.
   * @param string $uuid
   *   The entity uuid.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect destination response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   When the entity type doesn't exist or has no UUIDs, no entity has the
   *   UUID (for example, because it was deleted), or the entity type has no
   *   edit form.
   */
  public function edit(string $type, string $uuid): RedirectResponse {
    try {
      $entity = $this->entityRepository->loadEntityByUuid($type, $uuid);
    }
    catch (PluginNotFoundException | EntityStorageException) {
      $entity = NULL;
    }
    // @todo Fall back to other links in
    //   https://www.drupal.org/project/entity_embed/issues/3416512.
    if (!$entity || !$entity->hasLinkTemplate('edit-form')) {
      throw new NotFoundHttpException();
    }
    $url = $entity->toUrl('edit-form');
    return $this->redirect($url->getRouteName(), $url->getRouteParameters());
  }

}
