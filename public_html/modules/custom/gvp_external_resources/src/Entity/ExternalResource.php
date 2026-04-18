<?php

namespace Drupal\gvp_external_resources\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\gvp_external_resources\ExternalResourceInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the External resource entity.
 *
 * @ingroup gvp_external_resources
 *
 * @ContentEntityType(
 *   id = "gvp_external_resource",
 *   label = @Translation("External resource"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "gvp_external_resource",
 *   revision_table = "gvp_external_resource_revision",
 *   admin_permission = "administer external resource entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_user",
 *     "revision_created" = "revision_created",
 *     "revision_log_message" = "revision_log_message",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/gvp_external_resource/{gvp_external_resource}",
 *     "add-form" = "/admin/structure/gvp_external_resource/add",
 *     "edit-form" = "/admin/structure/gvp_external_resource/{gvp_external_resource}/edit",
 *     "delete-form" = "/admin/structure/gvp_external_resource/{gvp_external_resource}/delete",
 *     "collection" = "/admin/structure/gvp_external_resource",
 *   },
 *   field_ui_base_route = "gvp_external_resource.settings"
 * )
 */
class ExternalResource extends RevisionableContentEntityBase implements ExternalResourceInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;
  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
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
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published = NULL) {
    if ($published === NULL) {
      $published = TRUE;
    }
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Add the published field.
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the External resource entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the External resource.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setDescription(t('A long description of the External resource.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 0,
        'settings' => [
          'rows' => 5,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['homepage_url'] = BaseFieldDefinition::create('link')
      ->setLabel(t('Homepage URL'))
      ->setDescription(t('The homepage URL of the external resource.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'link_type' => 16, // LinkItemInterface::LINK_GENERIC
        'title' => 1, // LinkItemInterface::LINK_DISABLED
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'link',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'link_default',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['resource_kind'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Resource Kind'))
      ->setDescription(t('The kind of resource.'))
      ->setRevisionable(TRUE)
      ->setSetting('allowed_values', [
        'tool' => 'Tool',
        'dataset' => 'Dataset',
        'website' => 'Website',
        'hazard' => 'Hazard',
        'stations' => 'Stations',
        'portal' => 'Portal',
        'other' => 'Other',
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'list_default',
        'weight' => 2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['schema_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Schema Type'))
      ->setDescription(t('The Schema.org type (optional).'))
      ->setRevisionable(TRUE)
      ->setSetting('allowed_values', [
        'WebPage' => 'WebPage',
        'WebSite' => 'WebSite',
        'Dataset' => 'Dataset',
        'SoftwareApplication' => 'SoftwareApplication',
        'CreativeWork' => 'CreativeWork',
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'list_default',
        'weight' => 3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['default_endpoints'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Default endpoints'))
      ->setDescription(t('Optional: default endpoints to show for this resource on volcano pages. If a volcano-resource relationship does not specify endpoints, these defaults can be used.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'external_resource_endpoint')
      ->setSetting('handler', 'default')
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => 4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 4,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status']->setDescription(t('A boolean indicating whether the External resource is published.'))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
