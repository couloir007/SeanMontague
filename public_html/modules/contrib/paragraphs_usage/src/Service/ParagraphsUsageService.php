<?php

declare(strict_types=1);

namespace Drupal\paragraphs_usage\Service;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\paragraphs\Entity\ParagraphsType;

/**
 * Class ParagraphsUsageService.
 *
 * Service for getting usages of a given paragraph type.
 *
 * @package Drupal\paragraphs_usage\Service
 */
class ParagraphsUsageService {

  /**
   * Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity Type Bundle Info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Entity Field Manger service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Paragraphs Type (searched for).
   *
   * @var \Drupal\paragraphs\Entity\ParagraphsType
   */
  protected $paragraphType;

  /**
   * Holds the usages of a paragraph type.
   *
   * @var array
   */
  protected $usedParagraphs = [];

  /**
   * Holds all fieldable entities/content entities.
   *
   * @var array
   */
  protected $fieldableEntityTypes = [];

  /**
   * ParagraphsUsageService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity Type Manager service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   Entity Type Bundle Info service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   Entity Field Manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityFieldManagerInterface $entity_field_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityFieldManager = $entity_field_manager;
    $this->setFieldableEntityTypes();
    $this->setBundleInfos();
  }

  /**
   * Setting the paragraphs type for which the usage should be determined.
   *
   * This method needs to be called.
   *
   * @param \Drupal\paragraphs\Entity\ParagraphsType $paragraphs_type
   *   The paragraphs type for which to check usage.
   */
  public function setParagraphType(ParagraphsType $paragraphs_type): void {
    $this->paragraphType = $paragraphs_type;
    $this->setUsedParagraphs();
  }

  /**
   * Getter for the usages.
   *
   * @return array
   *   usage in paragraphs.
   */
  public function getUsedParagraphs(): array {
    return $this->usedParagraphs;
  }

  /**
   * Internal function for setting the usages.
   */
  protected function setUsedParagraphs(): void {
    foreach ($this->fieldableEntityTypes as $entity_type_id => $type_bundles) {
      if (!empty($type_bundles['bundle'])) {
        foreach ($type_bundles['bundle'] as $type_bundle) {
          $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $type_bundle);

          foreach ($field_definitions as $field_definition) {
            if ($field_definition->getType() == 'entity_reference_revisions') {
              $field_settings = $field_definition->getSettings();
              if (empty($field_settings['handler_settings']['target_bundles'])) {
                if (isset($field_settings['handler_settings']['negate']) && $field_settings['handler_settings']['negate'] == 1) {
                  $this->addToUsedParagraphs($entity_type_id, $type_bundle, $field_definition, $type_bundles['bundle_entity_type']);
                }
              }
              else {
                if (isset($field_settings['handler_settings']['negate']) && $field_settings['handler_settings']['negate'] == 1) {
                  $add_to_used = TRUE;
                  foreach ($field_settings['handler_settings']['target_bundles'] as $target_bundle) {
                    if ($target_bundle == $this->paragraphType->id()) {
                      $add_to_used = FALSE;
                      break;
                    }
                  }
                  if ($add_to_used) {
                    $this->addToUsedParagraphs($entity_type_id, $type_bundle, $field_definition, $type_bundles['bundle_entity_type']);
                  }
                }
                else {
                  foreach ($field_settings['handler_settings']['target_bundles'] as $target_bundle) {
                    if ($target_bundle == $this->paragraphType->id()) {
                      $this->addToUsedParagraphs($entity_type_id, $type_bundle, $field_definition, $type_bundles['bundle_entity_type']);
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
  }

  /**
   * Internal function for adding to the usage array.
   *
   * @param string $entity_type
   *   Entity type id.
   * @param string $bundle
   *   Entity type bundle.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   Field definition.
   * @param null|string $bundle_entity_type
   *   Bundle entity type.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function addToUsedParagraphs($entity_type, $bundle, FieldDefinitionInterface $field_definition, $bundle_entity_type = NULL): void {
    $label = "";

    if (!empty($bundle_entity_type)) {
      $label_entity = $this->entityTypeManager->getStorage($bundle_entity_type)->load($bundle);
      if ($label_entity !== NULL) {
        $label = $label_entity->label();
      }
    }
    else {
      $label_entity = $this->entityTypeManager->getDefinition($entity_type);
      if ($label_entity !== NULL) {
        $label = $label_entity->getLabel();
      }
    }

    $this->usedParagraphs[] = [
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'bundle_entity_type' => $bundle_entity_type,
      'entity_type_label' => $label,
      'field' => [
        'label' => $field_definition->getLabel(),
        'name' => $field_definition->getName(),
      ],
    ];
  }

  /**
   * Internal function for setting the fieldable/content entity types.
   *
   * Defines where to look for usages.
   */
  protected function setFieldableEntityTypes(): void {
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($entity_type instanceof ContentEntityTypeInterface) {
        $this->fieldableEntityTypes[$entity_type_id] = [
          'bundle_entity_type' => $entity_type->getBundleEntityType(),
        ];
      }
    }
  }

  /**
   * Internal function for setting bundle infos.
   *
   * On the fieldable content entity types.
   */
  protected function setBundleInfos(): void {
    foreach (array_keys($this->fieldableEntityTypes) as $entity_type_id) {
      $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
      foreach (array_keys($bundles) as $bundle_id) {
        $this->fieldableEntityTypes[$entity_type_id]['bundle'][] = $bundle_id;
      }
    }
  }

}
