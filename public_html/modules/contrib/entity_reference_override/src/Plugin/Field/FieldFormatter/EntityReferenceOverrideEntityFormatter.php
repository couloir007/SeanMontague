<?php

namespace Drupal\entity_reference_override\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'entity_reference_override_entity' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_override_entity",
 *   label = @Translation("Rendered entity"),
 *   description = @Translation("Display the referenced entities rendered by entity_view(), with optional field overrides."),
 *   field_types = {
 *     "entity_reference_override"
 *   }
 * )
 */
class EntityReferenceOverrideEntityFormatter extends EntityReferenceEntityFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'override_action' => 'title',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $target_entity_type_id = $this->getFieldSetting('target_type');
    $target_bundle_ids = $this->getFieldSetting('handler_settings')['target_bundles'];
    // @TODO make this extendable.
    $stringy_field_ids = [
      'string',
      'text_long',
      'email',
    ];
    $all_fields_config = \Drupal::entityTypeManager()->getStorage('field_config')->loadMultiple();

    // Field configurations where the field type is one of our stringy types and present
    // on the target entity reference entity type and bundle(s).
    $overridable_fields = array_filter($all_fields_config, function (FieldDefinitionInterface $field_def) use ($target_entity_type_id, $target_bundle_ids, $stringy_field_ids) {
      return (
        $field_def->get('entity_type') === $target_entity_type_id
        &&
        in_array($field_def->get('bundle'), $target_bundle_ids)
        &&
        in_array($field_def->getType(), $stringy_field_ids)
      );
    });

    $field_options = [];
    foreach ($overridable_fields as $overridable_field) {
      $field_options[$overridable_field->get('field_name')] = $overridable_field->label();
    }

    $elements = parent::settingsForm($form, $form_state);
    $elements['override_action'] = [
      '#type' => 'select',
      '#options' => [
        'title' => $this->t('Entity title'),
        'title-append' => $this->t('Append to the title'),
        'class' => $this->t('Link class'),
      ] + $field_options,
      '#title' => $this->t('Use custom text to override'),
      '#default_value' => $this->getSetting('override_action'),
      '#required' => TRUE,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $override_action = $this->getSetting('override_action');
    switch ($override_action) {
      case 'title':
        $override = $this->t('title');
        break;

      case 'title-append':
        $override = $this->t('title addition');
        break;

      case 'class':
        $override = $this->t('CSS class');
        break;

      case 'display':
        $override = $this->t('display mode');
        break;

      default:
        $override = $this->t('@override field', ['@override' => $this::friendlyField($override_action)]);
        break;
    }
    $summary[] = $this->t('Per-entity @override override', ['@override' => $override]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $view_mode = $this->getSetting('view_mode');
    $elements = [];

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      // Due to render caching and delayed calls, the viewElements() method
      // will be called later in the rendering process through a '#pre_render'
      // callback, so we need to generate a counter that takes into account
      // all the relevant information about this field and the referenced
      // entity that is being rendered.
      $recursive_render_id = $items->getFieldDefinition()->getTargetEntityTypeId() .
        $items->getFieldDefinition()->getTargetBundle() .
        $items->getName() .
        // We include the referencing entity, so we can render default images
        // without hitting recursive protections.
        $items->getEntity()->id() .
        $entity->getEntityTypeId() .
        $entity->id();

      if (isset(static::$recursiveRenderDepth[$recursive_render_id])) {
        static::$recursiveRenderDepth[$recursive_render_id]++;
      }
      else {
        static::$recursiveRenderDepth[$recursive_render_id] = 1;
      }

      // Protect ourselves from recursive rendering.
      if (static::$recursiveRenderDepth[$recursive_render_id] > static::RECURSIVE_RENDER_LIMIT) {
        $this->loggerFactory->get('entity')->error(
              'Recursive rendering detected when rendering entity %entity_type: %entity_id, using the %field_name field on the %bundle_name bundle. Aborting rendering.', [
                '%entity_type' => $entity->getEntityTypeId(),
                '%entity_id' => $entity->id(),
                '%field_name' => $items->getName(),
                '%bundle_name' => $items->getFieldDefinition()->getTargetBundle(),
              ]
          );
        return $elements;
      }

      $clone = clone $entity;

      if (isset($items[$delta]->override) && strlen($items[$delta]->override)) {
        $override = $this->getSetting('override_action');
        switch ($override) {
          case 'title':
            $title_key = $clone->getEntityType()->getKey('label');
            $title_key = $title_key ?: 'title';
            $clone->$title_key = $items[$delta]->override;
            break;

          case 'title-append':
            $title_key = $clone->getEntityType()->getKey('label');
            $title_key = $title_key ?: 'title';
            $clone->$title_key = $clone->$title_key->value . ' ('. $items[$delta]->override . ')';
            break;

          case 'class':
            $override_class = $items[$delta]->override;
            break;

          case 'display':
            $view_mode = $items[$delta]->override;
            break;

          default:
            $clone->set(
                  $override, [
                    'value' => $items[$delta]->override,
                    'format' => $items[$delta]->override_format,
                  ]
              );
            break;
        }
      }

      $view_builder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
      $elements[$delta] = $view_builder->view($clone, $view_mode, $entity->language()->getId());

      if (!empty($items[$delta]->override)) {
        $elements[$delta]['#cache']['keys'][] = md5($items[$delta]->override);
      }

      if (!empty($override_class)) {
        $elements[$delta]['class'][] = $override_class;
      }

      // Add a resource attribute to set the mapping property's value to the
      // entity's url. Since we don't know what the markup of the entity will
      // be, we shouldn't rely on it for structured data such as RDFa.
      if (!empty($items[$delta]->_attributes) && !$entity->isNew() && $entity->hasLinkTemplate('canonical')) {
        $items[$delta]->_attributes += ['resource' => $entity->toUrl()->toString()];
      }
    }

    return $elements;
  }

  /**
   * Provide a human-readable version of a field machine name.
   */
  public static function friendlyField($string) {
    return str_replace(['field_', '_'], ['', ' '], $string);
  }

}
