<?php

namespace Drupal\geolocation_gpx\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\geolocation_gpx\Entity\GeolocationGpx;
use Drupal\geolocation_gpx\Plugin\Field\FieldType\GeolocationGpx as GeolocationGpxItem;
use Drupal\geolocation_gpx\GeolocationGpxImporter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a custom field widget.
 */
#[FieldWidget(
  id: 'geolocation_gpx_file',
  label: new \Drupal\Core\StringTranslation\TranslatableMarkup('Geolocation GPX File'),
  field_types: ['geolocation_gpx']
)]
class GeolocationGpxFileWidget extends WidgetBase {

  /**
   * Constructor.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected GeolocationGpxImporter $importer,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('geolocation_gpx.importer'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, Array $element, Array &$form, FormStateInterface $form_state): array {
    $item = $items[$delta];
    $gpx_id = $item->gpx_id ?? NULL;
    $gpx_file_id = $item->gpx_file_id ?? NULL;

    // Two alternative sources plus their dependency switch read as one thing,
    // and a multi-value field would otherwise run them together.
    $element += [
      '#type' => 'fieldset',
    ];

    $gpx = NULL;
    if ($gpx_id) {
      /** @var \Drupal\geolocation_gpx\Entity\GeolocationGpx|null $gpx */
      $gpx = $this->entityTypeManager->getStorage('geolocation_gpx')->load($gpx_id) ?? NULL;
      if ($gpx) {
        $element['summary'] = $gpx->renderedSummaryTable();
      }
    }

    $element['gpx_file_id'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('GPX File'),
      '#default_value' => $gpx_file_id ? [$gpx_file_id] : NULL,
      '#upload_location' => 'public://uploads/',
      '#upload_validators' => [
        'FileExtension' => ['extensions' => 'gpx xml'],
      ],
      '#description' => $this->t('Allowed file types: <i>gpx, xml</i>. The uploaded file will be parsed and the structure imported, <b>replacing</b> any existing.'),
    ];

    $element['or'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $this->t('- or -'),
      '#attributes' => [
        'class' => ['geolocation-gpx-widget-or'],
      ],
    ];

    // A GPX that came out of an upload belongs to the file above, so only an
    // item without a file is showing a GPX that was referenced by hand.
    $element['gpx_reference'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'geolocation_gpx',
      '#title' => $this->t('Existing GPX'),
      '#default_value' => $gpx_file_id ? NULL : $gpx,
      '#description' => $this->t('Reference a GPX data set that already exists, instead of importing one. Ignored if a file is uploaded as well.'),
    ];

    $parents = $element['#field_parents'];
    $parents[] = $this->fieldDefinition->getName();
    $selector = $root = array_shift($parents);
    if ($parents) {
      $selector = $root . '[' . implode('][', $parents) . ']';
    }

    $element['gpx_owned'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Delete the referenced GPX along with this item'),
      '#default_value' => ($gpx_id && !$gpx_file_id && $item instanceof GeolocationGpxItem) ? $item->isOwning() : FALSE,
      '#description' => $this->t('The referenced GPX is otherwise left alone when it is removed here, as it may well be in use elsewhere. An imported file is always deleted with its item.'),
      '#states' => [
        'visible' => [
          ':input[name="' . $selector . '[' . $delta . '][gpx_reference]"]' => ['filled' => TRUE],
        ],
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state): void {
    $form_state->set('old_items', $items);

    parent::extractFormValues($items, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state): array {
    $values = parent::massageFormValues($values, $form, $form_state);

    if (!$form_state->isValidationComplete()) {
      return [];
    }

    /** @var \Drupal\Core\Field\FieldItemListInterface<\Drupal\geolocation_gpx\Plugin\Field\FieldType\GeolocationGpx> $old_items */
    $old_items = $form_state->get('old_items');

    foreach ($values as $delta => &$value) {
      $old_item = $old_items->get($delta);
      $old_value = $old_item?->getValue() ?? [];
      $old_file_id = $old_value['gpx_file_id'] ?? NULL;
      $old_gpx_id = $old_value['gpx_id'] ?? NULL;
      $old_owned = $old_item?->isOwning() ?? TRUE;

      // Neither is a stored column, both only ever exist in the widget.
      unset($value['summary']);
      $reference_id = $value['gpx_reference'] ?: NULL;
      unset($value['gpx_reference']);

      $new_file_id = $value['gpx_file_id'][0] ?? NULL;
      $value['gpx_file_id'] = $new_file_id;

      if (!$new_file_id && !$reference_id) {
        $this->release($old_file_id, $old_gpx_id, $old_owned);

        $value['gpx_id'] = NULL;
        $value['gpx_owned'] = NULL;
        continue;
      }

      // An existing GPX was picked and no file was given, so nothing has to be
      // imported. Whether it may be deleted again is the user's call here.
      if (!$new_file_id) {
        // Anything the item held before is let go, except the very GPX now
        // being referenced.
        $this->release($old_file_id, $old_gpx_id == $reference_id ? NULL : $old_gpx_id, $old_owned);

        $value['gpx_id'] = $reference_id;
        $value['gpx_owned'] = (int) !empty($value['gpx_owned']);
        continue;
      }

      if ($reference_id) {
        \Drupal::messenger()->addWarning($this->t('A GPX file and an existing GPX were both given. The file takes precedence, the reference was discarded.'));
      }

      // The file was not touched, so there is nothing to import. Keep the
      // existing GPX entity instead of replacing it, or every save of the
      // host entity would discard both the uploaded file and any edits made
      // to the imported structure.
      if ($old_gpx_id && $new_file_id == $old_file_id) {
        $value['gpx_id'] = $old_gpx_id;
        $value['gpx_owned'] = (int) $old_owned;
        continue;
      }

      $new_gpx_file = File::load($new_file_id);

      try {
        $gpxFile = $this->importer->parse($new_gpx_file->getFileUri());
      }
      catch (\Exception $e) {
        $value['gpx_file_id'] = $old_file_id;
        $value['gpx_id'] = $old_gpx_id;
        $value['gpx_owned'] = (int) $old_owned;
        \Drupal::messenger()->addWarning($this->t('Could not import GPX file: @message Reset to previous value.', [
          '@message' => $e->getMessage(),
        ]));
        continue;
      }

      $this->release($old_file_id, $old_gpx_id, $old_owned);

      $value['gpx_id'] = $this->importer->import($gpxFile, $new_gpx_file->getFilename())->id();
      // A GPX imported here exists for this item alone.
      $value['gpx_owned'] = 1;
    }

    return $values;
  }

  /**
   * Let go of what an item pointed at before it was changed.
   *
   * @param int|string|null $file_id
   *   Uploaded file to delete, if any. It is only ever the import source of
   *   this one item, so it always goes.
   * @param int|string|null $gpx_id
   *   GPX to delete, if any and if owned.
   * @param bool $owned
   *   Whether the item was allowed to delete that GPX.
   */
  protected function release(int|string|null $file_id, int|string|null $gpx_id, bool $owned): void {
    if ($file_id) {
      File::load($file_id)?->delete();
    }

    if ($gpx_id && $owned) {
      GeolocationGpx::load($gpx_id)?->delete();
    }
  }

}
