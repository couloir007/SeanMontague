<?php

namespace Drupal\ireland_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ireland_import\Batch\IrelandImportBatch;

/**
 * Admin form for importing Ireland trip KMZ data.
 */
class IrelandImportForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'ireland_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['kmz_file'] = [
      '#type'              => 'managed_file',
      '#title'             => $this->t('KMZ file'),
      '#description'       => $this->t('Upload a Google Maps KMZ export containing Sites, Lodging Full Trip, and Day route folders.'),
      '#upload_location'   => 'temporary://',
      '#upload_validators' => ['file_validate_extensions' => ['kmz']],
      '#required'          => TRUE,
    ];

    $form['import_types'] = [
      '#type'          => 'checkboxes',
      '#title'         => $this->t('Import options'),
      '#options'       => [
        'pois'         => $this->t('Import Sites (POIs → geo_entity:poi)'),
        'destinations' => $this->t('Import Lodging (Destinations → geo_entity:destination)'),
        'routes'       => $this->t('Extract Routes (GeoJSON → public://geoshape/)'),
      ],
      '#default_value' => ['pois', 'destinations', 'routes'],
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type'  => 'submit',
      '#value' => $this->t('Import'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $fids = $form_state->getValue('kmz_file');
    $fid  = is_array($fids) ? reset($fids) : $fids;

    /** @var \Drupal\file\FileInterface|null $file */
    $file = \Drupal::entityTypeManager()->getStorage('file')->load($fid);
    if (!$file) {
      $this->messenger()->addError($this->t('Could not load the uploaded file.'));
      return;
    }

    $real_path = \Drupal::service('file_system')->realpath($file->getFileUri());
    $import_types = array_filter($form_state->getValue('import_types'));

    $parsed = IrelandImportBatch::parseKmz($real_path);
    if ($parsed === NULL) {
      $this->messenger()->addError($this->t('Failed to parse KMZ. Ensure the file is a valid Google Maps KMZ export containing a doc.kml entry.'));
      return;
    }

    $operations = [];

    if (!empty($import_types['pois'])) {
      foreach ($parsed['pois'] as $poi) {
        $operations[] = [[IrelandImportBatch::class, 'importPoi'], [$poi]];
      }
    }

    if (!empty($import_types['destinations'])) {
      foreach ($parsed['destinations'] as $dest) {
        $operations[] = [[IrelandImportBatch::class, 'importDestination'], [$dest]];
      }
    }

    if (!empty($import_types['routes'])) {
      foreach ($parsed['routes'] as $route) {
        $operations[] = [[IrelandImportBatch::class, 'writeRoute'], [$route]];
      }
    }

    if (empty($operations)) {
      $this->messenger()->addWarning($this->t('No items to import — no matching folders found in KMZ (expected: Sites, Lodging Full Trip, Day N…).'));
      return;
    }

    batch_set([
      'title'            => $this->t('Importing Ireland trip data'),
      'operations'       => $operations,
      'finished'         => [IrelandImportBatch::class, 'finished'],
      'init_message'     => $this->t('Starting import…'),
      'progress_message' => $this->t('Processing @current of @total.'),
      'error_message'    => $this->t('Import encountered errors.'),
    ]);
  }

}
