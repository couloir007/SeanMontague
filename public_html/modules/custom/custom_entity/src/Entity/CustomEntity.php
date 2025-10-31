<?php

declare(strict_types=1);

namespace Drupal\custom_entity\Entity;

use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\custom_entity\CustomEntityInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the custom entity entity class.
 *
 * @ContentEntityType(
 *   id = "custom_entity",
 *   label = @Translation("Custom entity"),
 *   label_collection = @Translation("Custom entities"),
 *   label_singular = @Translation("custom entity"),
 *   label_plural = @Translation("custom entities"),
 *   label_count = @PluralTranslation(
 *     singular = "@count custom entities",
 *     plural = "@count custom entities",
 *   ),
 *   bundle_label = @Translation("Custom entity type"),
 *   handlers = {
 *     "list_builder" = "Drupal\custom_entity\CustomEntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\custom_entity\Form\CustomEntityForm",
 *       "edit" = "Drupal\custom_entity\Form\CustomEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *       "revision-delete" = \Drupal\Core\Entity\Form\RevisionDeleteForm::class,
 *       "revision-revert" = \Drupal\Core\Entity\Form\RevisionRevertForm::class,
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *       "revision" = \Drupal\Core\Entity\Routing\RevisionHtmlRouteProvider::class,
 *     },
 *   },
 *   base_table = "custom_entity",
 *   data_table = "custom_entity_field_data",
 *   revision_table = "custom_entity_revision",
 *   revision_data_table = "custom_entity_field_revision",
 *   show_revision_ui = TRUE,
 *   translatable = TRUE,
 *   admin_permission = "administer custom_entity types",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "langcode" = "langcode",
 *     "bundle" = "bundle",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log",
 *   },
 *   links = {
 *     "collection" = "/admin/content/custom-entity",
 *     "add-form" = "/custom-entity/add/{custom_entity_type}",
 *     "add-page" = "/custom-entity/add",
 *     "canonical" = "/custom-entity/{custom_entity}",
 *     "edit-form" = "/custom-entity/{custom_entity}/edit",
 *     "delete-form" = "/custom-entity/{custom_entity}/delete",
 *     "delete-multiple-form" = "/admin/content/custom-entity/delete-multiple",
 *     "revision" = "/custom-entity/{custom_entity}/revision/{custom_entity_revision}/view",
 *     "revision-delete-form" = "/custom-entity/{custom_entity}/revision/{custom_entity_revision}/delete",
 *     "revision-revert-form" = "/custom-entity/{custom_entity}/revision/{custom_entity_revision}/revert",
 *     "version-history" = "/custom-entity/{custom_entity}/revisions",
 *   },
 *   bundle_entity_type = "custom_entity_type",
 *   field_ui_base_route = "entity.custom_entity_type.edit_form",
 * )
 */
final class CustomEntity extends RevisionableContentEntityBase implements CustomEntityInterface {

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
      ->setRevisionable(TRUE)
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
      ->setRevisionable(TRUE)
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

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setLabel(t('Description'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'above',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setRevisionable(TRUE)
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
      ->setDescription(t('The time that the custom entity was created.'))
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
      ->setDescription(t('The time that the custom entity was last edited.'));

    return $fields;
  }

}
