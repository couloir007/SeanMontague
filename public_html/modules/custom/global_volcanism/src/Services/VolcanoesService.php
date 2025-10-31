<?php

namespace Drupal\global_volcanism\Services;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\taxonomy\Entity\Term;

class VolcanoesService extends NoaaApiClient {

  protected function getOrCreateTermId(string $name, string $vocabulary): int {
    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties([
        'name' => $name,
        'vid' => $vocabulary,
      ]);
    $term = reset($terms);
    if (!$term) {
      $term = Term::create([
        'name' => $name,
        'vid' => $vocabulary,
      ]);
      $term->save();
    }
    return $term->id();
  }

  public function importVolcanoDataFromFile(string $filepath): int {
    $count_imported = 0;

    if (!file_exists($filepath)) {
      throw new \Exception("File not found: $filepath");
    }

    $handle = fopen($filepath, 'r');
    if ($handle === FALSE) {
      throw new \Exception("Unable to open the file $filepath");
    }

    $headers = fgetcsv($handle, 0, "\t");
    if (!$headers) {
      fclose($handle);
      throw new \Exception("Failed reading the header line.");
    }

    $storage = \Drupal::entityTypeManager()
      ->getStorage('si_mapping');

    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('bundle', 'world_volcanoes');

    $ids = $query->execute();

    $existing_volcanoes = [];
    if (!empty($ids)) {
      $entities = $storage->loadMultiple($ids);
      foreach ($entities as $entity) {
        $volcano_id = $entity->get('field_valcano_id')->value ?? NULL;
        if ($volcano_id !== NULL) {
          $existing_volcanoes[$volcano_id] = $entity;
        }
      }
    }

    while (($data = fgetcsv($handle, 0, "\t")) !== FALSE) {
      $row = array_combine($headers, $data);
      if (!$row) {
        continue;
      }
      $volcano_number = $row['Volcano Number'] ?? NULL;
      if (!$volcano_number) {
        continue;
      }

      if (isset($existing_volcanoes[$volcano_number])) {
        $entity = $existing_volcanoes[$volcano_number];
      } else {
        $entity = $storage->create([
          'bundle' => 'world_volcanoes',
        ]);
      }

      if ($row['Volcano Name'] ?? NULL) {
        $entity->set('label', $row['Volcano Name']);
      }

      $entity->set('field_valcano_id', $volcano_number);

      if ($row['Elevation (m)'] ?? NULL) {
        $entity->set('field_elevation', $row['Elevation (m)']);
      }

      if (isset($row['Longitude'], $row['Latitude'])) {
        $location_geojson = json_encode([
          "type" => "FeatureCollection",
          "features" => [
            [
              "type" => "Feature",
              "properties" => new \stdClass(),
              "geometry" => [
                "type" => "Point",
                "coordinates" => [
                  (float) $row['Longitude'],
                  (float) $row['Latitude'],
                ],
              ],
            ],
          ],
        ]);
        $entity->set('field_location', $location_geojson);
      }

      if (!empty($row['Country'])) {
        $country_tid = $this->getOrCreateTermId($row['Country'], 'country');
        $entity->set('field_country', ['target_id' => $country_tid]);
      }

      if (!empty($row['Location'])) {
        $locality_tid = $this->getOrCreateTermId($row['Location'], 'locality');
        $entity->set('field_locality', ['target_id' => $locality_tid]);
      }

      if (!empty($row['Type'])) {
        $type_tid = $this->getOrCreateTermId($row['Type'], 'volcano_type');
        $entity->set('field_type', ['target_id' => $type_tid]);
      }

      if (!empty($row['Status'])) {
        $status_tid = $this->getOrCreateTermId($row['Status'], 'volcano_status');
        $entity->set('field_status', ['target_id' => $status_tid]);
      }

      if (!empty($row['Last Known Eruption'])) {
        $eruption_tid = $this->getOrCreateTermId($row['Last Known Eruption'], 'last_known_eruption');
        $entity->set('field_last_known_eruption', ['target_id' => $eruption_tid]);
      }

      $entity->save();
      $count_imported++;
    }

    fclose($handle);

    $this->importVolcanicEventsFromApi();

    return $count_imported;
  }

  public function importVolcanicEventsFromApi(): int {
    $countImported = 0;

    // Fetch first page to get pagination info
    $firstPage = $this->fetchApiPage(1, 200);
    if (empty($firstPage)) {
      \Drupal::logger('global_volcanism')
        ->error('Failed to fetch first page from NOAA API.');
      return 0;
    }

    $totalItems = $firstPage['totalItems'] ?? 0;
    $itemsPerPage = $firstPage['itemsPerPage'] ?? 200;
    $totalPages = $firstPage['totalPages'] ?? (int) ceil($totalItems / $itemsPerPage);

    $entityManager = \Drupal::entityTypeManager();
    $eventStorage = $entityManager->getStorage('si_mapping_si_mapping_item');
    $volcanoStorage = $entityManager->getStorage('si_mapping');

    $volcanoCache = [];

    for ($page = 1; $page <= $totalPages; $page++) {
      $response = ($page === 1) ? $firstPage : $this->fetchApiPage($page, $itemsPerPage);
      if (empty($response['items'])) {
        continue;
      }

      foreach ($response['items'] as $eventData) {
        if (!isset($eventData['volcanoLocationNewNum'])) {
          // Skip this event and proceed with the next one
          continue;
        }
        // Load or create volcanic event entity by event id
        $existingEvents = $eventStorage->loadByProperties([
          'bundle' => 'volcanic_events',
          'field_event_id' => $eventData['id'],
        ]);
        $event = reset($existingEvents);
        if (!$event) {
          $event = $eventStorage->create(['bundle' => 'volcanic_events']);
        }

        $year = $eventData['year'] ?? null;
        $month = $eventData['month'] ?? null;
        $day = $eventData['day'] ?? null;

        if ($year) {
          if ($month && $day) {
            $timestamp = mktime(0, 0, 0, (int) $month, (int) $day, (int) $year);

            $titleDate = $year . '-' . $month . '-' . $day;
          } else {
            // Only year available
            $titleDate = $year;
            $timestamp = mktime(0, 0, 1, 1, 2, (int) $year);
          }

          // For datetime fields you can also do this.
          $date_formatter = \Drupal::service('date.formatter');
          $date = $date_formatter->format($timestamp, 'custom', 'Y-m-d H:i:s');

          $theDate = new DrupalDateTime($date, 'UTC');

          $event->set('field_eruption_date_text', $titleDate);

          if ($year > 999) {
            $event->set('field_eruption_date', ['value' => $theDate->format('Y-m-d\TH:i:s')]);
          }
        }

        $label = trim(($eventData['name'] ?? '') . ': ' . $titleDate);
        $event->set('label', $label);

        $event->set('field_event_id', $eventData['id']);
        $event->set('field_volcano_number', $eventData['volcanoLocationNewNum'] ?? NULL);

        // Set deathsTotal if available, or null otherwise
        $deathsTotal = isset($eventData['deathsTotal']) ? $eventData['deathsTotal'] : NULL;
        $event->set('field_deaths', $deathsTotal);

        $event->save();

        $volcanoId = $eventData['volcanoLocationNewNum'];

        if (!isset($volcanoCache[$volcanoId])) {
          $existingVolcanoes = $volcanoStorage->loadByProperties([
            'bundle' => 'world_volcanoes',
            'field_valcano_id' => $volcanoId,
          ]);
          $volcanoCache[$volcanoId] = reset($existingVolcanoes);
        }
        $volcano = $volcanoCache[$volcanoId];

        if (!$volcano) {
          $volcano = $volcanoStorage->create(['bundle' => 'world_volcanoes']);
          $volcano->set('field_valcano_id', $volcanoId);
        }

        // Check existing event references, append if missing
        $referencedIds = [];
        foreach ($volcano->get('field_eruption_events')->getValue() as $ref) {
          $referencedIds[] = $ref['target_id'];
        }

        if (!in_array($event->id(), $referencedIds, TRUE)) {
          $volcano->get('field_eruption_events')
            ->appendItem(['target_id' => $event->id()]);
          $volcano->save();
        }

        $countImported++;
      }
    }

    return $countImported;
  }

  protected function fetchApiPage(int $page, int $itemsPerPage): array {
    $url = "https://www.ngdc.noaa.gov/hazel/hazard-service/api/v1/volcanoes";
    $queryParams = http_build_query([
      'page' => $page,
      'itemsPerPage' => $itemsPerPage,
    ]);
    $fullUrl = $url . '?' . $queryParams;

    $httpClient = \Drupal::httpClient();
    try {
      $response = $httpClient->get($fullUrl);
      $data = json_decode($response->getBody()->getContents(), TRUE);
      return $data ?: [];
    }
    catch (\Exception $e) {
      \Drupal::logger('global_volcanism')
        ->error('API request failed on page @page: @message', [
          '@page' => $page,
          '@message' => $e->getMessage(),
        ]);
      return [];
    }
  }
}
