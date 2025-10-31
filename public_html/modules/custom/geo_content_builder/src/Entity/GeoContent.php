<?php

declare(strict_types=1);

namespace Drupal\geo_content_builder\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\geo_content_builder\GeoContentInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the si map entity class.
 *
 * @ContentEntityType(
 *   id = "geo_content_builder",
 *   label = @Translation("SI Map"),
 *   label_collection = @Translation("SI Maps"),
 *   label_singular = @Translation("si map"),
 *   label_plural = @Translation("si maps"),
 *   label_count = @PluralTranslation(
 *     singular = "@count si maps",
 *     plural = "@count si maps",
 *   ),
 *   bundle_label = @Translation("SI Map type"),
 *   handlers = {
 *     "list_builder" = "Drupal\geo_content_builder\GeoContentListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "geo_content_builder",
 *   data_table = "geo_content_builder_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer geo_content_builder types",
 *   entity_keys = {
 *     "id" = "id",
 *     "langcode" = "langcode",
 *     "bundle" = "bundle",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/si-map",
 *     "add-form" = "/geo_content_builder/add/{geo_content_builder_type}",
 *     "add-page" = "/geo_content_builder/add",
 *     "canonical" = "/geo_content_builder/{geo_content_builder}",
 *     "edit-form" = "/geo_content_builder/{geo_content_builder}/edit",
 *     "delete-form" = "/geo_content_builder/{geo_content_builder}/delete",
 *     "delete-multiple-form" = "/admin/content/si-map/delete-multiple",
 *   },
 *   bundle_entity_type = "geo_content_builder_type",
 *   permission_granularity = "bundle",
 *   field_ui_base_route = "entity.geo_content_builder_type.edit_form",
 *   common_reference_target  = TRUE,
 * )
 */
final class GeoContent extends ContentEntityBase implements GeoContentInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function getName() {
    $name = $this->get('label')->value;

    if (empty($name)) {
      $media_source = $this->getSource();
      return $media_source->getMetadata($this, $media_source->getPluginDefinition()['default_name_metadata_attribute']);
    }

    return $name;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getName();
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    return $this->set('name', $name);
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    return $this->set('created', $timestamp);
  }

  /**
   * {@inheritdoc}
   */
  public function getSource() {
    return $this->bundle->entity->getSource();
  }

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
      ->setDescription(t('The time that the si map was created.'))
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
      ->setDescription(t('The time that the si map was last edited.'));

    return $fields;
  }

}
