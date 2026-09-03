<?php

namespace Drupal\geolocation\Element;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\RenderElementBase;
use Drupal\Core\Template\Attribute;

/**
 * Provides a render element to add a geolocation layer.
 *
 * Usage example:
 * @code
 * $form['layer'] = [
 *   '#type' => 'geolocation_layer',
 *   '#prefix' => $this->t('Geolocation Layer Render Element'),
 *   '#description' => $this->t('Render element type "geolocation_layer"'),
 *   'location_1' => [...],
 * ];
 * @endcode
 *
 * @RenderElement("geolocation_layer")
 */
class GeolocationLayer extends RenderElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    $class = get_class($this);

    return [
      '#process' => [
        [$class, 'processGroup'],
      ],
      '#pre_render' => [
        [$class, 'preRenderGroup'],
        [$this, 'preRenderLayer'],
      ],
    ];
  }

  /**
   * Map element.
   *
   * @param array $render_array
   *   Element.
   *
   * @return array
   *   Renderable layer.
   */
  public function preRenderLayer(array $render_array): array {
    $render_array['#theme'] = 'geolocation_map_layer';

    $render_array['#cache'] = array_merge_recursive(
      $render_array['#cache'] ?? [],
      ['contexts' => ['languages:language_interface']]
    );

    if (empty($render_array['#id'])) {
      $render_array['#id'] = uniqid('layer-');
    }

    if (!empty($render_array['#children'])) {
      uasort($render_array['#children'], [
        SortArray::class,
        'sortByWeightProperty',
      ]);
    }

    foreach (Element::children($render_array) as $child) {
      $render_array['#children'][$child] = $render_array[$child];
      unset($render_array[$child]);
    }

    $render_array['#attributes'] = new Attribute($render_array['#attributes'] ?? []);
    $render_array['#attributes']->addClass('geolocation-map-layer');
    $render_array['#attributes']->setAttribute('id', $render_array['#id']);

    return $render_array;
  }

}
