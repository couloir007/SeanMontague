<?php

namespace Drupal\geolocation_gpx\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\geolocation\Plugin\Field\FieldFormatter\GeolocationMapFormatterBase;

/**
 * Plugin implementation of the 'geofield' formatter.
 */
#[FieldFormatter(
  id: 'geolocation_gpx_map',
  label: new \Drupal\Core\StringTranslation\TranslatableMarkup('Geolocation GPX Formatter - Map'),
  field_types: ['geolocation_gpx']
)]
class GeolocationGpxMapFormatter extends GeolocationMapFormatterBase {

  /**
   * {@inheritdoc}
   */
  protected static string $dataProviderId = 'geolocation_gpx';

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $element = parent::settingsForm($form, $form_state);

    unset($element['set_marker']);
    // @todo re-enable?
    unset($element['title']);
    unset($element['info_text']);
    unset($element['replacement_patterns']);

    return $element;
  }

}
