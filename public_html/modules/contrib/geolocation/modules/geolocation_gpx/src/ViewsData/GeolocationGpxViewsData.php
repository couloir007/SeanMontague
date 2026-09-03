<?php

namespace Drupal\geolocation_gpx\ViewsData;

use Drupal\views\EntityViewsData;

/**
 * Geolocation GPX Views data.
 */
class GeolocationGpxViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData(): array {
    $data = parent::getViewsData();

    $data['geolocation_gpx']['bulk_form'] = [
      'title' => $this->t('Geolocation GPX bulk form'),
      'help' => $this->t('Add a form element that lets you run operations on multiple issues.'),
      'field' => [
        'id' => 'bulk_form',
      ],
    ];

    return $data;
  }

}
