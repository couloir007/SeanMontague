<?php

namespace Drupal\geolocation\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\geolocation\MapCenterManager;
use Drupal\geolocation\MapProviderInterface;
use Drupal\geolocation\MapProviderManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Base class for map-based field widgets.
 */
abstract class GeolocationMapWidgetBase extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * Map provider ID.
   *
   * If set (and valid), will fixate map provider and disable selection.
   */
  protected ?string $mapProviderId = NULL;

  /**
   * Map provider.
   */
  protected ?MapProviderInterface $mapProvider = NULL;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    protected MapCenterManager $mapCenterManager,
    protected MapProviderManager $mapProviderManager,
    protected ModuleHandlerInterface $moduleHandler,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->mapProvider = $this->getMapProvider();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('plugin.manager.geolocation.mapcenter'),
      $container->get('plugin.manager.geolocation.mapprovider'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function flagErrors(FieldItemListInterface $items, ConstraintViolationListInterface $violations, array $form, FormStateInterface $form_state): void {
    foreach ($violations as $violation) {
      if ($violation->getMessageTemplate() == 'This value should not be null.') {
        $form_state->setErrorByName($items->getName(), $this->t('No location has been selected yet for required field %field.', ['%field' => $items->getFieldDefinition()->getLabel()]));
      }
    }
    parent::flagErrors($items, $violations, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    $settings = parent::defaultSettings();

    $settings['hide_inputs'] = FALSE;
    $settings['allow_override_map_settings'] = FALSE;
    $settings['map_provider_id'] = NULL;
    $settings['map_provider_settings'] = [];
    $settings['centre'] = [
      'fit_bounds' => [
        'enable' => TRUE,
        'weight' => -101,
        'map_center_id' => 'fit_bounds',
      ],
    ];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $settings = $this->getSettings();
    $element = [];

    $element['hide_inputs'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide field inputs in favor of map.'),
      '#default_value' => $settings['hide_inputs'],
    ];

    $element['allow_override_map_settings'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow override the map settings when create/edit an content.'),
      '#default_value' => $settings['allow_override_map_settings'],
    ];

    $element['centre'] = $this->mapCenterManager->getCenterOptionsForm((array) $settings['centre'], ['widget' => $this]);

    $map_provider_options = $this->mapProviderManager->getMapProviderOptions();
    if (empty($map_provider_options)) {
      return [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => $this->t("No map provider found."),
      ];
    }

    $parents = [
      'fields',
      $this->fieldDefinition->getName(),
      'settings_edit_form',
      'settings',
    ];

    $user_input = $form_state->getUserInput();
    $map_provider_id = $this->mapProviderId ?? NestedArray::getValue($user_input, array_merge($parents, ['map_provider_id'])) ?? $settings['map_provider_id'];
    if (!$map_provider_id) {
      $map_provider_id = key($map_provider_options);
    }

    if (!$this->mapProviderId) {
      $element['map_provider_id'] = [
        '#type' => 'select',
        '#options' => $map_provider_options,
        '#title' => $this->t('Map Provider'),
        '#default_value' => $settings['map_provider_id'] ?? '',
        '#ajax' => [
          'callback' => [
            get_class($this->mapProviderManager),
            'addSettingsFormAjax',
          ],
          'wrapper' => 'map-provider-settings',
          'effect' => 'fade',
        ],
      ];
    }

    $element['map_provider_settings'] = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#value' => $this->t("No settings available."),
    ];

    $map_provider_settings = NestedArray::getValue($user_input, array_merge($parents, ['map_provider_settings'])) ?? $settings['map_provider_settings'] ?? [];
    $map_provider_settings = NestedArray::mergeDeep($this->mapProviderManager->getMapProviderDefaultSettings($map_provider_id) ?? [], $map_provider_settings);

    if (!empty($map_provider_id)) {
      $element['map_provider_settings'] = $this->mapProviderManager
        ->createInstance($map_provider_id, $map_provider_settings)
        ->getSettingsForm(
          $map_provider_settings,
          array_merge($parents, ['map_provider_settings'])
        );
    }

    $element['map_provider_settings'] = array_replace(
      $element['map_provider_settings'],
      [
        '#prefix' => '<div id="map-provider-settings">',
        '#suffix' => '</div>',
      ]
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = [];

    if (!$this->mapProvider) {
      $summary[] = $this->t("ATTENTION: No map provider set!");
      return $summary;
    }

    $settings = $this->getSettings();

    if (!empty($settings['allow_override_map_settings'])) {
      $summary[] = $this->t('Users will be allowed to override the map settings for each content.');
    }

    return array_replace_recursive($summary, $this->getMapProvider()?->getSettingsSummary($settings['map_provider_settings'] ?? []));
  }

  /**
   * {@inheritdoc}
   */
  public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = NULL): array {
    $element = parent::form($items, $form, $form_state, $get_delta);

    $settings = $this->getSettings();
    $id = Html::getUniqueId('edit_' . $this->fieldDefinition->getName() . '_wrapper');

    $element['#attributes'] = array_merge_recursive(
      $element['#attributes'] ?? [],
      [
        'data-widget-type' => $this->getPluginId(),
        'id' => $id,
        'class' => [
          'geolocation-map-widget',
        ],
      ]
    );
    $element['#attached'] = BubbleableMetadata::mergeAttachments(
      $element['#attached'] ?? [],
      [
        'library' => [
          'geolocation/geolocation.widget.map',
        ],
        'drupalSettings' => [
          'geolocation' => [
            'widgetSettings' => [
              $element['#attributes']['id'] => [
                'brokerImportPath' => base_path() . $this->moduleHandler->getModule('geolocation')->getPath() . '/js/GeolocationWidgetBroker.js',
                'widgetSubscribers' => [],
              ],
            ],
          ],
        ],
      ]
    );

    $element['map_description'] = [
      '#type' => 'item',
      '#weight' => -11,
      '#title' => $this->t('Map Widget - %field', ['%field' => $this->fieldDefinition->getLabel()]),
      '#description' => $this->t('Click on the map to set new coordinates and add a marker at that location. Click on an existing marker to clear those coordinates and remove the marker. Drag markers to alter the respective coordinates. Altering coordinate values directly will move the marker accordingly.'),
    ];

    $map_provider_id = $this->mapProviderId ?? $settings['map_provider_id'] ?? NULL;
    $element['map'] = [
      '#type' => 'geolocation_map',
      '#weight' => -10,
      '#settings' => $settings['map_provider_settings'],
      '#id' => $id . '-map',
      '#maptype' => $map_provider_id,
      '#context' => ['widget' => $this],
      'locations' => [],
    ];

    if ($settings['allow_override_map_settings']) {
      $overridden_map_settings = $items->get(0)?->getValue()['data']['map_provider_settings'] ?? $settings['map_provider_settings'] ?? [];

      $element['map']['#settings'] = $overridden_map_settings;

      $element['map_provider_settings'] = $this->getMapProvider()?->getSettingsForm(
        $overridden_map_settings,
        [
          $this->fieldDefinition->getName(),
          'map_provider_settings',
        ]
      );

      $element['map_provider_settings']['#weight'] = -9;
      $element['map_provider_settings']['#open'] = FALSE;
      $element['map_provider_settings']['#title'] .= ' - ' . $this->t('Override Map Default Preset');
    }

    $element['map']['#settings'] = array_merge($element['map']['#settings'], [
      'map_features' => [
        $this->getWidgetFeatureId() => [
          'enabled' => TRUE,
          'settings' => [
            'field_name' => $this->fieldDefinition->getName(),
            'field_type' => $this->fieldDefinition->getType(),
            'cardinality' => $this->fieldDefinition->getFieldStorageDefinition()->getCardinality(),
          ],
        ],
      ],
    ]);

    $element['map'] = $this->mapCenterManager->alterMap($element['map'], $settings['centre']);

    if ($settings['hide_inputs'] ?? FALSE) {
      if ($element['widget']['#cardinality_multiple']) {
        if (empty($element['widget']['#attributes'])) {
          $element['widget']['#attributes'] = [];
        }

        $element['widget']['#attributes'] = array_merge_recursive(
          $element['widget']['#attributes'],
          [
            'class' => [
              'visually-hidden',
            ],
          ]
        );
      }
      else {
        if (!empty($element['widget'][0])) {
          $element['widget'][0]['#attributes'] = array_merge_recursive(
            $element['widget'][0]['#attributes'],
            [
              'class' => [
                'visually-hidden',
              ],
            ]
          );
        }
      }
    }
    $context = [
      'widget' => $this,
      'form_state' => $form_state,
      'field_definition' => $this->fieldDefinition,
    ];

    if (!$this->isDefaultValueWidget($form_state)) {
      $this->moduleHandler->alter('geolocation_field_map_widget', $element, $context);
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state): void {
    $path = array_merge($form['#parents'], [$this->fieldDefinition->getName(), 'map_provider_settings']);
    $values = $form_state->getValues();
    $settings_exist = FALSE;
    $map_provider_settings = NestedArray::getValue($values, $path, $settings_exist);
    if ($settings_exist) {
      NestedArray::unsetValue($values, $path);
      NestedArray::setValue(
        $values,
        array_merge($form['#parents'], [$this->fieldDefinition->getName(), 0, 'data', 'map_provider_settings']),
        $map_provider_settings
      );

      $form_state->setValues($values);
    }

    parent::extractFormValues($items, $form, $form_state);
  }

  /**
   * Get map provider.
   *
   * @return \Drupal\geolocation\MapProviderInterface|null
   *   Map provider.
   */
  public function getMapProvider(?string $map_provider_id = NULL, ?array $map_provider_settings = []): ?MapProviderInterface {
    $settings = $this->getSettings();

    if (!$map_provider_id) {
      if ($this->mapProviderId) {
        $map_provider_id = $this->mapProviderId;
      }
      elseif ($settings['map_provider_id']) {
        $map_provider_id = $settings['map_provider_id'];
      }
    }

    if (!$map_provider_id) {
      return NULL;
    }

    if (!$map_provider_settings) {
      if ($settings['map_provider_settings']) {
        $map_provider_settings = $settings['map_provider_settings'];
      }
    }

    if ($this->mapProviderManager->hasDefinition($map_provider_id)) {
      return $this->mapProviderManager->getMapProvider($map_provider_id, $map_provider_settings ?? []);
    }

    return NULL;
  }

  /**
   * Get ID of feature to connect map and widget.
   *
   * @return string
   *   Feature ID.
   */
  protected function getWidgetFeatureId(): string {
    return 'geolocation_field_widget_map_connector';
  }

}
