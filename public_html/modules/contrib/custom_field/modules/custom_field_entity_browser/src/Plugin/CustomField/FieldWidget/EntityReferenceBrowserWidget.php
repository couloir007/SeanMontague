<?php

declare(strict_types=1);

namespace Drupal\custom_field_entity_browser\Plugin\CustomField\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\custom_field\Attribute\CustomFieldWidget;
use Drupal\custom_field\Plugin\CustomField\FieldWidget\EntityReferenceWidgetBase;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\entity_browser\Element\EntityBrowserElement;
use Drupal\entity_browser\FieldWidgetDisplayInterface;
use Drupal\entity_browser\FieldWidgetDisplayManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Entity browser widget for custom_field entity_reference subfields.
 *
 * Adapted from entity_browser's EntityReferenceBrowserWidget with cardinality
 * fixed to 1. Selection is stored in a hidden target_id as "entity_type:id"
 * and updated by entity_browser JS via the entity_browser_value_updated event.
 *
 * Entity resolution priority (see resolveSelectedEntity()):
 * 1. Raw user input for target_id
 * 2. Triggering element (browser AJAX update or remove/replace)
 * 3. Form-state stash (survives #limit_validation_errors)
 * 4. Stored field item entity
 */
#[CustomFieldWidget(
  id: 'entity_reference_entity_browser',
  label: new TranslatableMarkup('Entity browser'),
  description: new TranslatableMarkup('Allows you to select items using Entity Browser.'),
  category: new TranslatableMarkup('Reference'),
  field_types: [
    'entity_reference',
  ],
)]
class EntityReferenceBrowserWidget extends EntityReferenceWidgetBase {

  use LoggerChannelTrait;

  /**
   * Supported cardinality for this custom field widget.
   */
  protected const CARDINALITY = 1;

  /**
   * Fallback depth from remove/replace button to the widget root.
   *
   * Prefer #widget_array_parents on buttons when present. This constant only
   * backs older form builds that lack that property.
   */
  protected const DELETE_DEPTH = 4;

  /**
   * Field widget display plugin manager.
   *
   * @var \Drupal\entity_browser\FieldWidgetDisplayManager
   */
  protected FieldWidgetDisplayManager $fieldDisplayManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->fieldDisplayManager = $container->get('plugin.manager.entity_browser.field_widget_display');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'entity_browser' => NULL,
      'open' => FALSE,
      'field_widget_display' => 'label',
      'field_widget_edit' => TRUE,
      'field_widget_remove' => TRUE,
      'field_widget_replace' => FALSE,
      'field_widget_display_settings' => [],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSettingsForm(FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widgetSettingsForm($form_state, $field);
    $settings = $this->getSettings() + self::defaultSettings();
    $target_type = $field->getTargetType();
    $entity_type = $this->entityTypeManager->getStorage($target_type)->getEntityType();

    $browsers = [];
    try {
      foreach ($this->entityTypeManager->getStorage('entity_browser')->loadMultiple() as $browser) {
        $browsers[$browser->id()] = $browser->label();
      }
    }
    catch (\Exception $exception) {
      $this->getLogger('custom_field_entity_browser')->error(
        'Unable to load entity browsers for widget settings: @message',
        ['@message' => $exception->getMessage()]
      );
    }

    $element['entity_browser'] = [
      '#title' => $this->t('Entity browser'),
      '#type' => 'select',
      '#default_value' => $settings['entity_browser'],
      '#options' => $browsers,
      '#empty_option' => $this->t('- Select -'),
      '#required' => TRUE,
    ];

    $displays = $this->getApplicableDisplayOptions($entity_type);

    $id = Html::getId($field->getName()) . '-field-widget-display-settings-ajax-wrapper-' . md5($this->getUniqueIdentifier($field));
    $element['field_widget_display'] = [
      '#title' => $this->t('Entity display plugin'),
      '#type' => 'radios',
      '#default_value' => $settings['field_widget_display'],
      '#options' => $displays,
      '#ajax' => [
        'callback' => [static::class, 'updateFieldWidgetDisplaySettings'],
        'wrapper' => $id,
      ],
      '#limit_validation_errors' => [],
    ];

    if ($settings['field_widget_display']) {
      $element['field_widget_display_settings'] = [
        '#type' => 'details',
        '#title' => $this->t('Entity display plugin configuration'),
        '#open' => TRUE,
        '#prefix' => '<div id="' . $id . '">',
        '#suffix' => '</div>',
        '#tree' => TRUE,
      ];

      $display_plugin = $this->createFieldWidgetDisplay(
        $form_state->getValue(
          $this->getSettingsFormValueKeys($field, 'field_widget_display'),
          $settings['field_widget_display']
        ),
        $form_state->getValue(
          $this->getSettingsFormValueKeys($field, 'field_widget_display_settings'),
          $settings['field_widget_display_settings']
        ) + ['entity_type' => $target_type]
      );
      if ($display_plugin) {
        $element['field_widget_display_settings'] += $display_plugin->settingsForm($element, $form_state);
      }
    }

    $element['field_widget_edit'] = [
      '#title' => $this->t('Display Edit button'),
      '#type' => 'checkbox',
      '#default_value' => $settings['field_widget_edit'],
    ];

    $element['field_widget_remove'] = [
      '#title' => $this->t('Display Remove button'),
      '#type' => 'checkbox',
      '#default_value' => $settings['field_widget_remove'],
    ];

    $element['field_widget_replace'] = [
      '#title' => $this->t('Display Replace button'),
      '#description' => $this->t('This button will only be displayed if there is a single entity in the current selection.'),
      '#type' => 'checkbox',
      '#default_value' => $settings['field_widget_replace'],
    ];

    $element['open'] = [
      '#title' => $this->t('Show widget details as open by default'),
      '#description' => $this->t('If marked, the fieldset container that wraps the browser on the entity form will be loaded initially expanded.'),
      '#type' => 'checkbox',
      '#default_value' => $settings['open'],
    ];

    return $element;
  }

  /**
   * Ajax callback that updates the field widget display settings fieldset.
   *
   * @param array<string, mixed> $form
   *   The form definition for the widget settings.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the (entire) configuration form.
   */
  public static function updateFieldWidgetDisplaySettings(array $form, FormStateInterface $form_state): mixed {
    $array_parents = $form_state->getTriggeringElement()['#array_parents'];
    $up_two_levels = array_slice($array_parents, 0, count($array_parents) - 2);
    $settings_path = array_merge($up_two_levels, ['field_widget_display_settings']);

    return NestedArray::getValue($form, $settings_path);
  }

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, int $delta, array $element, array &$form, FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widget($items, $delta, $element, $form, $form_state, $field);
    $field_settings = $field->getFieldSettings();
    $settings = $this->getSettings() + self::defaultSettings();
    $field_name = $items->getFieldDefinition()->getName();
    $parents = is_array($form['#parents']) ? $form['#parents'] : [];
    $entity = $this->resolveSelectedEntity($parents, $items, $delta, $form_state, $field);

    // New field-item rows from "add more" must start empty even if form state
    // still holds a prior selection for this delta.
    if ($this->isNewItemAfterAddMore($parents, $field_name, $delta, $form_state)) {
      $entity = NULL;
    }

    // Stash selection so rebuilds with #limit_validation_errors still work.
    // Include form #parents so nested hosts (e.g. multi-value Paragraphs) do
    // not share one key when parent entity ids are empty/identical.
    $parent_entity = $items->getEntity();
    $form_state_key = static::getFormStateKey(
      $parent_entity->getEntityTypeId() . ':' . $parent_entity->id(),
      $field_name,
      $delta,
      $parents
    );
    $form_state->set($form_state_key, $entity?->id());

    $id_string = $this->getUniqueElementId($form, $field_name, $delta, $field->getName());
    $hidden_id = Html::getUniqueId($id_string . '-target-id');

    $element += [
      '#id' => $id_string,
      '#type' => 'details',
      '#open' => (!is_null($entity) || $settings['open']),
      '#required' => $field_settings['required'],
      // Maintain selection in our own hidden input for the full request cycle.
      'target_id' => [
        '#type' => 'hidden',
        '#id' => $hidden_id,
        // Repeat ID: Form API may otherwise omit it when rendering.
        '#attributes' => [
          'id' => $hidden_id,
        ],
        '#default_value' => is_null($entity) ? '' : $entity->getEntityTypeId() . ':' . $entity->id(),
        // Hidden elements need an explicit event for #ajax to fire.
        '#ajax' => [
          'callback' => [static::class, 'updateWidgetCallback'],
          'wrapper' => $id_string,
          'event' => 'entity_browser_value_updated',
        ],
      ],
    ];

    $cardinality = static::CARDINALITY;
    $selection_mode = EntityBrowserElement::SELECTION_MODE_APPEND;

    if (EntityBrowserElement::isEntityBrowserAvailable($selection_mode, $cardinality, (int) !is_null($entity))) {
      $persistentData = $this->getPersistentData($field);

      $element['entity_browser'] = [
        '#type' => 'entity_browser',
        '#entity_browser' => $settings['entity_browser'],
        '#cardinality' => $cardinality,
        '#selection_mode' => $selection_mode,
        '#default_value' => $entity,
        '#entity_browser_validators' => $persistentData['validators'],
        '#widget_context' => $persistentData['widget_context'],
        '#custom_hidden_id' => $hidden_id,
        '#process' => [
          ['\Drupal\entity_browser\Element\EntityBrowserElement', 'processEntityBrowser'],
          [static::class, 'processEntityBrowser'],
        ],
      ];
      $element['target_id']['#attributes']['data-entity-browser-available'] = 1;
    }
    else {
      $element['target_id']['#attributes']['data-entity-browser-visible'] = 0;
    }

    $element['#attached']['library'][] = 'entity_browser/entity_reference';

    if (!is_null($entity)) {
      $element['current'] = $this->displayCurrentSelection(
        $id_string,
        [(string) $items->getName()],
        $entity,
        $delta,
        $field
      );
    }

    return $element;
  }

  /**
   * Render API callback: Processes the entity browser element.
   *
   * Points entity_browser JS at our custom hidden target_id input.
   *
   * @param array<string, mixed> $element
   *   The element.
   *
   * @return array<string, mixed>
   *   The updated element.
   */
  public static function processEntityBrowser(array &$element): array {
    if (NestedArray::keyExists($element, ['#attached', 'drupalSettings', 'entity_browser'])) {
      $uuid = key($element['#attached']['drupalSettings']['entity_browser']);
      $element['#attached']['drupalSettings']['entity_browser'][$uuid]['selector'] = '#' . $element['#custom_hidden_id'];
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValue(mixed $value, array $column): mixed {
    if (!is_array($value) || empty($value['target_id'])) {
      return NULL;
    }

    $raw = $value['target_id'];
    if (!is_string($raw)) {
      return NULL;
    }

    // Expected shape from entity browser: "entity_type:id".
    $parts = explode(':', $raw, 2);
    if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
      return NULL;
    }

    return [
      'target_id' => $parts[1],
    ];
  }

  /**
   * AJAX form callback for browser selection updates and remove/replace.
   *
   * @param array<string, mixed> $form
   *   The form structure where widgets are being attached to.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array<string, mixed>
   *   The form part to update.
   */
  public static function updateWidgetCallback(array $form, FormStateInterface $form_state): array {
    $trigger = $form_state->getTriggeringElement();
    $reopen_browser = FALSE;
    $parents = static::getWidgetArrayParentsFromTrigger($trigger, $reopen_browser);

    $widget = NestedArray::getValue($form, $parents) ?? [];
    $widget['#attached']['drupalSettings']['entity_browser_reopen_browser'] = $reopen_browser;
    return $widget;
  }

  /**
   * Submit callback for replace and remove buttons.
   *
   * @param array<string, mixed> $form
   *   The form structure where widgets are being attached to.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function removeItemSubmit(array $form, FormStateInterface $form_state): void {
    $triggering_element = $form_state->getTriggeringElement();
    if (empty($triggering_element['#attributes']['data-entity-id']) || !isset($triggering_element['#attributes']['data-row-id'])) {
      return;
    }

    $array_parents = $triggering_element['#widget_array_parents']
      ?? array_slice($triggering_element['#array_parents'], 0, -static::DELETE_DEPTH);

    $target_id_element = &NestedArray::getValue($form, array_merge($array_parents, ['target_id']));
    if (!is_array($target_id_element)) {
      return;
    }

    $form_state->setValueForElement($target_id_element, '');
    $user_input = &$form_state->getUserInput();
    NestedArray::setValue($user_input, $target_id_element['#parents'], '');
    $form_state->setRebuild();
  }

  /**
   * Builds the render array for the current single selection.
   *
   * @param string $id
   *   The ID for the details element and button key prefixes.
   * @param string[] $field_parents
   *   Field parents.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The referenced entity.
   * @param int $delta
   *   Field item delta.
   * @param \Drupal\custom_field\Plugin\CustomFieldTypeInterface $field
   *   The custom field type object.
   *
   * @return array<string, mixed>
   *   The render array for the current selection.
   */
  protected function displayCurrentSelection(string $id, array $field_parents, EntityInterface $entity, int $delta, CustomFieldTypeInterface $field): array {
    $settings = $this->getSettings() + self::defaultSettings();
    $name_key = str_replace('-', '_', $id);
    $target_entity_type = $field->getTargetType();

    $field_widget_display = $this->createFieldWidgetDisplay(
      $settings['field_widget_display'],
      ($settings['field_widget_display_settings'] ?? []) + ['entity_type' => $target_entity_type]
    );
    if (!$field_widget_display) {
      return [];
    }

    $edit_button_access = $settings['field_widget_edit'] && $entity->access('update', $this->currentUser);
    if ($entity->getEntityTypeId() === 'file') {
      // File entities need file_entity to expose a standalone edit form.
      $edit_button_access = $edit_button_access && $this->moduleHandler->moduleExists('file_entity');
    }

    $display = $field_widget_display->view($entity);
    if (is_string($display)) {
      $display = ['#markup' => $display];
    }

    // Widget root is the details element; buttons live under current/items/0.
    // #widget_array_parents is set in a process callback after parents exist.
    $button_base = [
      '#type' => 'submit',
      '#ajax' => [
        'callback' => [static::class, 'updateWidgetCallback'],
        'wrapper' => $id,
      ],
      '#submit' => [[static::class, 'removeItemSubmit']],
      '#limit_validation_errors' => [array_merge($field_parents, [$field->getName()])],
      '#attributes' => [
        'data-entity-id' => $entity->getEntityTypeId() . ':' . $entity->id(),
        'data-row-id' => $delta,
      ],
      // Prefer #after_build so Form API can still attach #ajax process.
      '#after_build' => [[static::class, 'afterBuildSelectionButton']],
    ];

    return [
      '#theme_wrappers' => ['container'],
      '#attributes' => [
        'class' => [
          'entities-list',
          Html::cleanCssIdentifier("entity-type--$target_entity_type"),
        ],
        'data-entity-browser-entities-list' => 1,
      ],
      // Single-entity structure kept compatible with entity_browser CSS/JS.
      'items' => [
        [
          '#theme_wrappers' => ['container'],
          '#attributes' => [
            'class' => [
              'item-container',
              Html::getClass($field_widget_display->getPluginId()),
            ],
            'data-entity-id' => $entity->getEntityTypeId() . ':' . $entity->id(),
            'data-row-id' => $delta,
          ],
          'display' => $display,
          'remove_button' => [
            '#value' => $this->t('Remove'),
            '#name' => $name_key . '_entity_browser_remove',
            '#attributes' => [
              'data-entity-id' => $entity->getEntityTypeId() . ':' . $entity->id(),
              'data-row-id' => $delta,
              'class' => ['remove-button'],
            ],
            '#access' => (bool) $settings['field_widget_remove'],
          ] + $button_base,
          'replace_button' => [
            '#value' => $this->t('Replace'),
            '#name' => $name_key . '_entity_browser_replace',
            '#attributes' => [
              'data-entity-id' => $entity->getEntityTypeId() . ':' . $entity->id(),
              'data-row-id' => $delta,
              'class' => ['replace-button'],
            ],
            '#access' => (bool) $settings['field_widget_replace'],
          ] + $button_base,
          'edit_button' => [
            '#type' => 'submit',
            '#value' => $this->t('Edit'),
            '#name' => $name_key . '_entity_browser_edit',
            '#ajax' => [
              'url' => Url::fromRoute(
                'entity_browser.edit_form',
                [
                  'entity_type' => $entity->getEntityTypeId(),
                  'entity' => $entity->id(),
                ]
              ),
              'options' => [
                'query' => [
                  'details_id' => $id,
                ],
              ],
            ],
            '#attributes' => [
              'class' => ['edit-button'],
            ],
            '#access' => $edit_button_access,
          ],
        ],
      ],
    ];
  }

  /**
   * After-build callback: stores widget root array parents on action buttons.
   *
   * @param array<string, mixed> $element
   *   The button element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array<string, mixed>
   *   The processed element.
   */
  public static function afterBuildSelectionButton(array $element, FormStateInterface $form_state): array {
    // Button path: ...[ref][current][items][0][remove_button] → widget is 4 up.
    if (!empty($element['#array_parents'])) {
      $element['#widget_array_parents'] = array_slice(
        $element['#array_parents'],
        0,
        -static::DELETE_DEPTH
      );
    }
    return $element;
  }

  /**
   * Resolves the selected entity for this widget instance.
   *
   * Priority: user input → relevant trigger → form-state stash → stored item.
   *
   * @param string[] $parents
   *   The form #parents for the field.
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   Field values.
   * @param int $delta
   *   Field item delta.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Drupal\custom_field\Plugin\CustomFieldTypeInterface $field
   *   The custom field type object.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The selected entity, if any.
   */
  protected function resolveSelectedEntity(array $parents, FieldItemListInterface $items, int $delta, FormStateInterface $form_state, CustomFieldTypeInterface $field): ?EntityInterface {
    // 1) Explicit target_id in raw user input (covers IEF edge cases).
    $from_input = $this->getEntityByTargetId($parents, $items, $delta, $form_state, $field);
    if ($from_input !== NULL) {
      return $from_input;
    }

    // 2) This widget triggered the request (browser update or remove/replace).
    $from_trigger = $this->getEntityFromTrigger($items, $delta, $form_state, $field);
    if ($from_trigger['handled']) {
      return $from_trigger['entity'];
    }

    // 3) Stashed id from a prior build of this form.
    $from_state = $this->getEntityFromFormState($parents, $items, $delta, $form_state, $field);
    if ($from_state !== NULL) {
      return $from_state;
    }

    // 4) Value already stored on the entity.
    return $items[$delta]->{$field->getName() . '__entity'} ?? NULL;
  }

  /**
   * Loads an entity from the hidden target_id in raw user input.
   *
   * @param string[] $parents
   *   The form #parents for the field.
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   Field values.
   * @param int $delta
   *   Field item delta.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Drupal\custom_field\Plugin\CustomFieldTypeInterface $field
   *   The custom field type object.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity if present in user input.
   */
  protected function getEntityByTargetId(array $parents, FieldItemListInterface $items, int $delta, FormStateInterface $form_state, CustomFieldTypeInterface $field): ?EntityInterface {
    $path = [...$parents, $items->getName(), $delta, $field->getName(), 'target_id'];
    $user_input = $form_state->getUserInput();
    if (!NestedArray::keyExists($user_input, $path)) {
      return NULL;
    }

    $value = NestedArray::getValue($user_input, $path);
    return is_string($value) ? $this->processEntityId($value) : NULL;
  }

  /**
   * Resolves entity from the triggering element when it belongs to this widget.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   Field values.
   * @param int $delta
   *   Field item delta.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Drupal\custom_field\Plugin\CustomFieldTypeInterface $field
   *   The custom field type object.
   *
   * @return array{handled: bool, entity: \Drupal\Core\Entity\EntityInterface|null}
   *   Whether this widget handled the trigger, and the entity if any.
   */
  protected function getEntityFromTrigger(FieldItemListInterface $items, int $delta, FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $trigger = $form_state->getTriggeringElement();
    if (!$trigger || empty($trigger['#parents'])) {
      return ['handled' => FALSE, 'entity' => NULL];
    }

    $last_parent = end($trigger['#parents']);
    if (!in_array($last_parent, ['target_id', 'remove_button', 'replace_button'], TRUE)) {
      return ['handled' => FALSE, 'entity' => NULL];
    }

    // Confirm the trigger belongs to this subfield + delta instance.
    if (!$this->triggerBelongsToWidget($trigger, $field->getName(), $delta)) {
      return ['handled' => FALSE, 'entity' => NULL];
    }

    $value_parents = [];
    if (!empty($trigger['#ajax']['event']) && $trigger['#ajax']['event'] === 'entity_browser_value_updated') {
      $value_parents = $trigger['#parents'];
    }
    elseif (($trigger['#type'] ?? '') === 'submit' && is_string($trigger['#name'] ?? NULL) && str_ends_with($trigger['#name'], '_entity_browser_remove')) {
      $widget_parents = $trigger['#widget_array_parents']
        ?? array_slice($trigger['#parents'], 0, -static::DELETE_DEPTH);
      $value_parents = array_merge($widget_parents, ['target_id']);
    }
    // Replace uses a distinct button name suffix.
    elseif (($trigger['#type'] ?? '') === 'submit' && is_string($trigger['#name'] ?? NULL) && str_ends_with($trigger['#name'], '_entity_browser_replace')) {
      $widget_parents = $trigger['#widget_array_parents']
        ?? array_slice($trigger['#parents'], 0, -static::DELETE_DEPTH);
      $value_parents = array_merge($widget_parents, ['target_id']);
    }

    if ($value_parents === []) {
      return ['handled' => TRUE, 'entity' => NULL];
    }

    $value = $form_state->getValue($value_parents);
    if (is_string($value)) {
      return ['handled' => TRUE, 'entity' => $this->processEntityId($value)];
    }

    return ['handled' => TRUE, 'entity' => NULL];
  }

  /**
   * Loads a previously stashed selection from form state.
   *
   * @param array $parents
   *   The form #parents for this widget instance (distinguishes nested hosts).
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   Field values.
   * @param int $delta
   *   Field item delta.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Drupal\custom_field\Plugin\CustomFieldTypeInterface $field
   *   The custom field type object.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The stashed entity, if any.
   */
  protected function getEntityFromFormState(array $parents, FieldItemListInterface $items, int $delta, FormStateInterface $form_state, CustomFieldTypeInterface $field): ?EntityInterface {
    $parent_entity = $items->getEntity();
    $form_state_key = static::getFormStateKey(
      $parent_entity->getEntityTypeId() . ':' . $parent_entity->id(),
      $items->getFieldDefinition()->getName(),
      $delta,
      $parents
    );
    if (!$form_state->has($form_state_key)) {
      return NULL;
    }

    $stored_id = $form_state->get($form_state_key);
    if ($stored_id === NULL || $stored_id === '') {
      return NULL;
    }

    try {
      return $this->entityTypeManager
        ->getStorage($field->getTargetType())
        ->load($stored_id);
    }
    catch (\Exception $exception) {
      $this->getLogger('custom_field_entity_browser')->error(
        'Unable to load stashed entity @type:@id: @message',
        [
          '@type' => $field->getTargetType(),
          '@id' => (string) $stored_id,
          '@message' => $exception->getMessage(),
        ]
      );
      return NULL;
    }
  }

  /**
   * Whether the current request is adding a new empty delta via add_more.
   *
   * @param string[] $parents
   *   Form parents.
   * @param string $field_name
   *   Field machine name.
   * @param int $delta
   *   Field item delta.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return bool
   *   TRUE when this delta should start empty.
   */
  protected function isNewItemAfterAddMore(array $parents, string $field_name, int $delta, FormStateInterface $form_state): bool {
    $triggering_element = $form_state->getTriggeringElement();
    if (!isset($triggering_element['#name'])) {
      return FALSE;
    }

    $match = implode('_', [...$parents, $field_name, 'add_more']);
    if ($triggering_element['#name'] !== $match) {
      return FALSE;
    }

    $user_input = $form_state->getUserInput();
    return !NestedArray::keyExists($user_input, [...$parents, $field_name, $delta]);
  }

  /**
   * Checks that a trigger belongs to this subfield instance and delta.
   *
   * @param array<string, mixed> $trigger
   *   The triggering element.
   * @param string $subfield_name
   *   The custom subfield machine name.
   * @param int $delta
   *   Field item delta.
   *
   * @return bool
   *   TRUE if the trigger is for this widget instance.
   */
  protected function triggerBelongsToWidget(array $trigger, string $subfield_name, int $delta): bool {
    $parents = $trigger['#parents'] ?? [];
    // Prefer explicit widget parents when available.
    if (!empty($trigger['#widget_array_parents'])) {
      $widget_parents = $trigger['#widget_array_parents'];
      $count = count($widget_parents);
      return $count >= 2
        && ($widget_parents[$count - 1] ?? NULL) === $subfield_name
        && ($widget_parents[$count - 2] ?? NULL) === $delta;
    }

    $field_name_key = count($parents) - (static::DELETE_DEPTH + 1);
    return array_key_exists($field_name_key, $parents)
      && ($parents[$field_name_key] === $subfield_name)
      && (($parents[$field_name_key - 1] ?? NULL) === $delta);
  }

  /**
   * Resolves widget array parents from an AJAX/submit trigger.
   *
   * @param array<string, mixed> $trigger
   *   The triggering element.
   * @param bool|string $reopen_browser
   *   FALSE, or a button-name fragment when replace should reopen the browser.
   *
   * @param-out bool|string $reopen_browser
   *
   * @return array<int, string|int>
   *   Array parents of the widget root element.
   */
  protected static function getWidgetArrayParentsFromTrigger(array $trigger, bool|string &$reopen_browser): array {
    if (
      NestedArray::keyExists($trigger, ['#ajax', 'event'])
      && $trigger['#ajax']['event'] === 'entity_browser_value_updated'
    ) {
      return array_slice($trigger['#array_parents'], 0, -1);
    }

    $is_submit = ($trigger['#type'] ?? '') === 'submit';
    $name = $trigger['#name'] ?? '';

    if ($is_submit && is_string($name) && str_ends_with($name, '_entity_browser_remove')) {
      return $trigger['#widget_array_parents']
        ?? array_slice($trigger['#array_parents'], 0, -static::DELETE_DEPTH);
    }

    if ($is_submit && is_string($name) && str_ends_with($name, '_entity_browser_replace')) {
      $parents = $trigger['#widget_array_parents']
        ?? array_slice($trigger['#array_parents'], 0, -static::DELETE_DEPTH);
      // JS reopens the browser using a unique part of the button name path.
      $reopen_browser = implode('-', array_slice($trigger['#parents'], 0, -static::DELETE_DEPTH));
      return $parents;
    }

    return [];
  }

  /**
   * Gets data that should persist across Entity Browser renders.
   *
   * @param \Drupal\custom_field\Plugin\CustomFieldTypeInterface $field
   *   The custom field type object.
   *
   * @return array<string, array<string, mixed>>
   *   Validators and widget context for the entity browser element.
   */
  protected function getPersistentData(CustomFieldTypeInterface $field): array {
    $handler = $field->getFieldSettings()['handler_settings'] ?? [];
    return [
      'validators' => [
        'entity_type' => ['type' => $field->getTargetType()],
      ],
      'widget_context' => [
        'target_bundles' => !empty($handler['target_bundles']) ? $handler['target_bundles'] : [],
        'target_entity_type' => $field->getTargetType(),
        'cardinality' => static::CARDINALITY,
      ],
    ];
  }

  /**
   * Returns a unique identifier for the subfield.
   *
   * @param \Drupal\custom_field\Plugin\CustomFieldTypeInterface $field
   *   The custom field type object.
   *
   * @return string
   *   The identifier.
   */
  protected function getUniqueIdentifier(CustomFieldTypeInterface $field): string {
    return $field->getDataType() . '-' . $field->getTargetType() . '-' . $field->getName();
  }

  /**
   * Processes "entity_type:id" input and loads the entity.
   *
   * @param string $user_input
   *   The string containing the entity type and ID.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity if available.
   */
  protected function processEntityId(string $user_input): ?EntityInterface {
    if ($user_input === '') {
      return NULL;
    }
    $entities = EntityBrowserElement::processEntityIds($user_input);
    return $entities !== [] ? reset($entities) : NULL;
  }

  /**
   * Returns a form-state key used to stash the selected entity id.
   *
   * Parent entity id alone is not unique for unsaved Paragraphs (or other
   * nested IEF-style hosts): every new item can be type:NULL. Including the
   * widget form #parents path keeps each embedded instance isolated.
   *
   * @param string $id
   *   Parent entity type and id as "type:id".
   * @param string $field_name
   *   Custom field machine name on the parent entity.
   * @param int $delta
   *   Field item delta.
   * @param array $parents
   *   The form #parents for this widget instance.
   *
   * @return array<int, string>
   *   A key for form state storage.
   */
  protected static function getFormStateKey(string $id, string $field_name, int $delta, array $parents = []): array {
    $parents_key = $parents !== []
      ? implode('.', array_map(static fn ($part) => (string) $part, $parents))
      : '';
    return [
      'entity_browser_widget',
      $id . ':' . $field_name . ':' . $delta . ':' . $parents_key,
    ];
  }

  /**
   * Builds Field UI settings form value keys for this subfield setting.
   *
   * @param \Drupal\custom_field\Plugin\CustomFieldTypeInterface $field
   *   The custom field type object.
   * @param string $setting_name
   *   The setting key under the subfield.
   *
   * @return list<string>
   *   Value path for form state.
   */
  protected function getSettingsFormValueKeys(CustomFieldTypeInterface $field, string $setting_name): array {
    return [
      'fields',
      $this->fieldName,
      'settings_edit_form',
      'settings',
      'fields',
      $field->getName(),
      $setting_name,
    ];
  }

  /**
   * Returns applicable field widget display plugin labels.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The target entity type definition.
   *
   * @return array<string, string|\Drupal\Core\StringTranslation\TranslatableMarkup>
   *   Plugin id => label.
   */
  protected function getApplicableDisplayOptions($entity_type): array {
    $displays = [];
    foreach ($this->fieldDisplayManager->getDefinitions() as $id => $definition) {
      try {
        $plugin = $this->fieldDisplayManager->createInstance($id);
        assert($plugin instanceof FieldWidgetDisplayInterface);
        if ($plugin->isApplicable($entity_type)) {
          $displays[$id] = $definition['label'];
        }
      }
      catch (\Exception $exception) {
        $this->getLogger('custom_field_entity_browser')->error(
          'Unable to instantiate field widget display @id: @message',
          ['@id' => $id, '@message' => $exception->getMessage()]
        );
      }
    }
    return $displays;
  }

  /**
   * Instantiates a field widget display plugin.
   *
   * @param string|null $plugin_id
   *   The display plugin id.
   * @param array<string, mixed> $configuration
   *   Plugin configuration.
   *
   * @return \Drupal\entity_browser\FieldWidgetDisplayInterface|null
   *   The plugin instance, or NULL on failure.
   */
  protected function createFieldWidgetDisplay(?string $plugin_id, array $configuration = []): ?FieldWidgetDisplayInterface {
    if (!$plugin_id) {
      return NULL;
    }
    try {
      $plugin = $this->fieldDisplayManager->createInstance($plugin_id, $configuration);
      assert($plugin instanceof FieldWidgetDisplayInterface);
      return $plugin;
    }
    catch (\Exception $exception) {
      $this->getLogger('custom_field_entity_browser')->error(
        'Unable to create field widget display @id: @message',
        ['@id' => $plugin_id, '@message' => $exception->getMessage()]
      );
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function calculateWidgetDependencies(): array {
    $dependencies = parent::calculateWidgetDependencies();
    $browser = $this->getSetting('entity_browser');
    if ($browser) {
      /** @var \Drupal\entity_browser\Entity\EntityBrowser|null $entity_browser */
      $entity_browser = $this->entityTypeManager->getStorage('entity_browser')->load($browser);
      if ($entity_browser) {
        $dependencies[$entity_browser->getConfigDependencyKey()][] = $entity_browser->getConfigDependencyName();
      }
    }

    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function onWidgetDependencyRemoval(array $dependencies): array {
    $settings = $this->getSettings();
    $browser = $this->getSetting('entity_browser');
    if (!$browser) {
      return [];
    }

    /** @var \Drupal\entity_browser\Entity\EntityBrowser|null $entity_browser */
    $entity_browser = $this->entityTypeManager->getStorage('entity_browser')->load($browser);
    if (
      $entity_browser
      && !empty($dependencies[$entity_browser->getConfigDependencyKey()][$entity_browser->getConfigDependencyName()])
    ) {
      $settings['entity_browser'] = NULL;
      return $settings;
    }

    return [];
  }

}
