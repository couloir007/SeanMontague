<?php

namespace Drupal\geolocation\Plugin\geolocation\LayerFeature;

use Drupal\geolocation\Attribute\LayerFeature;
use Drupal\Core\Template\Attribute;
use Drupal\geolocation\LayerFeatureBase;
use Drupal\geolocation\MapProviderInterface;

/**
 * Marker zoom by anchor.
 */
#[LayerFeature(id: 'marker_zoom_by_anchor',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Marker Zoom By Anchor'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Set a URL anchor.'),
  type: 'all',
)]
class MarkerZoomByAnchor extends LayerFeatureBase {

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    return [
      'marker_zoom_anchor_id' => '',
      'click_marker' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings, array $parents = [], ?MapProviderInterface $mapProvider = NULL): array {
    $form = parent::getSettingsForm($settings, $parents, $mapProvider);

    $form['marker_zoom_anchor_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Anchor ID'),
      '#description' => $this->t('Clicking a link with the class "geolocation-marker-zoom" and this anchor target will zoom to the specific marker and animate it. Tokens supported.'),
      '#default_value' => $settings['marker_zoom_anchor_id'],
    ];

    $form['click_marker'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Click marker after zoom'),
      '#description' => $this->t('Behave as if the marker was clicked, e.g. to open its popup. Other features reacting to marker clicks, like "Marker Scroll-to-ID", will run as well.'),
      '#default_value' => $settings['click_marker'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function alterLayer(array $render_array, string $layer_id, array $feature_settings = [], array $context = []): array {
    $render_array = parent::alterLayer($render_array, $layer_id, $feature_settings, $context);

    // Depending on the render stage this feature runs at, the location render
    // arrays can either live under '#children' or directly under 'locations'
    // (they are only moved into '#children' later, in GeolocationMap's
    // pre-render). Support both so the attribute is reliably emitted.
    if (!empty($render_array['#children']['locations'])) {
      $locations = &$render_array['#children']['locations'];
    }
    elseif (!empty($render_array['locations'])) {
      $locations = &$render_array['locations'];
    }
    else {
      return $render_array;
    }

    if (!empty($context['view'])) {
      /** @var \Drupal\views\ViewExecutable $view */
      $view = $context['view'];
    }

    foreach ($locations as &$location) {
      $anchor_id = $this->token->replace($feature_settings['marker_zoom_anchor_id'], $context);

      if (empty($view)) {
        continue;
      }

      if (empty($location['#attributes'])) {
        $location['#attributes'] = [];
      }
      elseif (!is_array($location['#attributes'])) {
        /** @var array{"#attributes": array|\Drupal\Core\Template\Attribute} $location */
        $location['#attributes'] = new Attribute($location['#attributes']);
        $location['#attributes'] = $location['#attributes']->toArray();
      }

      if (isset($location['#attributes']['data-views-row-index'])) {
        $anchor_id = $view->getStyle()->tokenizeValue($anchor_id, (int) $location['#attributes']['data-views-row-index']);
        $location['#attributes']['data-marker-zoom-anchor-id'] = preg_replace('/\s+/', '', strip_tags($anchor_id));
      }
    }

    return $render_array;
  }

}
