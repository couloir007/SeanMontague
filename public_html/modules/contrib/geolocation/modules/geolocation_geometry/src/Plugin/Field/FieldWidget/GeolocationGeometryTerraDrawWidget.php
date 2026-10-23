<?php

namespace Drupal\geolocation_geometry\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\geolocation_geometry\Plugin\geolocation\MapFeature\GeolocationGeometryTerraDrawConnectorBase;

/**
 * Plugin implementation of the 'geolocation_geometry_terra_draw' widget.
 *
 * Terra Draw can only be used where it has an adapter for the selected map
 * provider, which the settings form and summary have to point out before a
 * site builder saves a map nobody can draw on.
 */
#[FieldWidget(
  id: 'geolocation_geometry_terra_draw',
  label: new \Drupal\Core\StringTranslation\TranslatableMarkup('Geolocation Geometry Terra Draw'),
  field_types: [
    'geolocation_geometry_point',
    'geolocation_geometry_linestring',
    'geolocation_geometry_polygon',
  ]
)]
class GeolocationGeometryTerraDrawWidget extends GeolocationGeometryMapWidget {

  /**
   * {@inheritdoc}
   */
  protected function getWidgetFeatureId(): string {
    return 'geolocation_geometry_terra_draw_connector';
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $element = parent::settingsForm($form, $form_state);

    // No map provider available at all, the parent returned a bare message.
    if (isset($element['#type'])) {
      return $element;
    }

    $labels = [];
    foreach (GeolocationGeometryTerraDrawConnectorBase::getSupportedMapProviders() as $map_provider_id) {
      $definition = $this->mapProviderManager->getDefinition($map_provider_id, FALSE);
      $labels[] = (string) ($definition['name'] ?? $map_provider_id);
    }

    $element['terra_draw_adapters'] = [
      '#type' => 'item',
      // Item elements take input by default, which would store this hint as a
      // widget setting no schema declares.
      '#input' => FALSE,
      '#weight' => -1,
      '#markup' => $this->t('Drawing requires a map provider Terra Draw ships an adapter for. Currently supported: %providers.', [
        '%providers' => implode(', ', $labels),
      ]),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = parent::settingsSummary();

    if ($this->mapProvider && !GeolocationGeometryTerraDrawConnectorBase::getAdapter($this->mapProvider->getPluginId())) {
      array_unshift($summary, $this->t('ATTENTION: Terra Draw provides no adapter for this map provider, drawing will be unavailable.'));
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = NULL): array {
    $element = parent::form($items, $form, $form_state, $get_delta);

    $element['map_description']['#description'] = $this->t('Use <em>Draw</em> to add a geometry to the map - a point is placed with a single click, a line or polygon is finished with a double click. Use <em>Edit</em> to select a geometry, then move it or its points, or remove it with <em>Delete</em>.');

    return $element;
  }

}
