<?php

namespace Drupal\geolocation_geometry\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\geolocation_geometry\Plugin\Field\FieldType\GeolocationGeometryBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\geolocation_geometry\GeometryFormat\GeoJSON;

/**
 * Plugin implementation of the 'geolocation_geometry_data' formatter.
 */
#[FieldFormatter(
  id: 'geolocation_geometry_data',
  label: new \Drupal\Core\StringTranslation\TranslatableMarkup('Geolocation Geometry - Data'),
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
class GeolocationGeometryDataFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    $settings = parent::defaultSettings();

    $settings['geometry_format'] = 'geojson';

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
      assert($item instanceof GeolocationGeometryBase);
      switch ($settings['geometry_format'] ?? FALSE) {
        case 'geojson':
          $data = $item->geojson;
          break;

        case 'wkt':
          try {
            $geometry = GeoJSON::geometryByText($item->geojson);
            $data = $geometry->toWKT();
          }
          catch (\Exception $e) {
            $data = $e->getMessage();
          }
          break;

        case 'gpx':
          try {
            $geometry = GeoJSON::geometryByText($item->geojson);
            $data = Html::escape($geometry->toGPX());
          }
          catch (\Exception $e) {
            $data = $e->getMessage();
          }
          break;

        case 'kml':
          try {
            $geometry = GeoJSON::geometryByText($item->geojson);
            $data = Html::escape($geometry->toKML());
          }
          catch (\Exception $e) {
            $data = $e->getMessage();
          }
          break;

        default:
          $data = $this->t('Unsupported format');
          break;
      }

      $element[$delta] = [
        '#markup' => $data,
      ];
    }

    return $element;
  }

}
