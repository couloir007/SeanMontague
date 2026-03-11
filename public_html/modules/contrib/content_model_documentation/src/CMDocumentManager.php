<?php

namespace Drupal\content_model_documentation;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Manager for working with content model document entities.
 */
class CMDocumentManager {

  use StringTranslationTrait;
  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Entity\EntityTypeBundleInfoInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Time of current request.
   *
   * @var \DateTimeInterface
   */
  private $requestDateTime;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The EntityTypeBundleInfoInterface service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, TimeInterface $time, EntityTypeBundleInfoInterface $entity_type_bundle_info, MessengerInterface $messenger) {
    $this->entityTypeManager = $entityTypeManager;
    $this->time = $time;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->messenger = $messenger;
  }

  /**
   * Returns all active Content Model Documents.
   *
   * @return \Drupal\content_model_documentation\Entity\CMDocumentInterface[]
   *   Array of active Content Model Documents indexed by their ids.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getCmDocuments(): array {
    /** @var \Drupal\content_model_documentation\Entity\CMDocumentInterface[] $cmDocuments */
    // @todo This probably is not needed and should be handled with a View.
    $cmDocuments = $this->entityTypeManager
      ->getStorage('cm_document')
      ->loadByProperties(['status' => 1]);
    return $cmDocuments;
  }

  /**
   * Get a map array connecting storage type keys to content keys.
   *
   * @return array
   *   Array of pattern ['storage_type' => 'entity content'].
   */
  public static function getStorageMap(): array {
    return [
      'node_type' => 'node',
      'media_type' => 'media',
      'block_content_type' => 'block_content',
      'menu_link_content' => 'menu_link_content',
      'paragraphs_type' => 'paragraph',
      'taxonomy_vocabulary' => 'taxonomy_term',
      'field_storage_config' => 'base.field',
      'field_config' => '',
      'view' => 'view',
    ];
  }

  /**
   * Function to get the entity type name from a bundle type.
   *
   * @param string $bundle
   *   The bundle type.
   *
   * @return string|null
   *   The entity type name if found, NULL otherwise.
   */
  public function getEntityTypeMachineNameFromBundle(string $bundle): ?string {
    // Get all bundle info.
    $allBundleInfo = $this->entityTypeBundleInfo->getAllBundleInfo();

    // Iterate through bundle info to find the entity type for the given bundle.
    foreach ($allBundleInfo as $entityTypeId => $bundles) {
      if (isset($bundles[$bundle])) {
        // Get the entity type definition for the given entity type.
        $entityTypeDefinition = $this->entityTypeManager->getDefinition($entityTypeId);

        // Check if the entity type has a bundle entity type.
        if ($entityTypeDefinition->getBundleEntityType()) {
          return $entityTypeDefinition->getBundleEntityType();
        }
      }
    }

    return NULL;
  }

  /**
   * Delete cm_document entities for an entity being deleted.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being deleted.
   */
  public function deleteRelatedDocuments(EntityInterface $entity): void {
    $storage_data = self::getStorageMap();
    $entity_type = $entity->getEntityTypeId();
    $cm_documents = [];
    // Making sure that $enity_type is valid.
    if (isset($storage_data[$entity_type])) {
      if ($entity_type === 'field_storage_config') {
        $field_machine_name = $entity->getName();
        $cm_doc_id = "{$storage_data[$entity_type]}.{$field_machine_name}";
      }
      elseif ($entity_type === 'field_config') {
        $bundle = $entity->getTargetBundle();
        $entity_type_id = $this->getEntityTypeMachineNameFromBundle($entity->getTargetBundle());
        $field_machine_name = $entity->getName();
        $cm_doc_id = "{$storage_data[$entity_type_id]}.{$bundle}.{$field_machine_name}";
      }
      else {
        $bundle = $entity->id();
        $cm_doc_id = "{$storage_data[$entity_type]}.{$bundle}";
      }
      $cm_documents = $this->entityTypeManager->getStorage('cm_document')->loadByProperties(['documented_entity' => ($cm_doc_id)]);
      if ($cm_documents) {
        // Get the first (and only) documentation entity.
        $cm_document = reset($cm_documents);
        // Delete the documentation entity.
        $cm_document->delete();
        // Notify the user.
        $this->messenger->addStatus($this->t('The documentation for @item entity has been removed.', ['@item' => $cm_doc_id]));
      }
    }
  }

}
