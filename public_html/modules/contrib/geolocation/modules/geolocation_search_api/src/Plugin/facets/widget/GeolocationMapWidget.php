<?php

namespace Drupal\geolocation_search_api\Plugin\facets\widget;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\Plugin\facets\facet_source\SearchApiDisplay;
use Drupal\facets\Widget\WidgetPluginBase;
use Drupal\geolocation\MapCenterManager;
use Drupal\geolocation\MapProviderManager;
use Drupal\geolocation_search_api\Plugin\facets\query_type\GeolocationBoundary;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Utility\QueryHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Map of the current results, filtering by the map boundaries on request.
 *
 * @FacetsWidget(
 *   id = "geolocation_map",
 *   label = @Translation("Geolocation map"),
 *   description = @Translation("Shows the current results on a map, which can then be used to filter by its boundaries."),
 * )
 */
class GeolocationMapWidget extends WidgetPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a GeolocationMapWidget object.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\geolocation\MapProviderManager $mapProviderManager
   *   Map provider manager.
   * @param \Drupal\geolocation\MapCenterManager $mapCenterManager
   *   Map center manager.
   * @param \Drupal\search_api\Utility\QueryHelperInterface $queryHelper
   *   Search API query helper.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected MapProviderManager $mapProviderManager,
    protected MapCenterManager $mapCenterManager,
    protected QueryHelperInterface $queryHelper,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.geolocation.mapprovider'),
      $container->get('plugin.manager.geolocation.mapcenter'),
      $container->get('search_api.query_helper'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'map_provider_id' => '',
      'map_provider_settings' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryType() {
    return 'geolocation_boundary';
  }

  /**
   * {@inheritdoc}
   */
  public function isPropertyRequired($name, $type) {
    return $name === 'show_only_one_result' && $type === 'settings';
  }

  /**
   * {@inheritdoc}
   */
  public function supportsFacet(FacetInterface $facet) {
    $facet_source = $facet->getFacetSource();
    if (!$facet_source instanceof SearchApiDisplay) {
      return FALSE;
    }

    $field = $facet_source->getIndex()->getField($facet->getFieldIdentifier());

    return $field && in_array($field->getType(), ['geolocation_coordinates', 'geolocation_geometry']);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $map_provider_id = $this->getMapProviderId();
    if (!$map_provider_id || !$this->mapProviderManager->hasDefinition($map_provider_id)) {
      return [];
    }

    return ['module' => [$this->mapProviderManager->getDefinition($map_provider_id)['provider']]];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    $map_provider_options = $this->mapProviderManager->getMapProviderOptions();

    if (empty($map_provider_options)) {
      $form['map_provider_id'] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => $this->t('No map provider found.'),
      ];
      return $form;
    }

    $parents = ['widget_config'];
    $configuration = $this->getConfiguration();
    $user_input = $form_state->getUserInput();

    $map_provider_id = NestedArray::getValue($user_input, array_merge($parents, ['map_provider_id']));
    $map_provider_reset = $map_provider_id && $map_provider_id !== $configuration['map_provider_id'];
    if (!$map_provider_id || !isset($map_provider_options[$map_provider_id])) {
      $map_provider_id = $this->getMapProviderId() ?: key($map_provider_options);
    }

    $form['map_provider_id'] = [
      '#type' => 'select',
      '#options' => $map_provider_options,
      '#title' => $this->t('Map provider'),
      '#default_value' => $map_provider_id,
      '#ajax' => [
        'callback' => [
          get_class($this->mapProviderManager),
          'addSettingsFormAjax',
        ],
        'wrapper' => 'map-provider-settings',
        'effect' => 'fade',
      ],
    ];

    $map_provider_definition = $this->mapProviderManager->getDefinition($map_provider_id);
    if ($map_provider_reset) {
      $map_provider_settings = $map_provider_definition['class']::getDefaultSettings();
      NestedArray::setValue($user_input, array_merge($parents, ['map_provider_settings']), $map_provider_settings);
      $form_state->setUserInput($user_input);
    }
    elseif ($configuration['map_provider_settings']) {
      $map_provider_settings = $configuration['map_provider_settings'];
    }
    else {
      $map_provider_settings = $map_provider_definition['class']::getDefaultSettings();
    }

    $form['map_provider_settings'] = $this->mapProviderManager
      ->createInstance($map_provider_id, $map_provider_settings)
      ->getSettingsForm(
        $map_provider_settings,
        array_merge($parents, ['map_provider_settings'])
      );

    $form['map_provider_settings'] = array_replace(
      $form['map_provider_settings'],
      [
        '#prefix' => '<div id="map-provider-settings">',
        '#suffix' => '</div>',
      ]
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet) {
    $active_value = GeolocationBoundary::getActiveValue($facet->getActiveItems());

    $placeholder_url = NULL;
    $reset_url = NULL;
    foreach ($facet->getResults() as $result) {
      if ($result->getRawValue() === GeolocationBoundary::PLACEHOLDER) {
        $placeholder_url = $result->getUrl();
      }
      elseif ($active_value !== NULL && $result->getRawValue() === $active_value) {
        $reset_url = $result->getUrl();
      }
    }

    $map_id = Html::getId('geolocation-facet-map-' . $facet->id());

    $map = [
      '#type' => 'geolocation_map',
      '#id' => $map_id,
      '#maptype' => $this->getMapProviderId(),
      '#settings' => $this->getConfiguration()['map_provider_settings'],
    ];

    foreach ($this->getResultLocations($facet) as $delta => $location) {
      $map['locations'][$delta] = $location;
    }

    $centre = [
      'fit_bounds' => [
        'enable' => TRUE,
        'map_center_id' => 'fit_bounds',
        'weight' => 0,
      ],
    ];

    $boundary = GeolocationBoundary::getActiveBoundary($facet->getActiveItems());
    if ($boundary) {
      // Without results to fit to, the boundary filtered for is the next best.
      $centre['fixed_boundaries'] = [
        'enable' => TRUE,
        'map_center_id' => 'fixed_boundaries',
        'weight' => 1,
        'settings' => $boundary,
      ];
    }

    $map = $this->mapCenterManager->alterMap($map, $centre);

    $build = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['geolocation-facet-map'],
        'data-drupal-facet-id' => $facet->id(),
        'data-drupal-facet-alias' => $facet->getUrlAlias(),
      ],
      'map' => $map,
      'actions' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['geolocation-facet-map-actions'],
        ],
      ],
      '#attached' => [
        'library' => [
          'geolocation_search_api/facet_map',
        ],
      ],
    ];

    if ($placeholder_url) {
      $build['actions']['filter'] = [
        '#type' => 'html_tag',
        '#tag' => 'button',
        '#value' => $this->t('Filter by map area'),
        '#attributes' => [
          'type' => 'button',
          'class' => [
            'button',
            'button--primary',
            'geolocation-facet-map-filter',
          ],
          'disabled' => 'disabled',
          'data-map-id' => $map_id,
          'data-url' => $placeholder_url->toString(),
          'data-placeholder' => GeolocationBoundary::PLACEHOLDER,
        ],
      ];
    }

    if ($reset_url) {
      $build['actions']['reset'] = [
        '#type' => 'link',
        '#title' => $this->t('Reset'),
        '#url' => $reset_url,
        // Styled like core's cancel buttons, a link works without JavaScript.
        // @see \Drupal\Core\Form\ConfirmFormHelper::buildCancelLink()
        '#attributes' => [
          'class' => [
            'button',
            'geolocation-facet-map-reset',
          ],
          'rel' => 'nofollow',
        ],
      ];
    }

    return $build;
  }

  /**
   * Returns the configured map provider, if still available.
   *
   * @return string|null
   *   Map provider ID.
   */
  protected function getMapProviderId(): ?string {
    $map_provider_id = $this->getConfiguration()['map_provider_id'] ?? NULL;
    if ($map_provider_id && $this->mapProviderManager->hasDefinition($map_provider_id)) {
      return $map_provider_id;
    }

    return array_key_first($this->mapProviderManager->getMapProviderOptions());
  }

  /**
   * Returns a map location per result on the current page.
   *
   * @param \Drupal\facets\FacetInterface $facet
   *   Facet.
   *
   * @return array
   *   Map location render arrays.
   */
  protected function getResultLocations(FacetInterface $facet): array {
    $facet_source = $facet->getFacetSource();
    if (!$facet_source instanceof SearchApiDisplay) {
      return [];
    }

    $results = $this->queryHelper->getResults($facet_source->getDisplay()->getPluginId());
    if (!$results) {
      return [];
    }

    // Views has usually loaded the entities already, for its access checks.
    $results->preLoadResultItems();

    $locations = [];
    foreach ($results->getResultItems() as $item) {
      $field = $item->getField($facet->getFieldIdentifier());
      if (!$field) {
        continue;
      }

      foreach ($field->getValues() as $value) {
        $coordinates = static::parsePoint((string) $value);
        if (!$coordinates) {
          continue;
        }

        $locations[] = [
          '#type' => 'geolocation_map_location',
          '#coordinates' => $coordinates,
          '#title' => $this->getItemLabel($item),
        ];
      }
    }

    return $locations;
  }

  /**
   * Returns the label of a result item.
   *
   * @param \Drupal\search_api\Item\ItemInterface $item
   *   Result item.
   *
   * @return string
   *   Label, empty if unavailable.
   */
  protected function getItemLabel(ItemInterface $item): string {
    try {
      $entity = $item->getOriginalObject()?->getValue();
    }
    catch (\Throwable) {
      return '';
    }

    return $entity instanceof EntityInterface ? (string) $entity->label() : '';
  }

  /**
   * Parses an indexed value into coordinates.
   *
   * Coordinates are indexed as "lat,lng", geometries as WKT. Backends may
   * return either, or the GeoJSON originally extracted. Only points are
   * placed on the map.
   *
   * @param string $value
   *   Indexed value.
   *
   * @return array|null
   *   Coordinates keyed lat and lng.
   */
  public static function parsePoint(string $value): ?array {
    $value = trim($value);

    if (preg_match('/^(-?[\d.]+)\s*,\s*(-?[\d.]+)$/', $value, $matches)) {
      [, $lat, $lng] = $matches;
    }
    elseif (preg_match('/^POINT\s*\(\s*(-?[\d.]+)\s+(-?[\d.]+)\s*\)$/i', $value, $matches)) {
      [, $lng, $lat] = $matches;
    }
    elseif (($geojson = json_decode($value, TRUE)) && ($geojson['type'] ?? NULL) === 'Point') {
      [$lng, $lat] = $geojson['coordinates'] ?? [NULL, NULL];
    }
    else {
      return NULL;
    }

    if (!is_numeric($lat) || !is_numeric($lng)) {
      return NULL;
    }

    return [
      'lat' => (float) $lat,
      'lng' => (float) $lng,
    ];
  }

}
