<?php

declare(strict_types=1);

namespace Drupal\si_mapping\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\si_mapping\SiMappingInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the si mapping entity class.
 *
 * @ContentEntityType(
 *   id = "si_mapping",
 *   label = @Translation("Si mapping"),
 *   label_collection = @Translation("Si mappings"),
 *   label_singular = @Translation("si mapping"),
 *   label_plural = @Translation("si mappings"),
 *   label_count = @PluralTranslation(
 *     singular = "@count si mappings",
 *     plural = "@count si mappings",
 *   ),
 *   bundle_label = @Translation("Si mapping type"),
 *   handlers = {
 *     "access" = "Drupal\si_mapping\Access\MapItemAccessControlHandler",
 *     "list_builder" = "Drupal\si_mapping\SiMappingListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\si_mapping\Form\SiMappingForm",
 *       "edit" = "Drupal\si_mapping\Form\SiMappingForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "si_mapping",
 *   data_table = "si_mapping_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer si_mapping types",
 *   entity_keys = {
 *     "id" = "id",
 *     "langcode" = "langcode",
 *     "bundle" = "bundle",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/si-mapping",
 *     "add-form" = "/si-mapping/add/{si_mapping_type}",
 *     "add-page" = "/si-mapping/add",
 *     "canonical" = "/si-mapping/{si_mapping}",
 *     "edit-form" = "/si-mapping/{si_mapping}/edit",
 *     "delete-form" = "/si-mapping/{si_mapping}/delete",
 *     "delete-multiple-form" = "/admin/content/si-mapping/delete-multiple",
 *   },
 *   bundle_entity_type = "si_mapping_type",
 *   field_ui_base_route = "entity.si_mapping_type.edit_form",
 * )
 */
final class SiMapping extends ContentEntityBase implements SiMappingInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);
    if (!$this->getOwnerId()) {
      // If no owner has been set explicitly, make the anonymous user the owner.
      $this->setOwnerId(0);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('Label'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Enabled')
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => FALSE,
        ],
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'label' => 'above',
        'weight' => 0,
        'settings' => [
          'format' => 'enabled-disabled',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setTranslatable(TRUE)
      ->setLabel(t('Author'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(self::class . '::getDefaultEntityOwner')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the si mapping was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the si mapping was last edited.'));

    return $fields;
  }

}
