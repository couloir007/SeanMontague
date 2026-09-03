<?php

namespace Drupal\geolocation_geometry\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'geolocation_geometry_file' formatter.
 */
#[FieldFormatter(
  id: 'geolocation_geometry_file',
  label: new \Drupal\Core\StringTranslation\TranslatableMarkup('Geolocation Geometry - File'),
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
class GeolocationGeometryFileFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    $settings = parent::defaultSettings();

    $settings['geometry_format'] = 'geojson';
    $settings['join_items'] = FALSE;

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $element = parent::settingsForm($form, $form_state);

    $settings = $this->getSettings();

    $element['geometry_format'] = [
      '#type' => 'select',
      '#options' => [
        'geojson' => $this->t('GeoJSON'),
        'wkt' => $this->t('WKT (Well Known Text)'),
        'gpx' => $this->t('GPX (GPS Exchange Format)'),
        'kml' => $this->t('KML (Keyhole Markup Language)'),
      ],
      '#title' => $this->t('Geometry Format'),
      '#default_value' => $settings['geometry_format'],
    ];

    $element['join_items'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Join Geometry Items'),
      '#description' => $this->t('Join single field item geometries to one combined geometry.'),
      '#default_value' => $settings['join_items'],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $settings = $this->getSettings();

    $summary = parent::settingsSummary();
    $summary[] = $this->t(
      'Data format: @format',
      [
        // @phpstan-ignore-next-line
        '@format' => match($settings['geometry_format']) {
          'geojson' => $this->t('GeoJSON'),
          'wkt' => $this->t('WKT (Well Known Text)'),
          'gpx' => $this->t('GPX (GPS Exchange Format)'),
          'kml' => $this->t('KML (Keyhole Markup Language)'),
        },
      ]
    );

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $element = [];

    $settings = $this->getSettings();

    foreach ($items as $delta => $item) {

      $element[$delta] = [
        '#type' => 'link',
        '#title' => $this->t(
          'Download %type file',
          [
            // @phpstan-ignore-next-line
            '%type' => match($settings['geometry_format']) {
              'geojson' => $this->t('GeoJSON'),
              'wkt' => $this->t('WKT (Well Known Text)'),
              'gpx' => $this->t('GPX (GPS Exchange Format)'),
              'kml' => $this->t('KML (Keyhole Markup Language)'),
            },
          ]
        ),
        '#url' => Url::fromRoute('geolocation_geometry.geometry_format_file_download', [
          'format' => $settings['geometry_format'],
          'entity_type' => $items->getEntity()->getEntityTypeId(),
          'entity_id' => $items->getEntity()->id(),
          'field_name' => $this->fieldDefinition->getName(),
          'delta' => $delta,
        ]),
        '#attributes' => [
          'target' => '_blank',
        ],
      ];
    }

    return $element;
  }

}
