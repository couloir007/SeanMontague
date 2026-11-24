<?php

declare(strict_types=1);

namespace Drupal\custom_field\Service;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface;
use Drupal\custom_field\Time;

/**
 * The GenerateData class.
 */
final class GenerateData implements GenerateDataInterface {

  /**
   * Constructs a new GenerateData object.
   */
  public function __construct(
    private readonly CustomFieldTypeManagerInterface $customFieldTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function generateFieldData(array $settings, string $target_entity_type): array {
    $items = [];
    $custom_items = $this->customFieldTypeManager->getCustomFieldItems($settings);
    foreach ($custom_items as $name => $custom_item) {
      $value = $custom_item->generateSampleValue($custom_item, $target_entity_type);
      $items[$name] = $value;
    }

    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function generateSampleFormData(FieldDefinitionInterface $field, ?array $deltas = NULL): array {
    $field_name = $field->getName();
    if ($deltas === NULL) {
      $deltas = [0];
    }

    // Generate data for the field.
    $settings = $field->getSettings();
    $target_entity_type = $field->getTargetEntityTypeId();

    $form_values = [
      'title[0][value]' => 'Test',
    ];
    foreach ($deltas as $delta) {
      $random_values = $this->generateFieldData($settings, $target_entity_type);

      // UUID's can't be unset through the GUI.
      unset($random_values['uuid']);

      // @todo Hardening: floating point calculation can randomly fail.
      $random_values['decimal'] = '0.50';

      // Cast integer to string.
      $random_values['integer'] = (string) $random_values['integer'];
      // Set a valid time string.
      $random_values['time'] = Time::createFromTimestamp($random_values['time'])->format('h:iA');

      // @todo Hardening: we need to treat maps specially due to ajax.
      unset($random_values['map']);
      unset($random_values['map_string']);

      // @todo Hardening: Add support for entity reference.
      unset($random_values['entity_reference']);

      // @todo Hardening: Add support for file.
      unset($random_values['file']);

      // @todo Hardening: Add support for image.
      unset($random_values['image']);

      // @todo Hardening: Add support for viewfield.
      unset($random_values['viewfield']);

      foreach ($random_values as $subfield => $value) {
        $element_key = "{$field_name}[$delta][$subfield]";

        // Handle nested fields for 'uri' and 'link' types.
        if (in_array($subfield, ['uri', 'link'])) {
          $form_values["{$element_key}[uri]"] = $value['uri'];
          if (isset($value['title'])) {
            $form_values["{$element_key}[title]"] = $value['title'] ?: 'Test title';
          }
        }
        elseif ($subfield === 'datetime' && is_string($value) && $value !== '') {
          if (str_contains($value, 'T')) {
            [$date, $time] = explode('T', $value, 2);
            $form_values["{$element_key}[value][date]"] = $date;
            $form_values["{$element_key}[value][time]"] = $time;
          }
          else {
            // Date-only field.
            $form_values["{$element_key}[value][date]"] = $value;
          }
        }
        elseif ($subfield === 'daterange' && is_array($value)) {
          $start = $value['value'] ?? NULL;
          $end = $value['end'] ?? NULL;

          if (is_string($start) && $start !== '') {
            $form_values["{$element_key}[value][date]"] = $start;
          }
          if (is_string($end) && $end !== '') {
            $form_values["{$element_key}[end_value][date]"] = $end;
          }
        }
        elseif ($subfield === 'time_range' && is_array($value)) {
          $start = $value['value'] ?? NULL;
          $end = $value['end'] ?? NULL;

          if (is_numeric($start)) {
            $form_values["{$element_key}[value]"] = Time::createFromTimestamp($start)->format('h:iA');
          }
          if (is_numeric($end)) {
            $form_values["{$element_key}[end_value]"] = Time::createFromTimestamp($end)->format('h:iA');
          }
        }
        else {
          // Handle flat subfields (e.g., string).
          $form_values[$element_key] = $value;
        }
      }
    }

    return $form_values;
  }

}
