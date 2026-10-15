<?php

namespace Drupal\custom_field\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for custom field Type plugins.
 */
interface CustomFieldTypeInterface extends PluginInspectionInterface {

  /**
   * Defines the field settings for this plugin.
   *
   * @return array<string, mixed>
   *   A list of default settings, keyed by the setting name.
   */
  public static function defaultFieldSettings(): array;

  /**
   * Returns a form for the widget settings for this custom field type.
   *
   * @param array<string, mixed> $form
   *   The parent form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the (entire) configuration form.
   *
   * @return array<string, mixed>
   *   The form definition for the field settings.
   */
  public function fieldSettingsForm(array &$form, FormStateInterface $form_state): array;

  /**
   * Render the stored value of the custom field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   A field.
   *
   * @return mixed
   *   The value.
   */
  public function value(FieldItemInterface $item): mixed;

  /**
   * Returns the raw stored value(s) of this subfield for a field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The field item to read the stored columns from.
   *
   * @return mixed
   *   A scalar for single-column subfields, or an array keyed by property
   *   suffix ('value', 'end', 'duration', ...) for multi-column subfields.
   */
  public function getRawValue(FieldItemInterface $item): mixed;

  /**
   * The default formatter plugin type.
   *
   * @return string
   *   The machine name of the formatter plugin.
   */
  public function getDefaultFormatter(): string;

  /**
   * The default widget plugin type.
   *
   * @return string
   *   The machine name of the widget plugin.
   */
  public function getDefaultWidget(): string;

  /**
   * The label for the custom field item.
   *
   * @return string
   *   The label.
   */
  public function getLabel(): string;

  /**
   * The machine name of the custom field item.
   *
   * @return string
   *   The machine name.
   */
  public function getName(): string;

  /**
   * The maxLength value for the custom field item.
   *
   * @return int
   *   The maxLength value.
   */
  public function getMaxLength(): int;

  /**
   * The dataType value for the custom field item.
   *
   * @return string
   *   The dataType value.
   */
  public function getDataType(): string;

  /**
   * The unsigned value from the custom field item.
   *
   * @return bool
   *   The boolean value for unsigned.
   */
  public function isUnsigned(): bool;

  /**
   * The scale value from the custom field item.
   *
   * @return int
   *   The scale value of the column.
   */
  public function getScale(): int;

  /**
   * The precision value from the custom field item.
   *
   * @return int
   *   The precision value of the column.
   */
  public function getPrecision(): int;

  /**
   * The datetime_type value from the custom field item.
   *
   * @return string
   *   The datetime_type value of the column.
   */
  public function getDatetimeType(): string;

  /**
   * The target_type value from the custom field item.
   *
   * @return string|null
   *   The target_type value of the column.
   */
  public function getTargetType(): ?string;

  /**
   * Returns the array of settings.
   *
   * @return array<string, mixed>
   *   The array of settings.
   */
  public function getSettings(): array;

  /**
   * Returns the value of a setting.
   *
   * @param string $setting
   *   The setting name.
   *
   * @return mixed
   *   The setting value.
   */
  public function getSetting(string $setting): mixed;

  /**
   * Returns the array of field settings.
   *
   * @return array<string, mixed>
   *   The array of settings.
   */
  public function getFieldSettings(): array;

  /**
   * Returns the value of a field setting.
   *
   * @param string $setting
   *   The setting name.
   *
   * @return mixed
   *   The setting value.
   */
  public function getFieldSetting(string $setting): mixed;

  /**
   * Should the field item be included in the empty check?
   *
   * @return bool
   *   TRUE if the field item should be included, otherwise FALSE.
   */
  public function checkEmpty(): bool;

  /**
   * Returns an array of schema properties.
   *
   * @param array<string, mixed> $settings
   *   Optional settings passed to the schema() function.
   *
   * @return array<string, mixed>
   *   An array of schema properties for the field type.
   */
  public static function schema(array $settings): array;

  /**
   * Returns the names of the extra properties this subfield stores.
   *
   * Some subfield types store more than one column: an image also stores its
   * alt text and dimensions, a viewfield also stores its display id. Those
   * live in sibling columns named "<subfield>__<suffix>".
   *
   * @param array<string, mixed> $settings
   *   The column settings, including the subfield machine name in 'name'.
   *
   * @return string[]
   *   FULL property names, not suffixes - for example
   *   ['my_view__display', 'my_view__items']. An empty array when the subfield
   *   stores only its main column.
   */
  public static function getExtraPropertyNames(array $settings): array;

  /**
   * Maps a nested subfield value onto its extra properties.
   *
   * Implement this when a subfield accepts a nested array whose keys do not
   * already match its extra property names, so that callers holding only that
   * nested value can still derive the flat sibling values from it.
   *
   * This method RETURNS values and must never write to properties or to the
   * field item - callers decide what to do with the result.
   *
   * @param array<string, mixed> $settings
   *   The column settings, including the subfield machine name in 'name'.
   * @param mixed $value
   *   The incoming subfield value, typically a nested array.
   *
   * @return array<string, mixed>
   *   Extra property values keyed by FULL property name, covering only the
   *   subset the value actually carries. Defaults to an empty array.
   */
  public static function extractExtraPropertyValues(array $settings, mixed $value): array;

  /**
   * Returns an array of property definitions.
   *
   * @param array<string, mixed> $settings
   *   Optional settings passed to the propertyDefinitions() function.
   *
   * @return mixed
   *   The DataDefinition of properties for the field type.
   */
  public static function propertyDefinitions(array $settings): mixed;

  /**
   * Generates placeholder field values.
   *
   * @param \Drupal\custom_field\Plugin\CustomFieldTypeInterface $field
   *   An instance of the custom field.
   * @param string $target_entity_type
   *   The entity type of the field this custom field is attached to.
   *
   * @return mixed
   *   A sample value for the custom field.
   */
  public static function generateSampleValue(CustomFieldTypeInterface $field, string $target_entity_type): mixed;

  /**
   * Returns an array of constraints.
   *
   * @return array<string, mixed>
   *   Array of constraints.
   */
  public function getConstraints(): array;

  /**
   * Returns an array of calculated dependencies.
   *
   * @param \Drupal\custom_field\Plugin\CustomFieldTypeInterface $item
   *   The custom field type interface.
   * @param array<string, mixed> $default_value
   *   A default value array for the field.
   *
   * @return array<string, mixed>
   *   An array of dependencies.
   */
  public static function calculateDependencies(CustomFieldTypeInterface $item, array $default_value): array;

  /**
   * Returns an array of widget settings to change when dependency is removed.
   *
   * @param \Drupal\custom_field\Plugin\CustomFieldTypeInterface $item
   *   The custom field type interface.
   * @param array<string, mixed> $dependencies
   *   An array of dependencies that will be deleted keyed by dependency type.
   *   Dependency types are, for example, entity, module and theme.
   *
   * @return array<string, mixed>
   *   An array of settings that changed.
   */
  public static function onDependencyRemoval(CustomFieldTypeInterface $item, array $dependencies): array;

  /**
   * Returns if the field type can be added.
   *
   * @return bool
   *   TRUE if the formatter can be used, FALSE otherwise.
   */
  public static function isApplicable(): bool;

  /**
   * React to changes to a child property or item.
   *
   * @param string $property_name
   *   The name of the property.
   * @param bool $notify
   *   (optional) Whether to forward the notification to the parent. Defaults to
   *   TRUE. By passing FALSE, overrides of this method can re-use the logic
   *   of parent classes without triggering notification.
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The field item.
   */
  public function onChange(string $property_name, bool $notify, FieldItemInterface $item): void;

}
