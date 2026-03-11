<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_media;

use Drupal\Core\Form\FormStateInterface;
use Drupal\media\MediaTypeInterface;

/**
 * The Schema.org media manager interface.
 */
interface SchemaDotOrgMediaManagerInterface {

  /**
   * Alter Schema.org mapping entity default values.
   *
   * @param array $defaults
   *   The Schema.org mapping entity default values.
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string|null $bundle
   *   The bundle.
   * @param string $schema_type
   *   The Schema.org type.
   *
   * @see hook_schemadotorg_mapping_defaults_alter()
   */
  public function mappingDefaultsAlter(array &$defaults, string $entity_type_id, ?string $bundle, string $schema_type): void;

  /**
   * Alter the Schema.org Blueprints UI mapping form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\media\MediaTypeForm::form
   */
  public function mappingFormAlter(array &$form, FormStateInterface &$form_state): void;

  /**
   * Alter bundle entity type before it is created.
   *
   * Sets the default values passed to MediaType::create.
   *
   * @param array &$values
   *   The bundle entity type values.
   * @param string $schema_type
   *   The Schema.org type.
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @see \Drupal\Tests\media\Traits\MediaTypeCreationTrait::createMediaType
   * @see \Drupal\media\MediaTypeForm::save()
   */
  public function bundleEntityAlter(array &$values, string $schema_type, string $entity_type_id): void;

  /**
   * Insert a new media type and handle Schema.org mappings.
   *
   * Creates the source field for media type.
   *
   * The below code is copied from the MediaTypeForm.
   *
   * @param \Drupal\media\MediaTypeInterface $media_type
   *   The media type being inserted.
   *
   * @see \Drupal\media\MediaTypeForm::save
   */
  public function mediaTypeInsert(MediaTypeInterface $media_type): void;

  /**
   * Alter field storage and field values before they are created.
   *
   * @param string $schema_type
   *   The Schema.org type.
   * @param string $schema_property
   *   The Schema.org property.
   * @param array $field_storage_values
   *   Field storage config values.
   * @param array $field_values
   *   Field config values.
   * @param string|null $widget_id
   *   The plugin ID of the widget.
   * @param array $widget_settings
   *   An array of widget settings.
   * @param string|null $formatter_id
   *   The plugin ID of the formatter.
   * @param array $formatter_settings
   *   An array of formatter settings.
   *
   * @see hook_schemadotorg_property_field_alter()
   */
  public function propertyFieldAlter(
    string $schema_type,
    string $schema_property,
    array &$field_storage_values,
    array &$field_values,
    ?string &$widget_id,
    array &$widget_settings,
    ?string &$formatter_id,
    array &$formatter_settings,
  ): void;

}
