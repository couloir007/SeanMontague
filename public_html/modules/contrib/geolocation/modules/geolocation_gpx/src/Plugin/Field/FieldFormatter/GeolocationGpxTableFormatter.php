<?php

namespace Drupal\geolocation_gpx\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Url;
use Drupal\file\Entity\File;

/**
 * Plugin implementation of the 'geofield' formatter.
 */
#[FieldFormatter(id: 'geolocation_gpx_table',
  label: new \Drupal\Core\StringTranslation\TranslatableMarkup('Geolocation GPX Formatter - Data Table'), field_types: ['geolocation_gpx'])]
class GeolocationGpxTableFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {

    if ($items->count() === 0) {
      return [];
    }

    $element = [];

    foreach ($items as $delta => $item) {
      /** @var \Drupal\geolocation_gpx\Entity\GeolocationGpx|NULL $gpx */
      $gpx = \Drupal::entityTypeManager()->getStorage('geolocation_gpx')->load($item->getValue()['gpx_id']);

      if (!$gpx) {
        continue;
      }

      $element[$delta] = [
        'elevation' => $gpx->renderedTracksElevationChart(),
        'summary' => $gpx->renderedSummaryTable(),
      ];

      if ($file = File::load($item->getValue()['gpx_file_id']) ?? NULL) {
        $element[$delta]['file'] = [
          '#type' => 'link',
          '#title' => $this->t('Source file: %file', ['%file' => $file->getFilename()]),
          '#url' => Url::fromUri($file->createFileUrl(FALSE)),
        ];
      }
    }

    return $element;
  }

}
