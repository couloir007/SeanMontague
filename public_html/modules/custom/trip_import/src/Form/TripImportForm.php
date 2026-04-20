<?php

namespace Drupal\trip_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\trip_import\Batch\TripImportBatch;

/**
 * Admin form for importing trip KMZ data into Drupal content.
 */
class TripImportForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'trip_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['trip_title'] = [
      '#type'        => 'textfield',
      '#title'       => $this->t('Trip title'),
      '#description' => $this->t('Used as the TouristTrip node title.'),
      '#required'    => TRUE,
    ];

    $form['trip_start_date'] = [
      '#type'        => 'date',
      '#title'       => $this->t('Trip start date (Day 1)'),
      '#description' => $this->t('Used to compute schema_date_published for each day article (start + N−1 days).'),
      '#required'    => TRUE,
    ];

    $form['kmz_file'] = [
      '#type'              => 'managed_file',
      '#title'             => $this->t('KMZ file'),
      '#description'       => $this->t('Upload a Google Maps KMZ export. Expected folders: Points of Interest (or Sites), Destinations, Lodging (or Lodging Full Trip), Day N route folders.'),
      '#upload_location'   => 'temporary://',
      '#upload_validators' => ['file_validate_extensions' => ['kmz']],
      '#required'          => TRUE,
    ];

    $form['import_types'] = [
      '#type'          => 'checkboxes',
      '#title'         => $this->t('Import options'),
      '#options'       => [
        'pois'         => $this->t('Points of Interest → geo_entity:poi'),
        'destinations' => $this->t('Destinations → geo_entity:destination'),
        'lodging'      => $this->t('Lodging → geo_entity:lodging'),
        'routes'       => $this->t('Extract routes + create Article nodes'),
        'trip'         => $this->t('Create TouristTrip node'),
      ],
      '#default_value' => ['pois', 'destinations', 'lodging', 'routes', 'trip'],
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

    $real_path     = \Drupal::service('file_system')->realpath($file->getFileUri());
    $trip_title    = $form_state->getValue('trip_title');
    $start_date    = $form_state->getValue('trip_start_date');
    $import_types  = array_filter($form_state->getValue('import_types'));

    $parsed = TripImportBatch::parseKmz($real_path);
    if ($parsed === NULL) {
      $this->messenger()->addError($this->t('Failed to parse KMZ. Ensure the file is a valid Google Maps KMZ export containing a doc.kml entry.'));
      return;
    }

    $operations = [];

    if (!empty($import_types['pois'])) {
      foreach ($parsed['pois'] as $item) {
        $operations[] = [[TripImportBatch::class, 'importPoi'], [$item]];
      }
    }

    if (!empty($import_types['destinations'])) {
      foreach ($parsed['destinations'] as $item) {
        $operations[] = [[TripImportBatch::class, 'importDestination'], [$item]];
      }
    }

    if (!empty($import_types['lodging'])) {
      foreach ($parsed['lodging'] as $item) {
        $operations[] = [[TripImportBatch::class, 'importLodging'], [$item]];
      }
    }

    if (!empty($import_types['routes'])) {
      // Sort days ascending so articles are created in order.
      $days = $parsed['days'];
      ksort($days);
      foreach ($days as $day => $day_data) {
        if (!empty($day_data['routes'])) {
          $operations[] = [[TripImportBatch::class, 'createDayArticle'], [$day, $day_data['routes'], $start_date]];
        }
      }
    }

    if (!empty($import_types['trip'])) {
      $operations[] = [[TripImportBatch::class, 'createTrip'], [$trip_title, $start_date]];
    }

    if (empty($operations)) {
      $this->messenger()->addWarning($this->t('No items to import — no matching folders found in KMZ or no import options selected.'));
      return;
    }

    batch_set([
      'title'            => $this->t('Importing trip data'),
      'operations'       => $operations,
      'finished'         => [TripImportBatch::class, 'finished'],
      'init_message'     => $this->t('Starting import…'),
      'progress_message' => $this->t('Processing @current of @total.'),
      'error_message'    => $this->t('Import encountered errors.'),
    ]);
  }

}
