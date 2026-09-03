<?php

namespace Drupal\geolocation\Element;

use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\RenderElementBase;
use Drupal\Core\Template\Attribute;

/**
 * Provides a render element for a single geolocation map location.
 *
 * Usage example:
 *
 * @code
 * $form['map'] = [
 *   '#type' => 'geolocation_map_shape',
 *   '#geometry' => [[[1,1],[2,2],[3,3]], [[4,4],[5,5],[6,6]]],
 *   '#id' => NULL,
 *   '#stroke_color' => NULL,
 *   '#stroke_width' => NULL,
 *   '#stroke_opacity' => NULL,
 *   '#fill_color' => NULL,
 *   '#fill_opacity' => NULL,
 * ];
 * @endcode
 *
 * @RenderElement("geolocation_map_shape")
 */
class GeolocationMapShape extends RenderElementBase {

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
        [$this, 'preRenderGeolocationShape'],
      ],
      '#title' => NULL,
      '#geometry' => NULL,
      '#geometry_type' => NULL,
      '#id' => NULL,
      '#stroke_color' => NULL,
      '#stroke_width' => NULL,
      '#stroke_opacity' => NULL,
      '#fill_color' => NULL,
      '#fill_opacity' => NULL,
    ];
  }

  /**
   * Shape element.
   *
   * @param array $render_array
   *   Element.
   *
   * @return array
   *   Renderable map.
   */
  public function preRenderGeolocationShape(array $render_array): array {
    $render_array['#theme'] = 'geolocation_map_shape';

    $render_array['#attributes'] = new Attribute($render_array['#attributes'] ?? []);
    $render_array['#attributes']->addClass('geolocation-geometry');
    $render_array['#attributes']->addClass('js-hide');

    $render_array['#attributes']->setAttribute('id', $render_array['#id'] ?? uniqid('geometry-'));
    $render_array['#attributes']->setAttribute('data-geometry-type', $render_array['#geometry_type'] ?? '');
    $render_array['#attributes']->setAttribute('data-stroke-color', $render_array['#stroke_color'] ?? '#0000FF');
    $render_array['#attributes']->setAttribute('data-stroke-width', $render_array['#stroke_width'] ?? 2);
    $render_array['#attributes']->setAttribute('data-stroke-opacity', $render_array['#stroke_opacity'] ?? 1);
    $render_array['#attributes']->setAttribute('data-fill-color', $render_array['#fill_color'] ?? $render_array['#stroke_color'] ?? '#0000FF');
    $render_array['#attributes']->setAttribute('data-fill-opacity', $render_array['#fill_opacity'] ?? 0.2);

    foreach (Element::children($render_array) as $child) {
      $render_array['#children'][] = $render_array[$child];
    }

    return $render_array;
  }

}
