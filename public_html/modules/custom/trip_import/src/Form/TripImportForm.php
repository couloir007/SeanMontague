<?php

namespace Drupal\trip_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\trip_import\Batch\TripImportBatch;

/**
 * Admin form for importing trip KMZ data into Drupal content.
 *
 * Accepts up to 5 KMZ files in one submission — necessary because Google
 * My Maps limits layers per map, so a single trip may require multiple
 * exports (e.g. Day 1-5 routes, Day 6-7 routes, Destinations, Lodging).
 *
 * All KMZ files are parsed before any batch operations are queued, so
 * POI/destination/lodging IDs accumulated from earlier files are available
 * when createDayArticle runs for routes from later files.
 *
 * Operation order within the batch:
 *   1. importPoi        (all files)
 *   2. importDestination (all files)
 *   3. importLodging    (all files)
 *   4. createDayArticle (all files, days ascending)
 *   5. createTrip       (once, last)
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

    $form['kmz_files'] = [
      '#type'              => 'managed_file',
      '#title'             => $this->t('KMZ files'),
      '#description'       => $this->t('Upload up to 5 KMZ exports from Google My Maps. All files are parsed together before import begins, so POIs, destinations, and lodging from any file are attached to the correct day articles regardless of upload order. Expected folders: Points of Interest (or Sites), Destinations, Lodging, Day N route folders.'),
      '#upload_location'   => 'temporary://',
      '#upload_validators' => ['file_validate_extensions' => ['kmz']],
      '#multiple'          => TRUE,
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
    $fids         = $form_state->getValue('kmz_files');
    $trip_title   = $form_state->getValue('trip_title');
    $start_date   = $form_state->getValue('trip_start_date');
    $import_types = array_filter($form_state->getValue('import_types'));

    if (empty($fids)) {
      $this->messenger()->addError($this->t('No files uploaded.'));
      return;
    }

    $file_storage = \Drupal::entityTypeManager()->getStorage('file');
    $file_system  = \Drupal::service('file_system');

    // Parse ALL KMZ files first, merging results into one combined dataset.
    // This ensures destinations/lodging from a separate KMZ are available
    // when createDayArticle runs for routes from a different KMZ.
    $combined = ['pois' => [], 'destinations' => [], 'lodging' => [], 'days' => []];
    $parse_errors = [];

    foreach ((array) $fids as $fid) {
      /** @var \Drupal\file\FileInterface|null $file */
      $file = $file_storage->load($fid);
      if (!$file) {
        continue;
      }
      $real_path = $file_system->realpath($file->getFileUri());
      $parsed    = TripImportBatch::parseKmz($real_path);

      if ($parsed === NULL) {
        $parse_errors[] = $file->getFilename();
        continue;
      }

      $combined['pois']         = array_merge($combined['pois'], $parsed['pois']);
      $combined['destinations'] = array_merge($combined['destinations'], $parsed['destinations']);
      $combined['lodging']      = array_merge($combined['lodging'], $parsed['lodging']);

      // Merge days — if the same day number appears in multiple files,
      // merge their routes rather than overwriting.
      foreach ($parsed['days'] as $day => $day_data) {
        if (!isset($combined['days'][$day])) {
          $combined['days'][$day] = $day_data;
        }
        else {
          $combined['days'][$day]['routes'] = array_merge(
            $combined['days'][$day]['routes'],
            $day_data['routes']
          );
        }
      }
    }

    if (!empty($parse_errors)) {
      $this->messenger()->addWarning($this->t(
        'Could not parse: @files. Continuing with successfully parsed files.',
        ['@files' => implode(', ', $parse_errors)]
      ));
    }

    // Build operations in the correct order:
    // All entity imports first, then articles, then trip — so that
    // $context['results'] has all IDs available when createDayArticle runs.
    $operations = [];

    if (!empty($import_types['pois'])) {
      foreach ($combined['pois'] as $item) {
        $operations[] = [[TripImportBatch::class, 'importPoi'], [$item]];
      }
    }

    if (!empty($import_types['destinations'])) {
      foreach ($combined['destinations'] as $item) {
        $operations[] = [[TripImportBatch::class, 'importDestination'], [$item]];
      }
    }

    if (!empty($import_types['lodging'])) {
      foreach ($combined['lodging'] as $item) {
        $operations[] = [[TripImportBatch::class, 'importLodging'], [$item]];
      }
    }

    if (!empty($import_types['routes'])) {
      ksort($combined['days']);
      foreach ($combined['days'] as $day => $day_data) {
        if (!empty($day_data['routes'])) {
          $operations[] = [[TripImportBatch::class, 'createDayArticle'], [$day, $day_data['routes'], $start_date]];
        }
      }
    }

    if (!empty($import_types['trip'])) {
      $operations[] = [[TripImportBatch::class, 'createTrip'], [$trip_title, $start_date]];
    }

    if (empty($operations)) {
      $this->messenger()->addWarning($this->t('No items to import — no matching folders found in any KMZ file.'));
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
