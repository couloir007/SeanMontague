<?php

declare(strict_types=1);

namespace Drupal\paragraphs_usage\Routing;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Registers a route for generic usage local tasks for entities.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Constructs a new RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config factory service.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, ConfigFactoryInterface $config) {
    $this->entityTypeManager = $entity_manager;
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    /** @var  \Drupal\Core\Entity\EntityTypeInterface $entity_type */
    $entity_type = $this->entityTypeManager->getDefinition('paragraphs_type');

    $template = '';
    if ($entity_type->hasLinkTemplate('canonical')) {
      $template = $entity_type->getLinkTemplate('canonical');
    }
    elseif ($entity_type->hasLinkTemplate('edit-form')) {
      $template = $entity_type->getLinkTemplate('edit-form');
    }
    $options = [
      '_admin_route' => TRUE,
      'parameters' => [
        'paragraphs_type' => [
          'type' => 'entity:' . $entity_type->id(),
        ],
      ],
    ];

    $route = new Route(
      $template . '/usage',
      [
        '_controller' => '\Drupal\paragraphs_usage\Controller\ParagraphsUsageController::getUsage',
        '_title' => 'Usage',
      ],
      [
        '_permission' => 'administer paragraphs types',
      ],
      $options
    );

    $collection->add("entity.paragraphs_type.paragraphs_usage", $route);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', 20];
    return $events;
  }

}
