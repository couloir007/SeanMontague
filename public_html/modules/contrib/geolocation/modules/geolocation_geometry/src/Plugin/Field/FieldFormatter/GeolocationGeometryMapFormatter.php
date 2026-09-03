<?php

namespace Drupal\geolocation_geometry\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\geolocation\Plugin\Field\FieldFormatter\GeolocationMapFormatterBase;

/**
 * Plugin implementation of the 'geometry' formatter.
 */
#[FieldFormatter(
  id: 'geolocation_geometry',
  label: new \Drupal\Core\StringTranslation\TranslatableMarkup('Geolocation Geometry Formatter - Map'),
  field_types: [
    'geolocation_geometry_geometry',
    'geolocation_geometry_geometrycollection',
    'geolocation_geometry_point',
    'geolocation_geometry_linestring',
    'geolocation_geometry_polygon',
    'geolocation_geometry_multipoint',
    'geolocation_geometry_multilinestring',
    'geolocation_geometry_multipolygon',
  ]
)]
class GeolocationGeometryMapFormatter extends GeolocationMapFormatterBase {

  /**
   * {@inheritdoc}
   */
  protected static string $dataProviderId = 'geolocation_geometry';

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $element = parent::settingsForm($form, $form_state);

    unset($element['set_marker']);
    unset($element['replacement_patterns']);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function getMapObjects(FieldItemListInterface $items): array {
    $settings = $this->getSettings();

    $map_objects = [];

    foreach ($items as $delta => $item) {
      foreach (array_merge($this->dataProvider->getLocationsFromItem($item), $map_objects) as $location) {
        switch ($location['#type']) {
          case 'container':
            foreach (Element::children($location) as $child_location) {
              $child_location['#weight'] = $delta;
              $map_objects[] = $this->addSettingsToLocation($child_location, $item);
            }
            break;

          case 'geolocation_map_location':
            $location['#weight'] = $delta;
            $map_objects[] = $this->addSettingsToLocation($location, $item);
            break;
        }
      }

      foreach (array_merge($this->dataProvider->getShapesFromItem($item), $map_objects) as $shape) {
        if (empty($shape['#title'])) {
          $shape['#title'] = $this->dataProvider->replaceFieldItemTokens($settings['title'], $item);
        }

        if ($settings['show_label']) {
          $shape['#label'] = $shape['#title'];
        }

        if (
          !empty($settings['info_text']['value'])
          && !empty($settings['info_text']['format'])
        ) {
          $shape['content'] = [
            '#type' => 'processed_text',
            '#text' => $this->dataProvider->replaceFieldItemTokens($settings['info_text']['value'], $item),
            '#format' => $settings['info_text']['format'],
          ];
        }

        $map_objects[] = $shape;
      }
    }

    return $map_objects;
  }

}
