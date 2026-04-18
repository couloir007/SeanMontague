<?php

namespace Drupal\content_model_documentation\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides local task definitions for entity diagram tabs.
 */
class DiagramLocalTab extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a DiagramLocalTab object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($entity_type_id === 'node_type' || $entity_type_id === 'taxonomy_vocabulary') {
        $base_route = $this->getBaseRoute($entity_type_id);
        $this->derivatives["$entity_type_id.diagram_tab"] = [
          'route_name' => "entity.{$entity_type_id}.diagram",
          'title' => 'Entity Relationships',
          'base_route' => $base_route,
          'weight' => 19,
          'route_parameters' => [
            'max_depth' => '2',
          ],
        ];
      }
    }
    foreach ($this->derivatives as &$entry) {
      $entry += $base_plugin_definition;
    }
    return $this->derivatives;
  }

  /**
   * Gets the base route to use for the entity in question.
   *
   * @param string $entity_type
   *   The machine name of the entity.
   *
   * @return string
   *   The vase route for the entity.
   */
  protected function getBaseRoute(string $entity_type): string {
    switch (TRUE) {
      case ($entity_type === 'node_type'):
        // Content type edit /admin/structure/types/manage/BUNDLENAME.
        $base_route = "entity.{$entity_type}.edit_form";
        break;

      case ($entity_type === 'taxonomy_vocabulary'):
        // Vocabulary edit /admin/structure/taxonomy/manage/VOCABULARYNAME.
        $base_route = "entity.{$entity_type}.overview_form";
        break;
    }
    return $base_route ?? '';
  }

}
