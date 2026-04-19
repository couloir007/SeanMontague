<?php

namespace Drupal\leaflet_full_page\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RenderContext;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Serves geo_entity items as a GeoJSON FeatureCollection.
 *
 * Bundle is taken from the {bundle} route parameter, falling back to the
 * configured default in leaflet_full_page.settings. Field mapping and the
 * geofield name are also read from settings.
 */
class MapItemsController extends ControllerBase {

  /**
   * Dumps geo_entity items as a GeoJSON FeatureCollection.
   */
  public function dump(string $bundle = ''): JsonResponse {
    $config = $this->config('leaflet_full_page.settings');

    if (empty($bundle)) {
      $bundle = $config->get('bundle') ?: '';
    }

    if (empty($bundle)) {
      return new JsonResponse([
        'type' => 'FeatureCollection',
        'features' => [],
        'error' => 'No bundle configured. Set a default bundle at /admin/config/leaflet-full-page/settings.',
      ]);
    }

    $geo_field = $config->get('geo_field') ?: 'location';
    $field_map = $config->get('field_map') ?: [];

    $storage = $this->entityTypeManager()->getStorage('geo_entity');
    $label_key = $storage->getEntityType()->getKey('label');

    $ids = $storage->getQuery()
      ->condition('bundle', $bundle)
      ->accessCheck(FALSE)
      ->sort($label_key, 'ASC')
      ->execute();

    $entities = $storage->loadMultiple($ids);

    $renderer = \Drupal::service('renderer');
    $render_context = new RenderContext();
    $features = [];

    foreach ($entities as $entity) {
      $geometry = $this->buildGeometry($entity, $geo_field);

      $properties = ['title' => $entity->label()];

      foreach ($field_map as $json_key => $field_config) {
        $field_name = is_array($field_config) ? ($field_config['field'] ?? '') : (string) $field_config;
        $view_mode = is_array($field_config) ? ($field_config['view_mode'] ?? 'default') : 'default';

        if ($field_name) {
          $properties[$json_key] = $this->renderField($entity, $field_name, $view_mode, $renderer, $render_context);
        }
      }

      $features[] = [
        'type' => 'Feature',
        'id' => (string) $entity->id(),
        'geometry' => $geometry,
        'properties' => $properties,
      ];
    }

    return new JsonResponse([
      'type' => 'FeatureCollection',
      'features' => $features,
    ]);
  }

  /**
   * Builds a GeoJSON Point geometry from a Geofield item.
   */
  protected function buildGeometry($entity, string $geo_field): ?array {
    if (!$entity->hasField($geo_field) || $entity->get($geo_field)->isEmpty()) {
      return NULL;
    }
    $item = $entity->get($geo_field)->first();
    $lat = $item->get('lat')->getValue();
    $lon = $item->get('lon')->getValue();
    if ($lat === NULL || $lon === NULL) {
      return NULL;
    }
    return [
      'type' => 'Point',
      'coordinates' => [(float) $lon, (float) $lat],
    ];
  }

  /**
   * Renders a single field to a string, auto-detecting field type.
   */
  protected function renderField($entity, string $field_name, string $view_mode, $renderer, $render_context): string {
    if (!$entity->hasField($field_name) || $entity->get($field_name)->isEmpty()) {
      return '';
    }
    $field = $entity->get($field_name);
    $field_type = $field->getFieldDefinition()->getType();

    if (in_array($field_type, ['text_with_summary', 'text_long'], TRUE)) {
      $first = $field->first();
      $build = [
        '#type' => 'processed_text',
        '#text' => $first->value ?? '',
        '#format' => $first->format ?? 'full_html',
      ];
      return (string) $renderer->executeInRenderContext($render_context, function () use ($build, $renderer) {
        return $renderer->renderRoot($build);
      });
    }

    if (in_array($field_type, ['entity_reference', 'entity_reference_revisions'], TRUE)) {
      return $this->renderReferencedEntities($entity, $field_name, $view_mode);
    }

    $first = $field->first();
    $main = $first->mainPropertyName();
    return (string) ($first->{$main} ?? '');
  }

  /**
   * Renders all referenced entities for a field using their view builders.
   */
  protected function renderReferencedEntities($entity, string $field_name, string $view_mode): string {
    $field = $entity->get($field_name);
    $grouped = [];

    foreach ($field->referencedEntities() as $ref) {
      if ($ref) {
        $grouped[$ref->getEntityTypeId()][] = $ref;
      }
    }

    if (!$grouped) {
      return '';
    }

    $renderer = \Drupal::service('renderer');
    $output = '';

    foreach ($grouped as $type_id => $refs) {
      $view_builder = $this->entityTypeManager()->getViewBuilder($type_id);
      $build = $view_builder->viewMultiple($refs, $view_mode);
      $output .= (string) $renderer->renderPlain($build);
    }

    return $output;
  }

}
