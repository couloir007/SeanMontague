<?php

namespace Drupal\geolocation\Plugin\views\style;

use Drupal\views\Attribute\ViewsStyle;

/**
 * Allow to display several field items on a common map.
 *
 * @ingroup views_style_plugins
 */
#[ViewsStyle(
  id: 'geolocation_layer',
  title: new \Drupal\Core\StringTranslation\TranslatableMarkup('Geolocation Layer'), help: new \Drupal\Core\StringTranslation\TranslatableMarkup('Display geolocations on a layer.'),
  theme: 'views_view_list',
  display_types: ['normal']
)]
class Layer extends GeolocationStyleBase {

  /**
   * {@inheritdoc}
   */
  public function render(): array {

    $render = parent::render();
    if (!$render) {
      return [];
    }

    $build = [
      '#type' => 'container',
      '#attributes' => [
        'id' => $this->displayHandler->display['id'],
        'class' => [
          'geolocation-map-layer',
        ],
      ],
    ];

    /*
     * Add locations to output.
     */
    foreach ($this->view->result as $row) {
      foreach ($this->getLocationsFromRow($row) as $location) {
        $build['locations'][] = $location;
      }
    }

    return $build;
  }

}
