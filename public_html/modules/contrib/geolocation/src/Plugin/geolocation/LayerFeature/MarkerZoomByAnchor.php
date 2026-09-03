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

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function alterLayer(array $render_array, string $layer_id, array $feature_settings = [], array $context = []): array {
    $render_array = parent::alterLayer($render_array, $layer_id, $feature_settings, $context);

    if (empty($render_array['#children']['locations'])) {
      return $render_array;
    }

    if (!empty($context['view'])) {
      /** @var \Drupal\views\ViewExecutable $view */
      $view = $context['view'];
    }

    foreach ($render_array['#children']['locations'] as &$location) {
      $anchor_id = $this->token->replace($feature_settings['marker_zoom_anchor_id'], $context);

      if (empty($view)) {
        continue;
      }

      if (empty($location['#attributes'])) {
        $location['#attributes'] = [];
      }
      elseif (!is_array($location['#attributes'])) {
        $location['#attributes'] = new Attribute($location['#attributes']);
        $location['#attributes'] = $location['#attributes']->toArray();
      }

      if (isset($location['#attributes']['data-views-row-index'])) {
        $anchor_id = $view->getStyle()->tokenizeValue($anchor_id, (int) $location['#attributes']['data-views-row-index']);
        $location['#attributes']['data-marker-zoom-anchor-id'] = trim($anchor_id);
      }
    }

    return $render_array;
  }

}
