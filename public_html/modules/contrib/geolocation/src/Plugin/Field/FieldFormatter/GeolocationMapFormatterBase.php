<?php

namespace Drupal\geolocation\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\filter\Entity\FilterFormat;
use Drupal\geolocation\DataProviderInterface;
use Drupal\geolocation\DataProviderManager;
use Drupal\geolocation\FilterFormatProvider;
use Drupal\geolocation\MapCenterManager;
use Drupal\geolocation\MapProviderInterface;
use Drupal\geolocation\MapProviderManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin base for Map based formatters.
 */
abstract class GeolocationMapFormatterBase extends FormatterBase {

  use DependencySerializationTrait;

  /**
   * Map provider.
   *
   * @var \Drupal\geolocation\MapProviderInterface|null
   */
  protected ?MapProviderInterface $mapProvider = NULL;

  /**
   * Data provider ID.
   *
   * @var string
   */
  protected static string $dataProviderId = 'geolocation_field_provider';

  /**
   * Data provider.
   *
   * @var \Drupal\geolocation\DataProviderInterface
   */
  protected DataProviderInterface $dataProvider;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    protected MapProviderManager $mapProviderManager,
    protected MapCenterManager $mapCenterManager,
    protected DataProviderManager $data_provider_manager,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $settings = $this->getSettings();

    if (!empty($settings['map_provider_id'])) {
      $this->mapProvider = $this->mapProviderManager->getMapProvider($settings['map_provider_id'], $settings['map_provider_settings']);
    }

    $this->dataProvider = $data_provider_manager->createInstance(static::$dataProviderId, $settings['data_provider_settings']);
    $this->dataProvider->setFieldDefinition($field_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): GeolocationMapFormatterBase {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('plugin.manager.geolocation.mapprovider'),
      $container->get('plugin.manager.geolocation.mapcenter'),
      $container->get('plugin.manager.geolocation.dataprovider'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    $settings = parent::defaultSettings();
    $settings['title'] = '';
    $settings['set_marker'] = TRUE;
    $settings['show_label'] = FALSE;
    $settings['show_delta_label'] = FALSE;
    $settings['common_map'] = TRUE;
    $settings['data_provider_settings'] = [];
    $settings['map_provider_id'] = '';
    if (\Drupal::moduleHandler()->moduleExists('geolocation_google_maps')) {
      $settings['map_provider_id'] = 'google_maps';
    }
    elseif (\Drupal::moduleHandler()->moduleExists('geolocation_leaflet')) {
      $settings['map_provider_id'] = 'leaflet';
    }
    $settings['centre'] = [
      'fit_bounds' => [
        'enable' => TRUE,
        'weight' => -101,
        'map_center_id' => 'fit_bounds',
      ],
    ];
    $settings['map_provider_settings'] = [];
    $settings['info_text'] = [
      'value' => '',
      'format' => FilterFormatProvider::getDefaultFormatId(),
    ];
    $settings['use_overridden_map_settings'] = FALSE;
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $map_provider_options = $this->mapProviderManager->getMapProviderOptions();

    if (empty($map_provider_options)) {
      return [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => $this->t("No map provider found."),
      ];
    }

    $settings = $this->getSettings();

    $element = [];

    $data_provider_settings_form = $this->dataProvider->getSettingsForm($settings['data_provider_settings']);
    if (!empty($data_provider_settings_form)) {
      $element['data_provider_settings'] = $data_provider_settings_form;
    }

    $element['set_marker'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Set map marker'),
      '#default_value' => $settings['set_marker'],
    ];

    $element['show_label'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show   label'),
      '#default_value' => $settings['show_label'],
    ];

    $element['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Marker title'),
      '#description' => $this->t('When the cursor hovers on the marker, this title will be shown as description.'),
      '#default_value' => $settings['title'],
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][set_marker]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $element['info_text'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Marker info text'),
      '#description' => $this->t('When the marker is clicked, this text will be shown in a popup above it. Leave blank to not display. Token replacement supported.'),
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][set_marker]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    if (!empty($settings['info_text']['value'])) {
      $element['info_text']['#default_value'] = $settings['info_text']['value'];
    }

    if (!empty($settings['info_text']['format'])) {
      $element['info_text']['#format'] = $settings['info_text']['format'];
    }

    $element['replacement_patterns'] = [
      '#type' => 'details',
      '#title' => 'Replacement patterns',
      '#description' => $this->t('The following replacement patterns are available.'),
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][set_marker]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $element['replacement_patterns']['token_geolocation'] = $this->dataProvider->getTokenHelp();

    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    if (
      $cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED
      || $cardinality > 1
    ) {
      $element['common_map'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Display multiple values on a common map'),
        '#description' => $this->t('By default, each value will be displayed in a separate map. Settings this option displays all values on a common map instead. This settings is only useful on multi-value fields.'),
        '#default_value' => $settings['common_map'],
      ];
      $element['show_delta_label'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Show item cardinality as marker label'),
        '#description' => $this->t('By default markers will not have labels, if shown on the common map it might be useful for AODA to show cardinality'),
        '#default_value' => $settings['show_delta_label'],
      ];
    }

    $element['centre'] = $this->mapCenterManager->getCenterOptionsForm((array) $settings['centre'], ['formatter' => $this]);

    $element['map_provider_id'] = [
      '#type' => 'select',
      '#options' => $map_provider_options,
      '#title' => $this->t('Map Provider'),
      '#default_value' => $settings['map_provider_id'],
      '#ajax' => [
        'callback' => [
          get_class($this->mapProviderManager), 'addSettingsFormAjax',
        ],
        'wrapper' => 'map-provider-settings',
        'effect' => 'fade',
      ],
    ];

    // The map provider form has to know its own form parents, which depend on
    // the form embedding the formatter - Field UI nests it below
    // fields[<field>][settings_edit_form][settings], Views UI below options.
    // Only #process runs late enough to see the parents the form builder
    // actually assigned.
    // No #type: the map provider form below carries the same #parents, and two
    // group-capable elements sharing them make Drupal's group pre-render inject
    // the provider form twice.
    $element['map_provider_settings'] = [
      '#tree' => TRUE,
      '#prefix' => '<div id="map-provider-settings">',
      '#suffix' => '</div>',
      '#process' => [[static::class, 'processMapProviderSettings']],
      '#map_provider_options' => $map_provider_options,
      '#map_provider_id' => $settings['map_provider_id'],
      '#map_provider_settings' => $settings['map_provider_settings'],
    ];

    $element['use_overridden_map_settings'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use custom map settings if provided'),
      '#description' => $this->t('The field map widget optionally allows to define custom map settings to use here.'),
      '#default_value' => $settings['use_overridden_map_settings'],
    ];

    return $element;
  }

  /**
   * Form #process callback to attach the selected map provider settings form.
   *
   * @param array $element
   *   Element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param array $complete_form
   *   Complete form.
   *
   * @return array
   *   Element.
   */
  public static function processMapProviderSettings(array &$element, FormStateInterface $form_state, array &$complete_form): array {
    $map_provider_options = $element['#map_provider_options'];

    // The map provider select is this element's sibling, so its input can be
    // read relative to the parents assigned by the form builder.
    $map_provider_id_parents = $element['#parents'];
    array_pop($map_provider_id_parents);
    $map_provider_id_parents[] = 'map_provider_id';

    $map_provider_id = NestedArray::getValue($form_state->getUserInput(), $map_provider_id_parents);
    if (empty($map_provider_id)) {
      $map_provider_id = $element['#map_provider_id'];
    }
    if (empty($map_provider_id)) {
      $map_provider_id = key($map_provider_options);
    }

    // On a map provider switch the submitted settings still describe the
    // previous provider - control positions for instance are provider
    // specific - so they must not be fed into the new provider's form.
    $triggering_element = $form_state->getTriggeringElement();
    if (($triggering_element['#parents'] ?? NULL) === $map_provider_id_parents) {
      $user_input = $form_state->getUserInput();
      NestedArray::unsetValue($user_input, $element['#parents']);
      $form_state->setUserInput($user_input);
    }

    /** @var \Drupal\geolocation\MapProviderManager $map_provider_manager */
    $map_provider_manager = \Drupal::service('plugin.manager.geolocation.mapprovider');
    $map_provider = $map_provider_id ? $map_provider_manager->getMapProvider($map_provider_id, $element['#map_provider_settings']) : NULL;

    if (!$map_provider) {
      $element['empty'] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => t("No settings available."),
      ];

      return $element;
    }

    // Seed the form from the stored settings only. Anything the user already
    // entered is restored by the form builder from the input it collected for
    // these very parents.
    $element['form'] = $map_provider->getSettingsForm($element['#map_provider_settings'], $element['#parents']);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $settings = $this->getSettings();

    $summary = [];
    $summary[] = $this->t('Marker set: @marker', ['@marker' => $settings['set_marker'] ? $this->t('Yes') : $this->t('No')]);
    if ($settings['set_marker']) {
      $summary[] = $this->t('Marker Title: @type', ['@type' => $settings['title']]);
      if ($settings['show_label']) {
        $summary[] = $this->t('Showing Marker Label');
      }
      if (
        !empty($settings['info_text']['value'])
        && !empty($settings['info_text']['format'])
      ) {
        $build = [
          '#type' => 'processed_text',
          '#text' => $settings['info_text']['value'],
          '#format' => $settings['info_text']['format'],
        ];

        $summary[] = $this->t('Marker Info Text: @type', [
          '@type' => current(explode(chr(10), wordwrap(\Drupal::service('renderer')->renderInIsolation($build), 30))),
        ]);
      }

      if (!empty($settings['common_map'])) {
        $summary[] = $this->t('Common Map Display: Yes');
      }
      if (!empty($settings['show_delta_label'])) {
        $summary[] = $this->t('Show Cardinality as Label: Yes');
      }
    }

    if ($this->mapProvider ?? NULL) {
      return array_replace_recursive($summary, $this->mapProvider->getSettingsSummary($settings['map_provider_settings']));
    }

    $summary[] = $this->t('Attention: No map provider set.');

    \Drupal::messenger()->addWarning('Geolocation Formatter - Map: No map provider set. Map cannot be displayed.');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    if (!($this->mapProvider ?? NULL)) {
      \Drupal::logger('geolocation')->warning('No map provider set. No map can be displayed.');
      return [];
    }

    if ($items->count() == 0) {
      return [];
    }

    $elements = [];

    $settings = $this->getSettings();

    $map_objects = $this->getMapObjects($items);

    $parent_entity = $items->getEntity();

    $element_pattern = [
      '#type' => 'geolocation_map',
      '#settings' => $settings['map_provider_settings'],
      '#maptype' => $settings['map_provider_id'],
      '#centre' => [],
      '#context' => [
        'formatter' => $this,
      ],
    ];

    $element_pattern['#context'][$parent_entity->getEntityTypeId()] = $parent_entity;

    if (!empty($settings['common_map'])) {
      $elements = [
        0 => $element_pattern,
      ];
      $elements[0]['#id'] = uniqid("map-");
      foreach ($map_objects as $delta => $object) {
        if (!empty($settings['show_delta_label'])) {
          $object['#label'] = $delta + 1;
        }
        $elements[0][$delta] = $object;
      }

      $elements[0] = $this->mapCenterManager->alterMap($elements[0], $settings['centre'], ['formatter' => $this]);
    }
    else {
      foreach ($map_objects as $delta => $object) {
        $elements[$delta] = $element_pattern;
        $elements[$delta]['#id'] = uniqid("map-" . $delta . "-");
        $elements[$delta]['content'] = $object;

        $elements[$delta] = $this->mapCenterManager->alterMap($elements[$delta], $settings['centre'], ['formatter' => $this]);
      }
    }

    if (
      $settings['use_overridden_map_settings']
      && !empty($items->get(0))
      && !empty($items->get(0)->getValue()['data']['map_provider_settings'])
      && is_array($items->get(0)->getValue()['data']['map_provider_settings'])
    ) {
      $map_settings = $this->mapProvider->getSettings($items->get(0)->getValue()['data']['map_provider_settings']);

      if (!empty($settings['common_map'])) {
        $elements[0]['#settings'] = $map_settings;
      }
      else {
        foreach (Element::children($elements) as $delta => $element) {
          $elements[$delta]['#settings'] = $map_settings;
        }
      }
    }

    return $elements;
  }

  /**
   * Get renderable locations from field items.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   Field items.
   *
   * @return array
   *   Renderable locations.
   */
  protected function getMapObjects(FieldItemListInterface $items): array {
    $map_objects = [];

    foreach ($items as $delta => $item) {
      foreach ($this->dataProvider->getLocationsFromItem($item) as $location) {
        if (empty($location)) {
          continue;
        }

        $location['#weight'] = $delta;

        $map_objects[] = $this->addSettingsToLocation($location, $item);
      }

      $map_objects = array_merge($this->dataProvider->getShapesFromItem($item), $map_objects);
    }

    return $map_objects;
  }

  /**
   * Add settings to location.
   *
   * @param array $location
   *   Map location render array.
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   Field item.
   *
   * @return array
   *   Completed map location render array.
   */
  protected function addSettingsToLocation(array $location, FieldItemInterface $item): array {
    $settings = $this->getSettings();

    if (
      empty($location['#type'])
      || $location['#type'] != 'geolocation_map_location'
    ) {
      return $location;
    }

    if (empty($location['#coordinates'])) {
      return $location;
    }

    $title = $this->dataProvider->replaceFieldItemTokens($settings['title'], $item);
    if (empty($title)) {
      $title = $location['#coordinates']['lat'] . ', ' . $location['#coordinates']['lng'];
    }

    if (empty($location['#title'])) {
      $location['#title'] = $title;
    }

    if ($settings['show_label']) {
      $location['#label'] = $location['#title'];
    }

    $location['#disable_marker'] = empty($settings['set_marker']);

    if (
      !empty($settings['info_text']['value'])
      && !empty($settings['info_text']['format'])
    ) {
      $location['content'] = [
        '#type' => 'processed_text',
        '#text' => $this->dataProvider->replaceFieldItemTokens($settings['info_text']['value'], $item),
        '#format' => $settings['info_text']['format'],
      ];
    }

    return $location;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies(): array {
    $dependencies = parent::calculateDependencies();
    $settings = $this->getSettings();

    if (!empty($settings['info_text']['format'])) {
      $filter_format = FilterFormat::load($settings['info_text']['format']);
    }

    if (!empty($filter_format)) {
      $dependencies['config'][] = $filter_format->getConfigDependencyName();
    }
    return $dependencies;
  }

}
