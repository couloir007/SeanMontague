<?php

declare(strict_types=1);

namespace Drupal\custom_field\Plugin\CustomField\FieldWidget;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\custom_field\Attribute\CustomFieldWidget;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\custom_field\Plugin\CustomFieldWidgetBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'uuid' widget.
 *
 * Simple uuid custom field widget. This doesn't render an editable control on
 * the form. Rather it sets a UUID on the field when the custom field item is
 * first created to give it a unique identifier.
 *
 * The value is carried in a hidden input so that it round-trips through form
 * submission and stays stable across AJAX rebuilds, including for items that
 * have not been saved yet. See widget() for why a '#type' => 'value' element
 * cannot do this.
 *
 * The main purpose of this field is to be able to identify a specific
 * custom field item without having to rely on any of the exposed fields which
 * could change at any given time (i.e. content is updated, or delta is changed
 * with a manual reorder).
 */
#[CustomFieldWidget(
  id: 'uuid',
  label: new TranslatableMarkup('UUID'),
  category: new TranslatableMarkup('General'),
  field_types: [
    'uuid',
  ],
)]
class UuidWidget extends CustomFieldWidgetBase {

  /**
   * The Uuid service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected UuidInterface $uuidService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->uuidService = $container->get('uuid');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    // We're not calling the parent widget method here since we don't want to
    // render an editable control for this widget.
    $is_config_form = $form_state->getBuildInfo()['base_form_id'] == 'field_config_form';
    if ($is_config_form) {
      return [
        '#type' => 'value',
        '#value' => NULL,
      ];
    }

    // Reuse the stored value whenever there is one, so this never overwrites an
    // existing UUID, and only generate when the value is genuinely empty.
    //
    // The element is a hidden input rather than a '#type' => 'value' so that
    // the value comes back with the submission. Core excludes 'value' elements
    // from input processing (see FormBuilder::handleInputElement()), so a
    // 'value' element sends nothing to the browser and is recomputed from
    // $items on every build. For a delta that is not in storage yet $items is
    // empty, so every form rebuild — an AJAX "Add another item", a failed
    // validation — would mint a different UUID and any reference taken to the
    // previous one would already be stale.
    $existing = $items[$delta]->{$field->getName()} ?? NULL;

    return [
      '#type' => 'hidden',
      '#default_value' => is_string($existing) && $existing !== ''
        ? $existing
        : $this->uuidService->generate(),
      // Putting the value in the page means it can come back empty — cleared in
      // the DOM, or dropped by something altering the form. An item must never
      // lose the identity it already had, so restore it on the way in.
      '#element_validate' => [[$this, 'validateUuid']],
    ];
  }

  /**
   * Restores the UUID when the submitted value came back empty.
   *
   * The element's #default_value is the value the widget built this delta with:
   * the stored UUID when the item already had one, and a freshly generated UUID
   * when it did not. Falling back to it therefore covers both halves of the
   * rule — an item that had a UUID keeps it regardless of what was submitted,
   * and an item that never had one still gets a valid value.
   *
   * A non-empty submitted value is always left alone, so this cannot overwrite
   * a UUID that arrived intact.
   *
   * @param array<string, mixed> $element
   *   The element being validated.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validateUuid(array &$element, FormStateInterface $form_state): void {
    $submitted = $element['#value'] ?? NULL;
    if (is_string($submitted) && $submitted !== '') {
      return;
    }

    $restored = $element['#default_value'] ?? NULL;
    if (!is_string($restored) || $restored === '') {
      $restored = $this->uuidService->generate();
    }

    $form_state->setValueForElement($element, $restored);
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSettingsForm(FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element['description'] = [
      '#type' => 'fieldset',
      '#description' => $this->t('This widget set a UUID value on the field the first time it is created and can be used as a unique identifier for the item in your custom code. This is the main use for the <em>uuid</em> field type.'),
    ];

    return $element;
  }

}
