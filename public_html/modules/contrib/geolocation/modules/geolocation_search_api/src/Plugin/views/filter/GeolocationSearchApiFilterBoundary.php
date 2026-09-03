<?php

namespace Drupal\geolocation_search_api\Plugin\views\filter;

use Drupal\views\Attribute\ViewsFilter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\geolocation\BoundaryTrait;
use Drupal\geolocation\GeocoderManager;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter handler for search keywords.
 *
 * @ingroup views_filter_handlers
 */
#[ViewsFilter(id: 'geolocation_search_api_filter_boundary')]
class GeolocationSearchApiFilterBoundary extends FilterPluginBase implements ContainerFactoryPluginInterface {

  use BoundaryTrait;

  /**
   * {@inheritdoc}
   */
  public $no_operator = TRUE;

  /**
   * Can be used for CommonMap interactive filtering.
   *
   * @var bool
   */
  public bool $isGeolocationCommonMapOption = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $alwaysMultiple = TRUE;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected GeocoderManager $geocoderManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): FilterPluginBase {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.geolocation.geocoder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary(): string {
    return $this->t("Boundary filter");
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions(): array {
    $options = parent::defineOptions();

    $options['expose']['contains']['input_by_geocoding_widget'] = ['default' => FALSE];
    $options['expose']['contains']['geocoder'] = ['default' => FALSE];
    $options['expose']['contains']['geocoder_settings'] = ['default' => FALSE];

    return $options;
  }

  /**
   * {@inheritdoc}
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function buildExposeForm(&$form, FormStateInterface $form_state): void {

    parent::buildExposeForm($form, $form_state);
    $form['expose']['#type'] = 'container';

    $geocoder_options = [];
    foreach ($this->geocoderManager->getDefinitions() as $id => $definition) {
      if (empty($definition['frontendCapable'])) {
        continue;
      }
      $geocoder_options[$id] = $definition['name'];
    }

    if (!$geocoder_options) {
      return;
    }

    $form['expose']['input_by_geocoding_widget'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use geocoding widget to retrieve boundary values'),
      '#default_value' => $this->options['expose']['input_by_geocoding_widget'],
    ];

    $form['expose']['geocoder'] = $this->geocoderManager->geocoderOptionsSelect($geocoder_options);
    $form['expose']['geocoder'] = array_merge($form['expose']['geocoder'], [
      '#default_value' => $this->options['expose']['geocoder'],
      '#states' => [
        'visible' => [
          'input[name="options[expose][input_by_geocoding_widget]"]' => ['checked' => TRUE],
        ],
      ],
    ]);

    $geocoder_plugin = $this->geocoderManager->getGeocoder(
      $this->options['expose']['geocoder'] ?? current(array_keys($geocoder_options)),
      $this->options['expose']['geocoder_settings'] ?: []
    );

    if (empty($geocoder_plugin)) {
      $form['expose']['geocoder_settings'] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => t("No settings available."),
      ];
    }
    else {
      $form['expose']['geocoder_settings'] = $geocoder_plugin->getOptionsForm();
    }
    $form['expose']['geocoder_settings'] = array_merge($form['expose']['geocoder_settings'], [
      '#states' => [
        'visible' => [
          'input[name="options[expose][input_by_geocoding_widget]"]' => ['checked' => TRUE],
        ],
      ],
    ]);
  }

  /**
   * {@inheritdoc}
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state): void {
    parent::buildExposedForm($form, $form_state);

    $identifier = $this->options['expose']['identifier'];
    if (empty($form[$identifier . '_wrapper'][$identifier])) {
      return;
    }
    $form[$identifier . '_wrapper']['#tree'] = FALSE;
    $form[$identifier . '_wrapper'][$identifier]['#tree'] = TRUE;

    if (
      !$this->options['expose']['input_by_geocoding_widget']
      || empty($this->options['expose']['geocoder'])
    ) {
      return;
    }

    $geocoder_plugin = $this->geocoderManager->getGeocoder(
      $this->options['expose']['geocoder'],
      $this->options['expose']['geocoder_settings'] ?: []
    );

    if (empty($geocoder_plugin)) {
      return;
    }

    $form[$identifier . '_wrapper'][$identifier]['lat_north_east']['#type'] = 'hidden';
    $form[$identifier . '_wrapper'][$identifier]['lng_north_east']['#type'] = 'hidden';
    $form[$identifier . '_wrapper'][$identifier]['lat_south_west']['#type'] = 'hidden';
    $form[$identifier . '_wrapper'][$identifier]['lng_south_west']['#type'] = 'hidden';

    $geocoder_plugin->alterRenderArray($form[$identifier . '_wrapper'][$identifier], $identifier);

    $form = BubbleableMetadata::mergeAttachments($form, [
      '#attached' => [
        'library' => [
          'geolocation/geolocation.views.filter.geocoder',
        ],
        'drupalSettings' => [
          'geolocation' => [
            'geocoder' => [
              'viewsFilterGeocoder' => [
                $identifier => [
                  'settings' => $geocoder_plugin->getSettings(),
                ],
              ],
            ],
          ],
        ],
      ],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function acceptExposedInput($input): bool {
    if (!parent::acceptExposedInput($input)) {
      return FALSE;
    }

    if ($this->isBoundarySet($this->value)) {
      return TRUE;
    }

    $identifier = $this->options['expose']['identifier'];
    if (
      empty($input[$identifier]['geolocation_geocoder_address'])
      || empty($this->options['expose']['input_by_geocoding_widget'])
      || empty($this->options['expose']['geocoder'])
    ) {
      return FALSE;
    }

    $geocoder_plugin = $this->geocoderManager->getGeocoder(
      $this->options['expose']['geocoder'],
      $this->options['expose']['geocoder_settings']
    );

    if (empty($geocoder_plugin)) {
      return FALSE;
    }

    $location_data = $geocoder_plugin->geocode($input[$this->options['expose']['identifier']]['geolocation_geocoder_address']);

    // Location geocoded server-side. Add to input for later processing.
    if (!empty($location_data['boundary'])) {
      $this->value = array_replace($input[$identifier], $location_data['boundary']);
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  protected function valueForm(&$form, FormStateInterface $form_state): void {
    parent::valueForm($form, $form_state);

    $form['value']['#type'] = 'container';
    $form['value']['#tree'] = TRUE;

    $value_element = &$form['value'];

    // Add the Latitude and Longitude elements.
    $value_element += [
      'lat_north_east' => [
        '#type' => 'textfield',
        '#title' => $this->t('North East Boundary - Latitude'),
        '#default_value' => !empty($this->value['lat_north_east']) ? $this->value['lat_north_east'] : '',
        '#size' => 30,
        '#weight' => 10,
      ],
      'lng_north_east' => [
        '#type' => 'textfield',
        '#title' => $this->t('North East Boundary - Longitude'),
        '#default_value' => !empty($this->value['lng_north_east']) ? $this->value['lng_north_east'] : '',
        '#size' => 30,
        '#weight' => 20,
      ],
      'lat_south_west' => [
        '#type' => 'textfield',
        '#title' => $this->t('South West Boundary - Latitude'),
        '#default_value' => !empty($this->value['lat_south_west']) ? $this->value['lat_south_west'] : '',
        '#size' => 30,
        '#weight' => 30,
      ],
      'lng_south_west' => [
        '#type' => 'textfield',
        '#title' => $this->t('South West Boundary - Longitude'),
        '#default_value' => !empty($this->value['lng_south_west']) ? $this->value['lng_south_west'] : '',
        '#size' => 30,
        '#weight' => 40,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query(): void {
    if (empty($this->value)) {
      return;
    }

    // Get the field alias.
    $lat_north_east = $this->value['lat_north_east'];
    $lng_north_east = $this->value['lng_north_east'];
    $lat_south_west = $this->value['lat_south_west'];
    $lng_south_west = $this->value['lng_south_west'];

    if (
      !is_numeric($lat_north_east)
      || !is_numeric($lng_north_east)
      || !is_numeric($lat_south_west)
      || !is_numeric($lng_south_west)
    ) {
      return;
    }

    /** @var \Drupal\search_api\Query\Query $query */
    $query = $this->query;

    $location_options = [
      [
        'field' => $this->realField,
        'geom' => "[$lat_south_west,$lng_south_west TO $lat_north_east,$lng_north_east]",
      ],
    ];
    $query->setOption('search_api_rpt', $location_options);
  }

  /**
   * Determine if boundary is set.
   *
   * @param mixed $values
   *   Value array.
   *
   * @return bool
   *   Boundary set or not.
   */
  private function isBoundarySet(mixed $values): bool {
    if (!is_array($values)) {
      return FALSE;
    }

    if (
      isset($values['lat_north_east'])
      && is_numeric($values['lat_north_east'])
      && isset($values['lng_north_east'])
      && is_numeric($values['lng_north_east'])
      && isset($values['lat_south_west'])
      && is_numeric($values['lat_south_west'])
      && isset($values['lng_south_west'])
      && is_numeric($values['lng_south_west'])
    ) {
      return TRUE;
    }

    return FALSE;
  }

}
